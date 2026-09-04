<?php
/**
 * Documentation REST Controller.
 *
 * @package Tribe\AI
 */

declare( strict_types=1 );

namespace Tribe\AI\REST;

use Tribe\AI\Documentation\Documentation_Registry;
use WP_Error;
use WP_REST_Controller;
use WP_REST_Request;
use WP_REST_Server;

/**
 * Class Documentation_Controller
 */
class Documentation_Controller extends WP_REST_Controller {
	/**
	 * Documentation registry.
	 *
	 * @var Documentation_Registry
	 */
	private Documentation_Registry $registry;

	/**
	 * Constructor.
	 *
	 * @param Documentation_Registry|null $registry Registry instance.
	 */
	public function __construct( ?Documentation_Registry $registry = null ) {
		$this->registry = $registry ?? new Documentation_Registry();
	}

	/**
	 * Register documentation routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			'tribe-ai/v1',
			'/docs',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_documentation' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'block_name' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => [ $this, 'validate_block_name' ],
					],
					'post_type'  => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					],
					'context'    => [
						'required'          => false,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					],
				],
			]
		);
	}

	/**
	 * Return documentation for a block.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_documentation( WP_REST_Request $request ) {
		$block_name = (string) $request->get_param( 'block_name' );

		if ( '' === $block_name ) {
			return new WP_Error(
				'invalid_block_name',
				__( 'A block name is required.', 'tribe-ai-assistant' ),
				[ 'status' => 400 ]
			);
		}

		$documentation = $this->registry->get_block_documentation(
			$block_name,
			$request->get_param( 'post_type' )
		);

		return [
			'success'      => true,
			'block'        => $documentation['block'],
			'items'        => $documentation['items'],
			'source'       => $documentation['source'],
			'last_updated' => $documentation['last_updated'],
		];
	}

	/**
	 * Permission check.
	 *
	 * @return bool
	 */
	public function check_permissions(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Validate block name.
	 *
	 * @param string $block_name Block name.
	 * @return bool
	 */
	public function validate_block_name( string $block_name ): bool {
		return '' !== trim( $block_name ) && strlen( $block_name ) <= 200;
	}
}
