<?php declare(strict_types=1);

namespace PatternSync\Sync;

use PatternSync\Connections\Connection_Manager;
use PatternSync\Patterns\Pattern_Registry_Service;

class Sync_Service {

	public function __construct(
		private readonly Connection_Manager $connection_manager,
		private readonly Pattern_Registry_Service $pattern_registry,
		private readonly Remote_Client $remote_client
	) {
	}

	/**
	 * Pull selected patterns from remote to local.
	 *
	 * @param string   $connection_id
	 * @param string[] $pattern_names
	 *
	 * @return array<int, array{name: string, success: bool, message: string}>
	 */
	public function pull( string $connection_id, array $pattern_names ): array {
		$connection = $this->connection_manager->get_by_id( $connection_id );
		if ( $connection === null ) {
			return [];
		}

		$remote_patterns = $this->remote_client->get_patterns(
			$connection['url'],
			$connection['username'],
			$connection['app_password']
		);

		$by_name = [];
		foreach ( $remote_patterns as $p ) {
			$n = $p['name'] ?? '';
			if ( $n === '' ) {
				continue;
			}

			$by_name[ $n ] = $p;
		}

		$results = [];
		foreach ( $pattern_names as $name ) {
			$pattern_def = $by_name[ $name ] ?? null;
			if ( $pattern_def === null ) {
				$results[] = [ 'name' => $name, 'success' => false, 'message' => __( 'Pattern not found on remote.', 'pattern-sync' ) ];
				continue;
			}
			if ( ! ( $pattern_def['syncable'] ?? false ) ) {
				$results[] = [ 'name' => $name, 'success' => false, 'message' => __( 'Only user-created or synced patterns can be pulled.', 'pattern-sync' ) ];
				continue;
			}

			$this->pattern_registry->store( $pattern_def );
			$results[] = [ 'name' => $name, 'success' => true, 'message' => __( 'Pulled successfully.', 'pattern-sync' ) ];
		}

		return $results;
	}

	/**
	 * Push selected patterns from local to remote.
	 *
	 * @param string   $connection_id
	 * @param string[] $pattern_names
	 *
	 * @return array<int, array{name: string, success: bool, message: string}>
	 */
	public function push( string $connection_id, array $pattern_names ): array {
		$connection = $this->connection_manager->get_by_id( $connection_id );
		if ( $connection === null ) {
			return [];
		}

		$results = [];
		foreach ( $pattern_names as $name ) {
			$pattern_def = $this->pattern_registry->get_by_name( $name );
			if ( $pattern_def === null ) {
				$results[] = [ 'name' => $name, 'success' => false, 'message' => __( 'Pattern not found locally.', 'pattern-sync' ) ];
				continue;
			}
			if ( ! ( $pattern_def['syncable'] ?? false ) ) {
				$results[] = [ 'name' => $name, 'success' => false, 'message' => __( 'Only user-created or synced patterns can be pushed.', 'pattern-sync' ) ];
				continue;
			}

			$result = $this->remote_client->post_pattern(
				$connection['url'],
				$connection['username'],
				$connection['app_password'],
				$pattern_def
			);

			$results[] = [
				'name'    => $name,
				'success' => $result['success'],
				'message' => $result['message'] ?? ( $result['success'] ? __( 'Pushed successfully.', 'pattern-sync' ) : __( 'Push failed.', 'pattern-sync' ) ),
			];
		}

		return $results;
	}

}
