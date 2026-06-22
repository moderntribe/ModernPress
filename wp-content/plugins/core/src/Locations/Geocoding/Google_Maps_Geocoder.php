<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations\Geocoding;

use Tribe\Plugin\Settings\Tribe_Settings;

/**
 * Google Geocoding API provider.
 *
 * Per-IP rate limits are enforced by Geocode_Endpoint via Geocode_Rate_Limiter before this
 * class is invoked. Unlike Nominatim, Google has no site-wide outbound spacing requirement.
 */
class Google_Maps_Geocoder implements Geocoder_Interface {

	private const string ENDPOINT = 'https://maps.googleapis.com/maps/api/geocode/json';
	private const int CACHE_TTL   = DAY_IN_SECONDS;

	public function __construct(
		private Tribe_Settings $settings,
	) {
	}

	public function geocode( string $address ): ?array {
		$address = trim( $address );
		$api_key = $this->settings->get_google_maps_api_key();

		if ( $address === '' || $api_key === '' ) {
			return null;
		}

		$cache_key = 'tribe_google_geocode_' . md5( strtolower( $address ) );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) && isset( $cached['lat'], $cached['lng'] ) ) {
			return [
				'lat' => (float) $cached['lat'],
				'lng' => (float) $cached['lng'],
			];
		}

		$url = add_query_arg(
			[
				'address' => $address,
				'key'     => $api_key,
			],
			self::ENDPOINT
		);

		$response = wp_remote_get(
			$url,
			[
				'timeout' => 10,
			]
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			return null;
		}

		$body   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$status = (string) ( $body['status'] ?? '' );

		$lat = $body['results'][0]['geometry']['location']['lat'] ?? null;
		$lng = $body['results'][0]['geometry']['location']['lng'] ?? null;

		if (
			! is_array( $body )
			|| 'OK' !== $status
			|| ! is_numeric( $lat )
			|| ! is_numeric( $lng )
		) {
			if ( 'ZERO_RESULTS' === $status ) {
				set_transient( $cache_key, [], self::CACHE_TTL );
			}

			return null;
		}

		$location = $body['results'][0]['geometry']['location'];

		$coordinates = [
			'lat' => (float) $location['lat'],
			'lng' => (float) $location['lng'],
		];

		set_transient( $cache_key, $coordinates, self::CACHE_TTL );

		return $coordinates;
	}

}
