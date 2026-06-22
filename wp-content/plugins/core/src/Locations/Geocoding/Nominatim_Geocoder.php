<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations\Geocoding;

class Nominatim_Geocoder implements Geocoder_Interface {

	private const string ENDPOINT = 'https://nominatim.openstreetmap.org/search';
	private const int CACHE_TTL   = DAY_IN_SECONDS;

	public function __construct(
		private Nominatim_Rate_Limiter $rate_limiter,
	) {
	}

	public function geocode( string $address ): ?array {
		$address = trim( $address );

		if ( $address === '' ) {
			return null;
		}

		$cache_key = 'tribe_nominatim_' . md5( strtolower( $address ) );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) && isset( $cached['lat'], $cached['lng'] ) ) {
			return [
				'lat' => (float) $cached['lat'],
				'lng' => (float) $cached['lng'],
			];
		}

		$url = add_query_arg(
			[
				'q'              => $address,
				'format'         => 'json',
				'limit'          => 1,
				'addressdetails' => 0,
			],
			self::ENDPOINT
		);

		return $this->rate_limiter->run_throttled(
			function () use ( $url, $cache_key ): ?array {
				$response = wp_remote_get(
					$url,
					[
						'timeout' => 10,
						'headers' => [
							'User-Agent' => $this->get_user_agent(),
							'Referer'    => home_url( '/' ),
						],
					]
				);

				if ( is_wp_error( $response ) ) {
					return null;
				}

				$status_code = (int) wp_remote_retrieve_response_code( $response );

				if ( $status_code < 200 || $status_code >= 300 ) {
					return null;
				}

				$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );

				$lat = $body[0]['lat'] ?? null;
				$lon = $body[0]['lon'] ?? null;

				if (
					! is_array( $body )
					|| ! is_numeric( $lat )
					|| ! is_numeric( $lon )
				) {
					return null;
				}

				$coordinates = [
					'lat' => (float) $lat,
					'lng' => (float) $lon,
				];

				set_transient( $cache_key, $coordinates, self::CACHE_TTL );

				return $coordinates;
			}
		);
	}

	private function get_user_agent(): string {
		$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$site_url  = home_url( '/' );

		return sprintf( '%1$s Location Geocoder (%2$s)', $site_name, $site_url );
	}

}
