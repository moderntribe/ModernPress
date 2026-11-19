<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

/**
 * Writes index rows for posts. Knows nothing about when to write.
 */
class Facet_Index_Writer {

	private const int ROWS_PER_QUERY = 500;

	public function __construct(
		private Facet_Registry $registry,
		private Facet_Index_Table $table,
		private \wpdb $wpdb,
	) {
	}

	/**
	 * Replace the rows of the given posts with their current terms. Posts that
	 * are not published, or have no facets, end up with no rows.
	 *
	 * @param list<int> $post_ids
	 */
	public function replace( array $post_ids ): void {
		$post_types = $this->registry->get_facet_post_types();

		if ( [] === $post_ids ) {
			return;
		}

		$this->delete( $post_ids );

		if ( [] === $post_types ) {
			return;
		}

		$posts = get_posts( [
			'post_type'              => $post_types,
			'post_status'            => 'publish',
			'include'                => $post_ids,
			'posts_per_page'         => count( $post_ids ),
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		] );

		if ( [] === $posts ) {
			return;
		}

		// One query for the whole batch instead of one per post and taxonomy.
		update_object_term_cache( wp_list_pluck( $posts, 'ID' ), $post_types );

		$this->insert( $this->build_rows( $posts ) );
	}

	/**
	 * @param list<int> $post_ids
	 *
	 * @return int Number of rows removed.
	 */
	public function delete( array $post_ids ): int {
		$deleted = 0;

		foreach ( array_chunk( $post_ids, self::ROWS_PER_QUERY ) as $chunk ) {
			$placeholders = implode( ', ', array_fill( 0, count( $chunk ), '%d' ) );
			$sql          = 'DELETE FROM ' . $this->table->table_name() . " WHERE post_id IN ( {$placeholders} )";

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$deleted += (int) $this->wpdb->query( $this->wpdb->prepare( $sql, $chunk ) );
		}

		return $deleted;
	}

	/**
	 * @param list<\WP_Post> $posts
	 *
	 * @return list<array{int, string, string, int, string}>
	 */
	private function build_rows( array $posts ): array {
		$rows = [];

		foreach ( $posts as $post ) {
			foreach ( $this->registry->get_all() as $facet ) {
				if ( ! in_array( $post->post_type, $facet['post_types'], true ) ) {
					continue;
				}

				$terms = get_the_terms( $post->ID, $facet['taxonomy'] );

				if ( ! is_array( $terms ) ) {
					continue;
				}

				foreach ( $terms as $term ) {
					$rows[] = [ $post->ID, $post->post_type, $facet['slug'], $term->term_id, $term->slug ];
				}
			}
		}

		return $rows;
	}

	/**
	 * @param list<array{int, string, string, int, string}> $rows
	 */
	private function insert( array $rows ): void {
		foreach ( array_chunk( $rows, self::ROWS_PER_QUERY ) as $chunk ) {
			$placeholders = array_fill( 0, count( $chunk ), '( %d, %s, %s, %d, %s )' );

			$sql = 'INSERT INTO ' . $this->table->table_name()
				. ' ( post_id, post_type, facet_slug, term_id, term_slug ) VALUES '
				. implode( ', ', $placeholders );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
			$this->wpdb->query( $this->wpdb->prepare( $sql, array_merge( ...$chunk ) ) );
		}
	}

}
