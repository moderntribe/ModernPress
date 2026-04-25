<?php declare(strict_types=1);

namespace PatternSync\Connections;

class Connection_Manager {

	public function __construct(
		private readonly Connection_Repository $repository
	) {
	}

	/**
	 * @return array<int, array{id: string, name: string, url: string, username: string}>
	 */
	public function get_all(): array {
		$all = $this->repository->get_all();

		return array_map( static function ( array $row ) {
			return [
				'id'       => $row['id'],
				'name'     => $row['name'],
				'url'      => $row['url'],
				'username' => $row['username'],
			];
		}, $all );
	}

	/**
	 * @return array{id: string, name: string, url: string, username: string, app_password: string}|null
	 */
	public function get_by_id( string $id ): ?array {
		return $this->repository->get_by_id( $id );
	}

	/**
	 * @param array{id?: string, name: string, url: string, username: string, app_password: string} $connection
	 */
	public function save( array $connection ): string {
		return $this->repository->save( $connection );
	}

	public function delete( string $id ): bool {
		return $this->repository->delete( $id );
	}

	/**
	 * Test REST connection to the given URL with username and application password.
	 * Returns true if GET pattern-sync/v1/patterns returns 200.
	 */
	public function test( string $url, string $username, string $app_password ): bool {
		$base     = untrailingslashit( $url );
		$rest_url = $base . '/wp-json/pattern-sync/v1/patterns?per_page=1';
		$response = $this->remote_get( $rest_url, $username, $app_password );

		return is_array( $response ) && ( $response['response']['code'] ?? 0 ) === 200;
	}

	/**
	 * Perform authenticated GET request. Returns wp_remote_get result (array or WP_Error).
	 *
	 * @return array{response: array{code: int}}|\WP_Error
	 */
	public function remote_get( string $url, string $username, string $app_password ): array|\WP_Error {
		$auth = base64_encode( $username . ':' . $app_password );

		return wp_remote_get( $url, [
			'headers' => [
				'Authorization' => 'Basic ' . $auth,
				'Content-Type'  => 'application/json',
			],
			'timeout' => 15,
		] );
	}

	/**
	 * Perform authenticated POST request with JSON body.
	 *
	 * @param array<string, mixed> $body
	 *
	 * @return array{response: array{code: int}, body: string}|\WP_Error
	 */
	public function remote_post( string $url, string $username, string $app_password, array $body ): array|\WP_Error {
		$auth = base64_encode( $username . ':' . $app_password );

		return wp_remote_post( $url, [
			'headers' => [
				'Authorization' => 'Basic ' . $auth,
				'Content-Type'  => 'application/json',
			],
			'body'    => wp_json_encode( $body ),
			'timeout' => 30,
		] );
	}

}
