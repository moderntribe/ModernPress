<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations\Geocoding;

/**
 * Rate limits geocode usage for the public REST endpoint and outbound provider requests.
 *
 * @see https://operations.osmfoundation.org/policies/nominatim/
 */
class Geocode_Rate_Limiter {

	private const int MAX_REST_REQUESTS_PER_IP = 10;
	private const int REST_WINDOW_SECONDS      = 60;
	private const float MIN_INTERVAL_SECONDS   = 1.0;
	private const string REST_TRANSIENT_PREFIX = 'tribe_geocode_rl_';
	private const string OUTBOUND_LOCK_NAME    = 'tribe_geocode_outbound';
	private const string LAST_OUTBOUND_OPTION  = 'tribe_geocode_last_outbound';
	private const int OUTBOUND_LOCK_TIMEOUT    = 30;

	/**
	 * Whether a client IP may call the public geocode REST endpoint.
	 */
	public function is_allowed(): bool {
		$ip      = $this->get_client_ip();
		$ip_hash = md5( $ip );

		if ( ! $this->has_min_interval_elapsed( $ip_hash ) ) {
			return false;
		}

		$key      = self::REST_TRANSIENT_PREFIX . $ip_hash;
		$attempts = (int) get_transient( $key );

		if ( $attempts >= self::MAX_REST_REQUESTS_PER_IP ) {
			return false;
		}

		set_transient( $key, $attempts + 1, self::REST_WINDOW_SECONDS );
		set_transient(
			self::REST_TRANSIENT_PREFIX . 'last_' . $ip_hash,
			microtime( true ),
			self::REST_WINDOW_SECONDS
		);

		return true;
	}

	/**
	 * Waits until at least one second has elapsed since the last outbound geocode request.
	 */
	public function wait_before_outbound_request(): void {
		$lock_acquired = $this->acquire_outbound_lock();

		if ( ! $lock_acquired ) {
			sleep( (int) ceil( self::MIN_INTERVAL_SECONDS ) );

			return;
		}

		try {
			$last_request = (float) get_option( self::LAST_OUTBOUND_OPTION, 0.0 );
			$wait_seconds = self::MIN_INTERVAL_SECONDS - ( microtime( true ) - $last_request );

			if ( $wait_seconds > 0 ) {
				usleep( (int) round( $wait_seconds * 1_000_000 ) );
			}

			update_option( self::LAST_OUTBOUND_OPTION, microtime( true ), false );
		} finally {
			$this->release_outbound_lock();
		}
	}

	private function has_min_interval_elapsed( string $ip_hash ): bool {
		$last_request = get_transient( self::REST_TRANSIENT_PREFIX . 'last_' . $ip_hash );

		if ( ! is_numeric( $last_request ) ) {
			return true;
		}

		return ( microtime( true ) - (float) $last_request ) >= self::MIN_INTERVAL_SECONDS;
	}

	private function acquire_outbound_lock(): bool {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT GET_LOCK(%s, %d)',
				self::OUTBOUND_LOCK_NAME,
				self::OUTBOUND_LOCK_TIMEOUT
			)
		);

		return '1' === (string) $result;
	}

	private function release_outbound_lock(): void {
		global $wpdb;

		$wpdb->get_var(
			$wpdb->prepare(
				'SELECT RELEASE_LOCK(%s)',
				self::OUTBOUND_LOCK_NAME
			)
		);
	}

	private function get_client_ip(): string {
		$ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );

		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return 'unknown';
	}

}
