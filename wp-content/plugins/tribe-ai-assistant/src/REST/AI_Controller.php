<?php
/**
 * AI REST Controller.
 *
 * @package Tribe\AI
 */

namespace Tribe\AI\REST;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_Error;

/**
 * Class AI_Controller
 */
class AI_Controller extends WP_REST_Controller {
	/**
	 * Register REST routes.
	 */
	public function register_routes(): void {
		// Content generation endpoint.
		register_rest_route(
			'tribe-ai/v1',
			'/generate',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate_content' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'prompt' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
						'validate_callback' => [ $this, 'validate_prompt' ],
					],
				],
			]
		);

		// Q&A endpoint.
		register_rest_route(
			'tribe-ai/v1',
			'/ask',
			[
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'ask_question' ],
				'permission_callback' => [ $this, 'check_permissions' ],
				'args'                => [
					'question' => [
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_textarea_field',
						'validate_callback' => static function ( string $v ): bool {
							return ! empty( $v ) && strlen( $v ) <= 500;
						},
					],
				],
			]
		);

		// Debug context endpoint.
		register_rest_route(
			'tribe-ai/v1',
			'/debug/context',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_debug_context' ],
				'permission_callback' => [ $this, 'check_admin_permissions' ],
			]
		);
	}

	/**
	 * Generate content endpoint.
	 *
	 * Uses the new Context_Builder to provide 3-tier context to the AI.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array|WP_Error Response data or error.
	 */
	public function generate_content( WP_REST_Request $request ) {
		$prompt = $request->get_param( 'prompt' );

		try {
			// Build 3-tier context.
			$context_builder = new \Tribe\AI\AI\Context_Builder(
				new \Tribe\AI\AI\Block_Registry_Analyzer(),
				new \Tribe\AI\AI\Site_Analyzer()
			);
			$context = $context_builder->build_context();

			// Get API key.
			$api_key = get_option( 'tribe_ai_api_key' );
			if ( empty( $api_key ) ) {
				return new WP_Error(
					'missing_api_key',
					__( 'OpenAI API key not configured.', 'tribe-ai-assistant' ),
					[ 'status' => 500 ]
				);
			}

			// Generate with enhanced context.
			$client = new \Tribe\AI\AI\OpenAI_Client( $api_key );
			$result = $client->generate_page( $prompt, $context );

			return [
				'success'   => true,
				'blocks'    => $result['blocks'],
				'reasoning' => $result['reasoning'],
				'metadata'  => [
					'blocks_available' => $context['metadata']['total_blocks'],
					'custom_blocks'    => $context['metadata']['custom_blocks'],
					'context_tokens'   => $this->estimate_context_tokens( $context ),
				],
			];

		} catch ( \Exception $e ) {
			return new WP_Error(
				'generation_failed',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Ask a question endpoint.
	 *
	 * Searches learn.tri.be, fetches matching articles, and passes them to OpenAI for a synthesized answer.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array|WP_Error Response data or error.
	 */
	public function ask_question( WP_REST_Request $request ) {
		$question = $request->get_param( 'question' );

		try {
			$api_key = get_option( 'tribe_ai_api_key' );
			if ( empty( $api_key ) ) {
				return new WP_Error(
					'missing_api_key',
					__( 'OpenAI API key not configured.', 'tribe-ai-assistant' ),
					[ 'status' => 500 ]
				);
			}

			$learn_host = 'https://learn.tri.be';
			$searcher   = new \Tribe\AI\AI\Learn_Searcher();
			$articles   = $searcher->search( $learn_host, $question );

			$client = new \Tribe\AI\AI\OpenAI_Client( $api_key );

			if ( empty( $articles ) ) {
				$result = $client->ask_from_knowledge( $question );

				return [
					'success' => true,
					'answer'  => $result['answer'],
					'sources' => [],
					'source'  => 'knowledge',
				];
			}

			$result = $client->ask_question( $question, $articles );

			return [
				'success' => true,
				'answer'  => $result['answer'],
				'sources' => $result['sources'],
				'source'  => $result['used_documentation'] ? 'documentation' : 'knowledge',
			];

		} catch ( \Exception $e ) {
			return new WP_Error(
				'ask_failed',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Get debug context endpoint.
	 *
	 * Returns full 3-tier context for debugging and verification.
	 * Admin-only access.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return array Response data.
	 */
	public function get_debug_context( WP_REST_Request $request ): array {
		$context_builder = new \Tribe\AI\AI\Context_Builder(
			new \Tribe\AI\AI\Block_Registry_Analyzer(),
			new \Tribe\AI\AI\Site_Analyzer()
		);

		$context = $context_builder->build_context();

		return [
			'tier_1_registry' => $context['tier_1_registry'],
			'tier_2_patterns' => $context['tier_2_patterns'],
			'tier_3_examples' => $context['tier_3_examples'],
			'metadata'        => $context['metadata'],
			'cache_status'    => [
				'registry_cached' => $this->is_registry_cached(),
				'site_cached'     => $this->is_site_cached(),
			],
			'token_estimates' => [
				'tier_1' => $this->estimate_tokens( $context['tier_1_registry'] ),
				'tier_2' => $this->estimate_tokens( $context['tier_2_patterns'] ),
				'tier_3' => $this->estimate_tokens( $context['tier_3_examples'] ),
				'total'  => $this->estimate_context_tokens( $context ),
			],
		];
	}

	/**
	 * Check user permissions.
	 *
	 * @return bool Whether user has permission.
	 */
	public function check_permissions(): bool {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Check admin permissions.
	 *
	 * Used for debug endpoints that should only be accessible to admins.
	 *
	 * @return bool Whether user has admin permission.
	 */
	public function check_admin_permissions(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Validate prompt parameter.
	 *
	 * @param string $prompt Prompt to validate.
	 * @return bool Whether prompt is valid.
	 */
	public function validate_prompt( string $prompt ): bool {
		return ! empty( $prompt ) && strlen( $prompt ) <= 1000;
	}

	/**
	 * Estimate token count for content.
	 *
	 * @param mixed $content Content to measure.
	 * @return int Estimated tokens.
	 */
	private function estimate_tokens( $content ): int {
		$text = is_array( $content ) ? wp_json_encode( $content ) : (string) $content;
		return (int) ceil( strlen( $text ) / 3.5 );
	}

	/**
	 * Estimate total context tokens.
	 *
	 * @param array $context Full context array.
	 * @return int Estimated total tokens.
	 */
	private function estimate_context_tokens( array $context ): int {
		return $this->estimate_tokens( $context );
	}

	/**
	 * Check if registry cache exists.
	 *
	 * @return bool True if registry is cached.
	 */
	private function is_registry_cached(): bool {
		$analyzer = new \Tribe\AI\AI\Block_Registry_Analyzer();
		return $analyzer->is_cached();
	}

	/**
	 * Check if site context cache exists.
	 *
	 * @return bool True if site context is cached.
	 */
	private function is_site_cached(): bool {
		return false !== get_transient( 'tribe_ai_site_context' );
	}
}
