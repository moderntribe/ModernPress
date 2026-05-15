<?php declare(strict_types=1);

namespace PatternSync\Rest;

use PatternSync\Core\Abstract_Subscriber;
use PatternSync\Rest\Controllers\Patterns_Controller;
use WP_REST_Server;

class Rest_Subscriber extends Abstract_Subscriber {

	private const NAMESPACE = 'pattern-sync/v1';

	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ], 10, 0 );
	}

	public function register_routes(): void {
		$controller = $this->container->get( Patterns_Controller::class );

		register_rest_route( self::NAMESPACE, '/patterns', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $controller, 'get_patterns' ],
			'permission_callback' => [ $this, 'check_manage_options' ],
			'args'                => [
				'page'     => [
					'type'              => 'integer',
					'default'           => 1,
					'minimum'           => 1,
					'sanitize_callback' => 'absint',
				],
				'per_page' => [
					'type'              => 'integer',
					'default'           => 10,
					'minimum'           => 1,
					'maximum'           => 100,
					'sanitize_callback' => 'absint',
				],
			],
		] );

		register_rest_route( self::NAMESPACE, '/pattern-categories', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [ $controller, 'get_categories' ],
			'permission_callback' => [ $this, 'check_manage_options' ],
		] );

		register_rest_route( self::NAMESPACE, '/patterns', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [ $controller, 'create_pattern' ],
			'permission_callback' => [ $this, 'check_manage_options' ],
		] );
	}

	public function check_manage_options(): bool {
		return current_user_can( 'manage_options' );
	}

}
