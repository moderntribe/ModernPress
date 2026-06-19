<?php declare(strict_types=1);

namespace Tribe\Plugin\Routes\API;

use Tribe\Plugin\Locations\Geocoding\Geocoder_Interface;
use Tribe\Plugin\Routes\Abstract_Route;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Geocode_Endpoint extends Abstract_Route {

	public function __construct(
		private Geocoder_Interface $geocoder,
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
		$query = trim( (string) $request->get_param( 'q' ) );

		if ( $query === '' ) {
			return new WP_Error(
				'tribe_geocode_missing_query',
				__( 'A search query is required.', 'tribe' ),
				[ 'status' => 400 ]
			);
		}

		$coordinates = $this->geocoder->geocode( $query );

		if ( null === $coordinates ) {
			return new WP_Error(
				'tribe_geocode_not_found',
				__( 'No results were found for that search.', 'tribe' ),
				[ 'status' => 404 ]
			);
		}

		return new WP_REST_Response( [
			'lat'  => $coordinates['lat'],
			'lng'  => $coordinates['lng'],
			'name' => $query,
		], 200 );
	}

}
