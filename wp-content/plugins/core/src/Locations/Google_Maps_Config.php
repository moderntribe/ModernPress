<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations;

/**
 * Google Maps region defaults for geocoding and Places autocomplete.
 *
 * Override per project via the tribe/google_maps/region and tribe/google_maps/country filters.
 */
class Google_Maps_Config {

	public const string DEFAULT_REGION  = 'us';
	public const string DEFAULT_COUNTRY = 'US';

	public function get_region(): string {
		/**
		 * Filter the Google Maps region bias used for geocoding and Places requests.
		 *
		 * @param string $region ISO 3166-1 alpha-2 country code used as the region bias.
		 */
		$region = apply_filters( 'tribe/google_maps/region', self::DEFAULT_REGION );

		return is_string( $region ) && $region !== '' ? strtolower( $region ) : self::DEFAULT_REGION;
	}

	public function get_country(): string {
		/**
		 * Filter the Google Maps country restriction used for geocoding and Places requests.
		 *
		 * @param string $country ISO 3166-1 alpha-2 country code.
		 */
		$country = apply_filters( 'tribe/google_maps/country', self::DEFAULT_COUNTRY );

		return is_string( $country ) && $country !== '' ? strtoupper( $country ) : self::DEFAULT_COUNTRY;
	}

	public function get_geocode_components(): string {
		return 'country:' . $this->get_country();
	}

}
