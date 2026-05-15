<?php declare(strict_types=1);

namespace PatternSync\Sync;

use PatternSync\Connections\Connection_Manager;

class Remote_Client {

	public function __construct(
		private readonly Connection_Manager $connection_manager
	) {
	}

	/**
	 * Fetch all patterns from remote site (paginated if needed).
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public function get_patterns( string $base_url, string $username, string $app_password ): array {
		$url      = untrailingslashit( $base_url ) . '/wp-json/pattern-sync/v1/patterns';
		$all      = [];
		$page     = 1;
		$per_page = 100;

		do {
			$response = $this->connection_manager->remote_get(
				$url . '?per_page=' . $per_page . '&page=' . $page,
				$username,
				$app_password
			);

			if ( is_wp_error( $response ) ) {
				return [];
			}

			$code = (int) ( $response['response']['code'] ?? 0 );
			if ( $code !== 200 ) {
				return $all;
			}

			$body = wp_remote_retrieve_body( $response );
			$data = json_decode( $body, true );
			if ( ! is_array( $data ) ) {
				return $all;
			}

			$all         = array_merge( $all, $data );
			$total_pages = (int) ( wp_remote_retrieve_header( $response, 'x-wp-totalpages' ) ?: 1 );
			$page++;
		} while ( $page <= $total_pages );

		return $all;
	}

	/**
	 * POST a single pattern to the remote site.
	 *
	 * @param array<string, mixed> $pattern_def
	 *
	 * @return array{success: bool, message?: string}
	 */
	public function post_pattern( string $base_url, string $username, string $app_password, array $pattern_def ): array {
		$url      = untrailingslashit( $base_url ) . '/wp-json/pattern-sync/v1/patterns';
		$response = $this->connection_manager->remote_post( $url, $username, $app_password, $pattern_def );

		if ( is_wp_error( $response ) ) {
			return [ 'success' => false, 'message' => $response->get_error_message() ];
		}

		$code = (int) ( $response['response']['code'] ?? 0 );
		if ( $code >= 200 && $code < 300 ) {
			return [ 'success' => true ];
		}

		$body    = wp_remote_retrieve_body( $response );
		$data    = json_decode( $body, true );
		$message = is_array( $data ) && isset( $data['message'] ) ? $data['message'] : (string) $code;

		return [ 'success' => false, 'message' => $message ];
	}

}
