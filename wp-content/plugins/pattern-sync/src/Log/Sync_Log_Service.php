<?php declare(strict_types=1);

namespace PatternSync\Log;

class Sync_Log_Service {

	private const OPTION = 'pattern_sync_log';

	private const MAX_ENTRIES = 100;

	/**
	 * Add a sync run to the log.
	 *
	 * @param string   $connection_id
	 * @param string   $direction  'pull' or 'push'
	 * @param int      $user_id
	 * @param array<int, array{name: string, success: bool, message: string}> $results
	 */
	public function add_entry( string $connection_id, string $direction, int $user_id, array $results ): void {
		$success_count   = count( array_filter( $results, static fn ( $r ) => $r['success'] ) );
		$overall_success = $success_count === count( $results );

		$entry = [
			'id'              => 'log_' . wp_generate_password( 8, false ),
			'connection_id'   => $connection_id,
			'direction'       => $direction,
			'user_id'         => $user_id,
			'timestamp'       => time(),
			'patterns'        => $results,
			'overall_success' => $overall_success,
		];

		$log = get_option( self::OPTION, [] );
		if ( ! is_array( $log ) ) {
			$log = [];
		}
		array_unshift( $log, $entry );
		$log = array_slice( $log, 0, self::MAX_ENTRIES );
		update_option( self::OPTION, $log, true );
	}

	/**
	 * Get recent log entries.
	 *
	 * @return array<int, array{id: string, connection_id: string, direction: string, user_id: int, timestamp: int, patterns: array, overall_success: bool}>
	 */
	public function get_recent( int $limit = 50 ): array {
		$log = get_option( self::OPTION, [] );
		if ( ! is_array( $log ) ) {
			return [];
		}

		return array_slice( $log, 0, $limit );
	}

}
