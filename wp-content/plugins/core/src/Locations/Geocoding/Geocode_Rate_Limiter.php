<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations\Geocoding;

/**
 * Per-IP rate limits for the public geocode REST endpoint.
 *
 * Enforced once per search in Geocode_Endpoint before any geocoder runs (Google or Nominatim).
 * Nominatim additionally uses Nominatim_Rate_Limiter for site-wide outbound spacing.
 */
class Geocode_Rate_Limiter {

	private const int MAX_REST_REQUESTS_PER_IP = 10;
	private const int REST_WINDOW_SECONDS      = 60;
	private const float MIN_INTERVAL_SECONDS   = 1.0;
	private const string REST_TRANSIENT_PREFIX = 'tribe_geocode_rl_';

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

	private function has_min_interval_elapsed( string $ip_hash ): bool {
		$last_request = get_transient( self::REST_TRANSIENT_PREFIX . 'last_' . $ip_hash );

		if ( ! is_numeric( $last_request ) ) {
			return true;
		}

		return ( microtime( true ) - (float) $last_request ) >= self::MIN_INTERVAL_SECONDS;
	}

	private function get_client_ip(): string {
		$ip = (string) ( $_SERVER['REMOTE_ADDR'] ?? '' );

		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return $ip;
		}

		return 'unknown';
	}

}
