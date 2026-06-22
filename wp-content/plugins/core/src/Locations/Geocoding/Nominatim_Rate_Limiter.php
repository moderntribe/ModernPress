<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations\Geocoding;

/**
 * Serializes outbound Nominatim requests to one per second per site.
 *
 * @see https://operations.osmfoundation.org/policies/nominatim/
 */
class Nominatim_Rate_Limiter {

	private const float MIN_REQUEST_INTERVAL  = 1.0;
	private const string LOCK_NAME            = 'tribe_nominatim_geocode';
	private const string LAST_REQUEST_OPTION  = 'tribe_nominatim_last_request';
	private const int LOCK_TIMEOUT_SECONDS    = 30;
	private const int LOCK_RETRY_MICROSECONDS = 100_000;

	/**
	 * Runs a Nominatim API request with site-wide throttling.
	 *
	 * Holds a MySQL lock for the duration of the callback so concurrent requests
	 * cannot overlap, and records the completion time after the callback returns.
	 *
	 * @template T
	 *
	 * @param callable(): T $callback Outbound Nominatim request callback.
	 *
	 * @return T
	 */
	public function run_throttled( callable $callback ) {
		$this->acquire_lock_with_retry();

		try {
			$last_request = (float) get_option( self::LAST_REQUEST_OPTION, 0.0 );
			$wait_seconds = self::MIN_REQUEST_INTERVAL - ( microtime( true ) - $last_request );

			if ( $wait_seconds > 0 ) {
				usleep( (int) round( $wait_seconds * 1_000_000 ) );
			}

			return $callback();
		} finally {
			update_option( self::LAST_REQUEST_OPTION, microtime( true ), false );
			$this->release_lock();
		}
	}

	private function acquire_lock_with_retry(): void {
		while ( ! $this->acquire_lock() ) {
			usleep( self::LOCK_RETRY_MICROSECONDS );
		}
	}

	private function acquire_lock(): bool {
		global $wpdb;

		$result = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT GET_LOCK(%s, %d)',
				self::LOCK_NAME,
				self::LOCK_TIMEOUT_SECONDS
			)
		);

		return '1' === (string) $result;
	}

	private function release_lock(): void {
		global $wpdb;

		$wpdb->get_var(
			$wpdb->prepare(
				'SELECT RELEASE_LOCK(%s)',
				self::LOCK_NAME
			)
		);
	}

}
