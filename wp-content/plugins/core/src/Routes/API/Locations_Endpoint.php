<?php declare(strict_types=1);

namespace Tribe\Plugin\Routes\API;

use Tribe\Plugin\Locations\Location_Data;
use Tribe\Plugin\Post_Types\Location\Location;
use Tribe\Plugin\Routes\Abstract_Route;
use WP_REST_Request;
use WP_REST_Response;

class Locations_Endpoint extends Abstract_Route {

	public function get_endpoint(): string {
		return 'locations';
	}

	public function register_rest_route( array $args = [] ): void {
		parent::register_rest_route( wp_parse_args( $args, [
			'args' => [
				'ids' => [
					'type'              => 'string',
					'required'          => false,
					'sanitize_callback' => 'sanitize_text_field',
				],
				'lat' => [
					'type'     => 'number',
					'required' => false,
				],
				'lng' => [
					'type'     => 'number',
					'required' => false,
				],
				'r'   => [
					'type'    => 'number',
					'default' => 30,
				],
			],
		] ) );
	}

	public function route_callback( WP_REST_Request $request ): WP_REST_Response {
		$ids = $request->get_param( 'ids' );

		if ( is_string( $ids ) && $ids !== '' ) {
			$post_ids = array_filter( array_map( 'absint', explode( ',', $ids ) ) );

			return new WP_REST_Response( [
				'locations' => Location_Data::get_locations_by_ids( $post_ids ),
			], 200 );
		}

		$lat = $request->get_param( 'lat' );
		$lng = $request->get_param( 'lng' );

		if ( is_numeric( $lat ) && is_numeric( $lng ) ) {
			$radius = (float) $request->get_param( 'r' );

			return new WP_REST_Response( [
				'locations' => Location_Data::get_locations_nearby( (float) $lat, (float) $lng, $radius ),
			], 200 );
		}

		$query = new \WP_Query( [
			'post_type'      => Location::NAME,
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );

		return new WP_REST_Response( [
			'locations' => Location_Data::get_locations_by_ids( $query->posts ),
		], 200 );
	}

}
