<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

/**
 * Keeps the index current as content changes.
 *
 * Changes queue post IDs and the queue is flushed once on shutdown, so a save
 * that touches several taxonomies reindexes the post once.
 */
class Facet_Index_Sync {

	/**
	 * Above this, a term change schedules a full rebuild instead of reindexing
	 * each attached post.
	 */
	private const int TERM_REINDEX_LIMIT = 500;

	/**
	 * @var array<int, true>
	 */
	private array $queue = [];

	public function __construct(
		private Facet_Registry $registry,
		private Facet_Index_Table $table,
		private Facet_Index_Writer $writer,
		private Facet_Index_Rebuilder $rebuilder,
	) {
	}

	public function queue_post( int $post_id ): void {
		if ( ! in_array( (string) get_post_type( $post_id ), $this->registry->get_facet_post_types(), true ) ) {
			return;
		}

		$this->queue[ $post_id ] = true;
	}

	public function queue_post_for_taxonomy( int $post_id, string $taxonomy ): void {
		if ( ! $this->registry->uses_taxonomy( $taxonomy ) ) {
			return;
		}

		$this->queue_post( $post_id );
	}

	public function flush_queue(): void {
		$post_ids    = array_keys( $this->queue );
		$this->queue = [];

		if ( [] === $post_ids || ! $this->table->is_writable() ) {
			return;
		}

		$this->writer->replace( $post_ids );
		$this->table->bump_cache_version();
	}

	public function delete_post( int $post_id ): void {
		if ( ! $this->table->is_writable() ) {
			return;
		}

		if ( $this->writer->delete( [ $post_id ] ) <= 0 ) {
			return;
		}

		$this->table->bump_cache_version();
	}

	public function reindex_term( int $term_id, string $taxonomy ): void {
		$object_ids = get_objects_in_term( $term_id, $taxonomy );

		if ( is_wp_error( $object_ids ) ) {
			return;
		}

		$this->reindex_posts( $taxonomy, $object_ids );
	}

	/**
	 * On term deletion pass the hook's object IDs: get_objects_in_term() is
	 * empty once the term is gone.
	 *
	 * @param list<int|string> $object_ids
	 */
	public function reindex_posts( string $taxonomy, array $object_ids ): void {
		if ( ! $this->registry->uses_taxonomy( $taxonomy ) || ! $this->table->is_writable() ) {
			return;
		}

		if ( count( $object_ids ) > self::TERM_REINDEX_LIMIT ) {
			$this->rebuilder->schedule();

			return;
		}

		foreach ( $object_ids as $object_id ) {
			$this->queue_post( (int) $object_id );
		}
	}

}
