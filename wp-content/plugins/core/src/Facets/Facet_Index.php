<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

/**
 * Read side of the denormalized post/term index (one row per post + facet + term),
 * so a multi-facet filter resolves in a single grouped query instead of one
 * taxonomy join per facet. Rows are written by Facet_Index_Writer.
 */
class Facet_Index {

	private const int CACHE_TTL = 6 * HOUR_IN_SECONDS;

	public function __construct(
		private Facet_Index_Table $table,
		private \wpdb $wpdb,
	) {
	}

	/**
	 * One page of matching post IDs, newest first, plus the full match count.
	 *
	 * Paginating here rather than handing every match to WP_Query keeps
	 * `post__in` the size of a page instead of the size of the result set.
	 *
	 * @param array<string, list<string>> $selections Facet slug => selected term slugs.
	 * @param list<string>                $post_types
	 *
	 * @return array{ids: list<int>, total: int}|null Null when the index cannot answer:
	 *                                                not built, or no facet constraints are active.
	 */
	public function get_page( array $selections, array $post_types, int $per_page, int $paged ): ?array {
		$active = array_filter( $selections, static fn ( array $terms ): bool => [] !== $terms );

		if ( [] === $active || ! $this->table->is_built() ) {
			return null;
		}

		$match = $this->build_match_clause( $active, $post_types );

		return [
			'ids'   => $this->query_page( $match, count( $active ), max( 1, $per_page ), max( 1, $paged ) ),
			'total' => $this->query_total( $match, count( $active ) ),
		];
	}

	/**
	 * Term slugs that have published posts of the given types.
	 *
	 * `hide_empty` on get_terms() counts every post type sharing a taxonomy, so
	 * without this a directory lists terms that lead to zero results.
	 *
	 * @param list<string> $post_types
	 *
	 * @return list<string>|null Null when the index cannot answer.
	 */
	public function get_available_term_slugs( string $facet_slug, array $post_types ): ?array {
		if ( [] === $post_types || ! $this->table->is_built() ) {
			return null;
		}

		sort( $post_types );

		$key   = 'tribe_facet_terms_' . $this->table->get_cache_version() . '_'
			. md5( (string) wp_json_encode( [ $facet_slug, $post_types ] ) );
		$slugs = get_transient( $key );

		if ( ! is_array( $slugs ) ) {
			$slugs = $this->query_available_term_slugs( $facet_slug, $post_types );

			set_transient( $key, $slugs, self::CACHE_TTL );
		}

		return array_map( 'strval', array_values( $slugs ) );
	}

	/**
	 * Shared WHERE fragment and bound values for the page and count queries.
	 *
	 * AND across facets, OR within a facet. The AND half is enforced by the
	 * callers' HAVING clause, since a post matches only when it carries a row
	 * for every constrained facet.
	 *
	 * @param array<string, list<string>> $selections
	 * @param list<string>                $post_types
	 *
	 * @return array{where: string, values: list<int|string>}
	 */
	private function build_match_clause( array $selections, array $post_types ): array {
		$clauses = [];
		$values  = [];

		foreach ( $selections as $slug => $terms ) {
			$clauses[] = '( i.facet_slug = %s AND i.term_slug IN ( ' . $this->placeholders( count( $terms ) ) . ' ) )';
			$values[]  = (string) $slug;
			$values    = array_merge( $values, $terms );
		}

		$where = '( ' . implode( ' OR ', $clauses ) . ' )';

		if ( [] !== $post_types ) {
			$where .= ' AND i.post_type IN ( ' . $this->placeholders( count( $post_types ) ) . ' )';
			$values = array_merge( $values, $post_types );
		}

		return [
			'where'  => $where,
			'values' => $values,
		];
	}

	/**
	 * Join to wp_posts for ordering rather than denormalizing post_date: it is
	 * an eq_ref join on the primary key of rows already being read, and it
	 * keeps the status check authoritative if a row ever goes stale.
	 *
	 * @param array{where: string, values: list<int|string>} $match
	 *
	 * @return list<int>
	 */
	private function query_page( array $match, int $facet_count, int $per_page, int $paged ): array {
		// post_date joins the GROUP BY rather than being aggregated so the sort
		// stays index-friendly; it is functionally dependent on post_id anyway.
		$sql = 'SELECT i.post_id ' . $this->match_sql( $match )
			. ' GROUP BY i.post_id, p.post_date'
			. ' HAVING COUNT( DISTINCT i.facet_slug ) = %d'
			. ' ORDER BY p.post_date DESC, i.post_id DESC'
			. ' LIMIT %d OFFSET %d';

		$values = array_merge( $match['values'], [ $facet_count, $per_page, ( $paged - 1 ) * $per_page ] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return array_map( 'intval', $this->wpdb->get_col( $this->wpdb->prepare( $sql, $values ) ) );
	}

	/**
	 * @param array{where: string, values: list<int|string>} $match
	 */
	private function query_total( array $match, int $facet_count ): int {
		$sql = 'SELECT COUNT(*) FROM ( SELECT i.post_id ' . $this->match_sql( $match )
			. ' GROUP BY i.post_id'
			. ' HAVING COUNT( DISTINCT i.facet_slug ) = %d ) matches';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $this->wpdb->get_var( $this->wpdb->prepare( $sql, array_merge( $match['values'], [ $facet_count ] ) ) );
	}

	/**
	 * @param list<string> $post_types
	 *
	 * @return list<string>
	 */
	private function query_available_term_slugs( string $facet_slug, array $post_types ): array {
		$sql = 'SELECT DISTINCT i.term_slug FROM ' . $this->table->table_name() . ' i'
			. " INNER JOIN {$this->wpdb->posts} p ON p.ID = i.post_id"
			. " WHERE i.facet_slug = %s AND i.post_type IN ( {$this->placeholders( count( $post_types ) )} )"
			. " AND p.post_status = 'publish'";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$results = $this->wpdb->get_col( $this->wpdb->prepare( $sql, array_merge( [ $facet_slug ], $post_types ) ) );

		return array_map( 'strval', $results );
	}

	/**
	 * @param array{where: string, values: list<int|string>} $match
	 */
	private function match_sql( array $match ): string {
		return 'FROM ' . $this->table->table_name() . ' i'
			. " INNER JOIN {$this->wpdb->posts} p ON p.ID = i.post_id"
			. " WHERE {$match['where']} AND p.post_status = 'publish'";
	}

	private function placeholders( int $count ): string {
		return implode( ', ', array_fill( 0, $count, '%s' ) );
	}

}
