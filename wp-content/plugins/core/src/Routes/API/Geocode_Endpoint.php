<?php declare(strict_types=1);

namespace Tribe\Plugin\Routes\API;

use Tribe\Plugin\Locations\Geocoding\Geocode_Rate_Limiter;
use Tribe\Plugin\Locations\Geocoding\Geocoder_Interface;
use Tribe\Plugin\Locations\Geocoding\Nominatim_Geocoder;
use Tribe\Plugin\Routes\Abstract_Route;
use Tribe\Plugin\Settings\Tribe_Settings;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Geocode_Endpoint extends Abstract_Route {

	public function __construct(
		private Geocoder_Interface $geocoder,
		private Nominatim_Geocoder $nominatim_geocoder,
		private Geocode_Rate_Limiter $rate_limiter,
		private Tribe_Settings $settings,
	) {
	}

	public function get_endpoint(): string {
		return 'geocode';
	}

	public function register_rest_route( array $args = [] ): void {
		parent::register_rest_route( wp_parse_args( $args, [
			'args' => [
				'q' => [
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] ) );
	}

	public function route_callback( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		if ( ! $this->rate_limiter->is_allowed() ) {
			return new WP_Error(
				'tribe_geocode_rate_limited',
				__( 'Too many search requests. Please wait a moment and try again.', 'tribe' ),
				[ 'status' => 429 ]
			);
		}

		$query = trim( (string) $request->get_param( 'q' ) );

		if ( $query === '' ) {
			return new WP_Error(
				'tribe_geocode_missing_query',
				__( 'A search query is required.', 'tribe' ),
				[ 'status' => 400 ]
			);
		}

		$coordinates = $this->geocoder->geocode( $query );

		if ( null === $coordinates && $this->settings->is_google_geocoder_active() ) {
			$coordinates = $this->nominatim_geocoder->geocode( $query );
		}

		if ( null === $coordinates ) {
			return new WP_Error(
				'tribe_geocode_not_found',
				__( 'No results were found for that search.', 'tribe' ),
				[ 'status' => 422 ]
			);
		}

		return new WP_REST_Response( [
			'lat'  => $coordinates['lat'],
			'lng'  => $coordinates['lng'],
			'name' => $query,
		], 200 );
	}

}
