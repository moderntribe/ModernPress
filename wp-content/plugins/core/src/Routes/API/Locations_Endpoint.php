<?php declare(strict_types=1);

namespace Tribe\Plugin\Routes\API;

use Tribe\Plugin\Locations\Location_Data;
use Tribe\Plugin\Post_Types\Location\Location;
use Tribe\Plugin\Routes\Abstract_Route;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class Locations_Endpoint extends Abstract_Route {

	public function __construct(
		private Location_Data $location_data,
	) {
	}

	public function get_endpoint(): string {
		return 'locations';
	}

	public function register_rest_route( array $args = [] ): void {
		parent::register_rest_route( wp_parse_args( $args, [
			'args' => [
				'ids'   => [
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'lat'   => [
					'type'     => 'number',
					'required' => false,
				],
				'lng'   => [
					'type'     => 'number',
					'required' => false,
				],
				'r'     => [
					'type'              => 'number',
					'default'           => 30,
					'minimum'           => 1,
					'maximum'           => 100,
					'sanitize_callback' => static fn( mixed $value ): float => max( 1.0, min( 100.0, (float) $value ) ),
				],
				'state' => [
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'city'  => [
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'zip'   => [
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] ) );
	}

	public function route_callback( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$ids = $request->get_param( 'ids' );

		if ( is_string( $ids ) && $ids !== '' ) {
			$post_ids = array_filter( array_map( 'absint', explode( ',', $ids ) ) );

			return new WP_REST_Response( [
				'locations' => $this->location_data->get_locations_by_ids( $post_ids ),
			], 200 );
		}

		$zip = trim( (string) $request->get_param( 'zip' ) );

		if ( $zip !== '' ) {
			return new WP_REST_Response( [
				'locations' => $this->location_data->get_locations_by_zip( $zip ),
			], 200 );
		}

		$city  = trim( (string) $request->get_param( 'city' ) );
		$state = trim( (string) $request->get_param( 'state' ) );

		if ( $city !== '' && $state !== '' ) {
			return new WP_REST_Response( [
				'locations' => $this->location_data->get_locations_by_city( $city, $state ),
			], 200 );
		}

		if ( $state !== '' ) {
			return new WP_REST_Response( [
				'locations' => $this->location_data->get_locations_by_state( $state ),
			], 200 );
		}

		$lat = $request->get_param( 'lat' );
		$lng = $request->get_param( 'lng' );

		if ( is_numeric( $lat ) && is_numeric( $lng ) ) {
			$lat = (float) $lat;
			$lng = (float) $lng;

			if ( ! $this->is_valid_coordinate( $lat, $lng ) ) {
				return new WP_Error(
					'tribe_locations_invalid_coordinates',
					__( 'Invalid latitude or longitude.', 'tribe' ),
					[ 'status' => 400 ]
				);
			}

			$radius = (float) $request->get_param( 'r' );

			return new WP_REST_Response( [
				'locations' => $this->location_data->get_locations_nearby( $lat, $lng, $radius ),
			], 200 );
		}

		$query = new \WP_Query( [
			'post_type'      => Location::NAME,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );

		return new WP_REST_Response( [
			'locations' => $this->location_data->get_locations_by_ids( $query->posts ),
		], 200 );
	}

	private function is_valid_coordinate( float $lat, float $lng ): bool {
		return $lat >= -90.0 && $lat <= 90.0 && $lng >= -180.0 && $lng <= 180.0;
	}

}
