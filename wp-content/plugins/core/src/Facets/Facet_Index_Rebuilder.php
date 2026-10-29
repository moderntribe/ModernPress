<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

/**
 * Full, resumable index rebuild.
 *
 * Hosts kill requests after a fixed time, so a rebuild works in batches inside
 * a time budget, remembers where it stopped, and resumes from WP-Cron. Until it
 * finishes the index counts as unbuilt and directories use taxonomy queries.
 */
class Facet_Index_Rebuilder {

	public const string HOOK = 'tribe_facets_rebuild_index';

	private const int BATCH_SIZE       = 200;
	private const int DEFAULT_BUDGET   = 20;
	private const int START_DELAY      = 30;
	private const int RESUME_DELAY     = 5;
	private const int SAFETY_NET_DELAY = 60;

	public function __construct(
		private Facet_Registry $registry,
		private Facet_Index_Table $table,
		private Facet_Index_Writer $writer,
		private \wpdb $wpdb,
	) {
	}

	/**
	 * Request a fresh rebuild from WP-Cron. Also drops a running one: it would
	 * mix old and new facet definitions.
	 */
	public function schedule(): void {
		$this->table->set_progress( null );
		$this->schedule_event( self::START_DELAY );
	}

	/**
	 * Cron entry point: continue a running rebuild, or start one.
	 */
	public function run(): void {
		if ( null === $this->table->get_progress() ) {
			$this->start();
		}

		$this->process();
	}

	public function start(): void {
		$this->table->maybe_create();

		// Unbuilt while truncating, so requests during a rebuild (or after a
		// fatal) use the taxonomy-query fallback instead of a partial index.
		$this->table->set_built( false );
		$this->table->bump_cache_version();
		$this->table->truncate();
		$this->table->set_progress( [
			'cursor'  => 0,
			'indexed' => 0,
			'total'   => $this->count_posts(),
		] );
	}

	/**
	 * Index batches until the time budget runs out, then hand over to cron.
	 */
	public function process(): void {
		$progress = $this->table->get_progress();

		if ( null === $progress ) {
			return;
		}

		// Survives a fatal or timeout mid-batch, which would skip the reschedule below.
		$this->schedule_event( self::SAFETY_NET_DELAY );

		$deadline = microtime( true ) + $this->get_time_budget();

		do {
			$post_ids = $this->get_next_post_ids( $progress['cursor'] );

			if ( [] === $post_ids ) {
				$this->finish();

				return;
			}

			$this->writer->replace( $post_ids );

			$progress['cursor']   = (int) end( $post_ids );
			$progress['indexed'] += count( $post_ids );
			$this->table->set_progress( $progress );
		} while ( microtime( true ) < $deadline );

		$this->schedule_event( self::RESUME_DELAY );
	}

	private function finish(): void {
		$this->table->set_built( true );
		$this->table->bump_cache_version();
		$this->table->set_progress( null );

		wp_clear_scheduled_hook( self::HOOK );
	}

	private function schedule_event( int $delay ): void {
		wp_clear_scheduled_hook( self::HOOK );
		wp_schedule_single_event( time() + $delay, self::HOOK );
	}

	private function get_time_budget(): int {
		$limit = (int) ini_get( 'max_execution_time' );

		if ( $limit <= 0 ) {
			return self::DEFAULT_BUDGET;
		}

		return min( self::DEFAULT_BUDGET, (int) floor( $limit * 0.6 ) );
	}

	/**
	 * @return list<int>
	 */
	private function get_next_post_ids( int $after_id ): array {
		[ $where, $values ] = $this->get_scope();

		if ( '' === $where ) {
			return [];
		}

		$sql = "SELECT ID FROM {$this->wpdb->posts} WHERE {$where} AND ID > %d ORDER BY ID ASC LIMIT %d";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$ids = $this->wpdb->get_col( $this->wpdb->prepare( $sql, array_merge( $values, [ $after_id, self::BATCH_SIZE ] ) ) );

		return array_map( 'intval', $ids );
	}

	private function count_posts(): int {
		[ $where, $values ] = $this->get_scope();

		if ( '' === $where ) {
			return 0;
		}

		$sql = "SELECT COUNT(*) FROM {$this->wpdb->posts} WHERE {$where}";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $this->wpdb->get_var( $this->wpdb->prepare( $sql, $values ) );
	}

	/**
	 * WHERE fragment for published posts of the indexed types, empty when no
	 * facet is configured.
	 *
	 * @return array{string, list<string>}
	 */
	private function get_scope(): array {
		$post_types = $this->registry->get_facet_post_types();

		if ( [] === $post_types ) {
			return [ '', [] ];
		}

		$placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

		return [ "post_status = 'publish' AND post_type IN ( {$placeholders} )", $post_types ];
	}

}
