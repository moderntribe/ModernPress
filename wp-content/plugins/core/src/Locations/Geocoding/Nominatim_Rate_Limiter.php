<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations\Geocoding;

/**
 * Enforces Nominatim's maximum of one outbound request per second per site.
 *
 * @see https://operations.osmfoundation.org/policies/nominatim/
 */
class Nominatim_Rate_Limiter {

	private const float MIN_REQUEST_INTERVAL = 1.0;
	private const string LOCK_NAME           = 'tribe_nominatim_geocode';
	private const string LAST_REQUEST_OPTION = 'tribe_nominatim_last_request';
	private const int LOCK_TIMEOUT_SECONDS   = 30;

	public function wait_before_request(): void {
		$lock_acquired = $this->acquire_lock();

		if ( ! $lock_acquired ) {
			sleep( (int) ceil( self::MIN_REQUEST_INTERVAL ) );

			return;
		}

		try {
			$last_request = (float) get_option( self::LAST_REQUEST_OPTION, 0.0 );
			$wait_seconds = self::MIN_REQUEST_INTERVAL - ( microtime( true ) - $last_request );

			if ( $wait_seconds > 0 ) {
				usleep( (int) round( $wait_seconds * 1_000_000 ) );
			}

			update_option( self::LAST_REQUEST_OPTION, microtime( true ), false );
		} finally {
			$this->release_lock();
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
