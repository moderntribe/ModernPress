<?php declare(strict_types=1);

namespace Tribe\Plugin\Routes;

use WP_REST_Request;
use WP_REST_Server;

abstract class Abstract_Route {

	public string $namespace = 'tribe';
	public string $version   = 'v1';

	abstract public function get_endpoint(): string;

	abstract public function route_callback( WP_REST_Request $request ): \WP_REST_Response|\WP_HTTP_Response|\WP_Error;

	public function register_rest_route( array $args = [] ): void {
		$route_params = wp_parse_args( $args, [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $this, 'route_callback' ],
			'permission_callback' => '__return_true',
		] );

		register_rest_route(
			"{$this->namespace}/{$this->version}",
			'/' . $this->get_endpoint(),
			$route_params
		);
	}

}
