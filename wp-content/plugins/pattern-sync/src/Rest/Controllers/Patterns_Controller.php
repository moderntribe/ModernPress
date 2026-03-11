<?php declare(strict_types=1);

namespace PatternSync\Rest\Controllers;

use PatternSync\Patterns\Pattern_Registry_Service;
use WP_REST_Request;
use WP_REST_Response;

class Patterns_Controller {

	public function __construct(
		private readonly Pattern_Registry_Service $pattern_registry
	) {
	}

	public function get_patterns( WP_REST_Request $request ): WP_REST_Response {
		$all    = $this->pattern_registry->list_all();
		$page   = (int) $request->get_param( 'page' );
		$per    = (int) $request->get_param( 'per_page' );
		$per    = $per >= 1 && $per <= 100 ? $per : 10;
		$offset = ( $page >= 1 ? $page - 1 : 0 ) * $per;
		$slice  = array_slice( $all, $offset, $per );
		$total  = count( $all );

		$response = new WP_REST_Response( $slice, 200 );
		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) ( (int) ceil( $total / $per ) ) );

		return $response;
	}

	public function get_categories( WP_REST_Request $request ): WP_REST_Response {
		$categories = [];
		if ( class_exists( 'WP_Block_Pattern_Categories_Registry' ) ) {
			$registry = \WP_Block_Pattern_Categories_Registry::get_instance();
			if ( method_exists( $registry, 'get_all_registered' ) ) {
				$registered = $registry->get_all_registered();
				foreach ( $registered as $cat ) {
					$categories[] = [
						'name'        => $cat['name'] ?? '',
						'label'       => $cat['label'] ?? '',
						'description' => $cat['description'] ?? '',
					];
				}
			}
		}

		return new WP_REST_Response( $categories, 200 );
	}

	public function create_pattern( WP_REST_Request $request ): WP_REST_Response {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) {
			return new WP_REST_Response( [ 'code' => 'invalid_body', 'message' => __( 'Invalid JSON body.', 'pattern-sync' ) ], 400 );
		}

		$title   = $body['title'] ?? '';
		$content = $body['content'] ?? '';
		if ( $title === '' || $content === '' ) {
			return new WP_REST_Response( [ 'code' => 'missing_fields', 'message' => __( 'title and content are required.', 'pattern-sync' ) ], 400 );
		}

		$this->pattern_registry->store( $body );
		$name  = $body['name'] ?? ( $this->pattern_registry->get_name_prefix() . sanitize_title( $title ) );
		$saved = $this->pattern_registry->get_by_name( $name );

		return new WP_REST_Response( $saved ?? array_merge( $body, [ 'name' => $name ] ), 201 );
	}

}
