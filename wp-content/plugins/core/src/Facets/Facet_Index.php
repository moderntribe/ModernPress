<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

/**
 * Denormalized post/term index backing faceted directory queries.
 *
 * One row per post + facet + term, so a multi-facet filter resolves in a single
 * grouped query instead of one taxonomy join per facet.
 */
class Facet_Index {

	public const string TABLE                 = 'tribe_facet_index';
	public const string SCHEMA_VERSION_OPTION = 'tribe_facet_index_schema';
	public const string BUILT_OPTION          = 'tribe_facet_index_built';
	public const string CACHE_VERSION_OPTION  = 'tribe_facet_index_version';
	public const string REBUILD_HOOK          = 'tribe_facets_rebuild_index';

	private const string SCHEMA_VERSION = '1';
	private const int    CACHE_TTL      = 6 * HOUR_IN_SECONDS;

	/**
	 * Above this, a term edit triggers a full rebuild instead of per-post reindexing.
	 *
	 * ponytail: naive threshold. Upgrade path is a batched cron queue if large
	 * taxonomies start timing out on term slug changes.
	 */
	private const int TERM_REINDEX_LIMIT = 500;

	private bool $cache_bumped = false;

	public function __construct(
		private Facet_Registry $registry,
	) {
	}

	public function table_name(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	public function maybe_create_table(): void {
		if ( get_option( self::SCHEMA_VERSION_OPTION ) === self::SCHEMA_VERSION ) {
			return;
		}

		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = $this->table_name();

		dbDelta( "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			post_type varchar(32) NOT NULL DEFAULT '',
			facet_slug varchar(64) NOT NULL DEFAULT '',
			term_id bigint(20) unsigned NOT NULL DEFAULT 0,
			term_slug varchar(200) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY facet_term (facet_slug, term_slug(100)),
			KEY post_facet (post_id, facet_slug),
			KEY post_type (post_type)
		) {$wpdb->get_charset_collate()}" );

		update_option( self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION, false );
	}

	public function is_built(): bool {
		return (bool) get_option( self::BUILT_OPTION, false );
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
	 * @return array{ids: list<int>, total: int}|null Null when no facet constraints are active.
	 */
	public function get_page( array $selections, array $post_types, int $per_page, int $paged ): ?array {
		$active = array_filter( $selections, static fn ( array $terms ): bool => [] !== $terms );

		if ( [] === $active ) {
			return null;
		}

		$per_page = max( 1, $per_page );
		$paged    = max( 1, $paged );

		// Cached apart: the total is the same for every page of a selection,
		// and counting is the more expensive of the two queries.
		$ids = $this->get_cached(
			$this->get_cache_key( $active, $post_types, $per_page, $paged ),
			fn (): array => $this->query_page( $active, $post_types, $per_page, $paged )
		);

		$total = $this->get_cached(
			$this->get_cache_key( $active, $post_types ),
			fn (): int => $this->query_total( $active, $post_types )
		);

		return [
			'ids'   => is_array( $ids ) ? array_map( 'intval', array_values( $ids ) ) : [],
			'total' => (int) $total,
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
		if ( ! $this->is_built() || [] === $post_types ) {
			return null;
		}

		$slugs = $this->get_cached(
			$this->get_term_cache_key( $facet_slug, $post_types ),
			fn (): array => $this->query_available_term_slugs( $facet_slug, $post_types )
		);

		return is_array( $slugs ) ? array_map( 'strval', array_values( $slugs ) ) : null;
	}

	/**
	 * Reindex a single post.
	 *
	 * Rows are computed and compared against what is already stored before
	 * anything is written, so a save that did not change this post's facet
	 * terms writes nothing and invalidates nothing. That matters because
	 * `set_object_terms` fires once per taxonomy, meaning one save reaches this
	 * method several times over.
	 *
	 * @param bool $stored_rows_known_empty Skip the comparison because the
	 *                                      caller just emptied the table.
	 */
	public function index_post( int $post_id, bool $stored_rows_known_empty = false ): void {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$rows = 'publish' === $post->post_status ? $this->build_rows( $post ) : [];

		if ( ! $stored_rows_known_empty && $rows === $this->get_stored_rows( $post_id ) ) {
			return;
		}

		$this->delete_post( $post_id );
		$this->insert_rows( $rows );
		$this->bump_cache_version_once();
	}

	public function delete_post( int $post_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $this->table_name(), [ 'post_id' => $post_id ], [ '%d' ] );

		$this->bump_cache_version_once();
	}

	/**
	 * Reindex every post referenced by current facet definitions.
	 *
	 * ponytail: single-request loop in batches of 500. Upgrade path is an
	 * incremental cron queue if a site outgrows one request.
	 *
	 * @return int Number of posts indexed.
	 */
	public function rebuild(): int {
		$this->maybe_create_table();

		$post_types = $this->get_indexed_post_types();

		// Mark unavailable before TRUNCATE so requests during rebuild (or after
		// a fatal) use the taxonomy-query fallback instead of an empty table.
		update_option( self::BUILT_OPTION, false, false );
		$this->bump_cache_version();

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'TRUNCATE TABLE ' . $this->table_name() );

		if ( [] === $post_types ) {
			return 0;
		}

		$indexed = 0;
		$paged   = 1;

		do {
			$ids = get_posts( [
				'post_type'              => $post_types,
				'post_status'            => 'publish',
				'posts_per_page'         => 500,
				'paged'                  => $paged,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			] );

			foreach ( $ids as $id ) {
				// The table was just truncated, so skip the per-post read that
				// change detection would otherwise do for every row.
				$this->index_post( (int) $id, true );
				$indexed++;
			}

			$paged++;
		} while ( count( $ids ) === 500 );

		update_option( self::BUILT_OPTION, true, false );
		$this->bump_cache_version();

		return $indexed;
	}

	/**
	 * Reindex posts attached to a term, or schedule a rebuild when the term is large.
	 */
	public function reindex_term( int $term_id, string $taxonomy ): void {
		$object_ids = get_objects_in_term( $term_id, $taxonomy );

		if ( is_wp_error( $object_ids ) ) {
			return;
		}

		$this->reindex_posts( $taxonomy, $object_ids );
	}

	/**
	 * Reindex posts after a term change. Call this from delete_term with the
	 * hook's object IDs: get_objects_in_term() is empty once the term is gone.
	 *
	 * @param list<int|string> $object_ids
	 */
	public function reindex_posts( string $taxonomy, array $object_ids ): void {
		if ( ! $this->is_indexed_taxonomy( $taxonomy ) ) {
			return;
		}

		if ( count( $object_ids ) > self::TERM_REINDEX_LIMIT ) {
			$this->schedule_rebuild();

			return;
		}

		foreach ( $object_ids as $object_id ) {
			$this->index_post( (int) $object_id );
		}
	}

	public function schedule_rebuild(): void {
		if ( wp_next_scheduled( self::REBUILD_HOOK ) ) {
			return;
		}

		wp_schedule_single_event( time() + 30, self::REBUILD_HOOK );
	}

	public function is_indexed_taxonomy( string $taxonomy ): bool {
		foreach ( $this->registry->get_all() as $facet ) {
			if ( $facet['taxonomy'] === $taxonomy ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Shared WHERE fragment and bound values for the page and count queries.
	 *
	 * AND across facets, OR within a facet. The AND half is enforced by the
	 * caller's HAVING clause, since a post matches only when it carries a row
	 * for every constrained facet.
	 *
	 * Pure, so the self-check can assert its shape without a database.
	 *
	 * @param array<string, list<string>> $selections
	 * @param list<string>                $post_types
	 *
	 * @return array{where: string, values: list<string>}
	 */
	public static function build_match_clause( array $selections, array $post_types ): array {
		$clauses = [];
		$values  = [];

		foreach ( $selections as $slug => $terms ) {
			$placeholders = implode( ', ', array_fill( 0, count( $terms ), '%s' ) );
			$clauses[]    = "( i.facet_slug = %s AND i.term_slug IN ( {$placeholders} ) )";
			$values[]     = (string) $slug;

			foreach ( $terms as $term ) {
				$values[] = $term;
			}
		}

		$where = '( ' . implode( ' OR ', $clauses ) . ' )';

		if ( [] !== $post_types ) {
			$type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
			$where            .= " AND i.post_type IN ( {$type_placeholders} )";

			foreach ( $post_types as $post_type ) {
				$values[] = $post_type;
			}
		}

		return [
			'where'  => $where,
			'values' => $values,
		];
	}

	/**
	 * Read-through transient wrapper.
	 *
	 * Only a literal `false` counts as a miss, so an empty page and a zero
	 * total both cache rather than recomputing on every request.
	 */
	private function get_cached( string $key, callable $compute ): mixed {
		$cached = get_transient( $key );

		if ( false !== $cached ) {
			return $cached;
		}

		$value = $compute();

		set_transient( $key, $value, self::CACHE_TTL );

		return $value;
	}

	/**
	 * The rows a post should have, in a comparable order.
	 *
	 * @return list<array{int, string, string, int, string}>
	 */
	private function build_rows( \WP_Post $post ): array {
		$facets = $this->get_facets_for_post_type( $post->post_type );

		if ( [] === $facets ) {
			return [];
		}

		$rows = [];

		foreach ( $this->group_by_taxonomy( $facets ) as $taxonomy => $taxonomy_facets ) {
			$terms = wp_get_object_terms( $post->ID, $taxonomy );

			if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
				continue;
			}

			foreach ( $taxonomy_facets as $facet ) {
				foreach ( $terms as $term ) {
					$rows[] = [
						(int) $post->ID,
						(string) $post->post_type,
						(string) $facet['slug'],
						(int) $term->term_id,
						(string) $term->slug,
					];
				}
			}
		}

		return self::sort_rows( $rows );
	}

	/**
	 * The rows a post currently has, in the same order build_rows() produces.
	 *
	 * @return list<array{int, string, string, int, string}>
	 */
	private function get_stored_rows( int $post_id ): array {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT post_id, post_type, facet_slug, term_id, term_slug FROM ' . $this->table_name() . ' WHERE post_id = %d',
				$post_id
			),
			ARRAY_A
		);

		if ( ! is_array( $results ) ) {
			return [];
		}

		return self::sort_rows( array_map(
			static fn ( array $row ): array => [
				(int) $row['post_id'],
				(string) $row['post_type'],
				(string) $row['facet_slug'],
				(int) $row['term_id'],
				(string) $row['term_slug'],
			],
			$results
		) );
	}

	/**
	 * Join to wp_posts for ordering rather than denormalizing post_date: it is
	 * an eq_ref join on the primary key of rows already being read, and it
	 * keeps the status check authoritative if a row ever goes stale.
	 *
	 * @param array{where: string, values: list<string>} $match
	 */
	private function match_sql( array $match ): string {
		global $wpdb;

		return 'FROM ' . $this->table_name() . ' i'
			. " INNER JOIN {$wpdb->posts} p ON p.ID = i.post_id"
			. " WHERE {$match['where']} AND p.post_status = 'publish'";
	}

	/**
	 * @param array<string, list<string>> $selections
	 * @param list<string>                $post_types
	 *
	 * @return list<int>
	 */
	private function query_page( array $selections, array $post_types, int $per_page, int $paged ): array {
		global $wpdb;

		$match    = self::build_match_clause( $selections, $post_types );
		$values   = $match['values'];
		$values[] = count( $selections );
		$values[] = $per_page;
		$values[] = ( $paged - 1 ) * $per_page;

		// post_date joins the GROUP BY rather than being aggregated so the sort
		// stays index-friendly; it is functionally dependent on post_id anyway.
		$sql = 'SELECT i.post_id ' . $this->match_sql( $match )
			. ' GROUP BY i.post_id, p.post_date'
			. ' HAVING COUNT( DISTINCT i.facet_slug ) = %d'
			. ' ORDER BY p.post_date DESC, i.post_id DESC'
			. ' LIMIT %d OFFSET %d';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_col( $wpdb->prepare( $sql, $values ) );

		return array_map( 'intval', $results );
	}

	/**
	 * @param list<string> $post_types
	 *
	 * @return list<string>
	 */
	private function query_available_term_slugs( string $facet_slug, array $post_types ): array {
		global $wpdb;

		$values = [ $facet_slug ];
		$where  = 'i.facet_slug = %s';

		$where .= ' AND i.post_type IN ( ' . implode( ', ', array_fill( 0, count( $post_types ), '%s' ) ) . ' )';

		foreach ( $post_types as $post_type ) {
			$values[] = $post_type;
		}

		$sql = 'SELECT DISTINCT i.term_slug FROM ' . $this->table_name() . ' i'
			. " INNER JOIN {$wpdb->posts} p ON p.ID = i.post_id"
			. " WHERE {$where} AND p.post_status = 'publish'";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_col( $wpdb->prepare( $sql, $values ) );

		return array_map( 'strval', $results );
	}

	/**
	 * Separate prefix from the result-set keys so a facet slug can never
	 * collide with a selection cache entry.
	 *
	 * @param list<string> $post_types
	 */
	private function get_term_cache_key( string $facet_slug, array $post_types ): string {
		sort( $post_types );

		return 'tribe_facet_terms_' . $this->get_cache_version() . '_'
			. md5( (string) wp_json_encode( [ $facet_slug, $post_types ] ) );
	}

	/**
	 * @param array<string, list<string>> $selections
	 * @param list<string>                $post_types
	 */
	private function query_total( array $selections, array $post_types ): int {
		global $wpdb;

		$match    = self::build_match_clause( $selections, $post_types );
		$values   = $match['values'];
		$values[] = count( $selections );

		$sql = 'SELECT COUNT(*) FROM ( SELECT i.post_id ' . $this->match_sql( $match )
			. ' GROUP BY i.post_id'
			. ' HAVING COUNT( DISTINCT i.facet_slug ) = %d ) matches';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $values ) );
	}

	/**
	 * @param list<array{int, string, string, int, string}> $rows
	 */
	private function insert_rows( array $rows ): void {
		if ( [] === $rows ) {
			return;
		}

		global $wpdb;

		$placeholders = [];
		$values       = [];

		foreach ( $rows as $row ) {
			$placeholders[] = '( %d, %s, %s, %d, %s )';

			foreach ( $row as $value ) {
				$values[] = $value;
			}
		}

		$sql = 'INSERT INTO ' . $this->table_name()
			. ' ( post_id, post_type, facet_slug, term_id, term_slug ) VALUES '
			. implode( ', ', $placeholders );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $wpdb->prepare( $sql, $values ) );
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function get_facets_for_post_type( string $post_type ): array {
		return array_values( array_filter(
			$this->registry->get_all(),
			static fn ( array $facet ): bool => in_array( $post_type, $facet['post_types'], true )
		) );
	}

	/**
	 * @param list<array<string, mixed>> $facets
	 *
	 * @return array<string, list<array<string, mixed>>>
	 */
	private function group_by_taxonomy( array $facets ): array {
		$grouped = [];

		foreach ( $facets as $facet ) {
			$grouped[ $facet['taxonomy'] ][] = $facet;
		}

		return $grouped;
	}

	/**
	 * @return list<string>
	 */
	private function get_indexed_post_types(): array {
		$post_types = [];

		foreach ( $this->registry->get_all() as $facet ) {
			foreach ( $facet['post_types'] as $post_type ) {
				$post_types[ $post_type ] = true;
			}
		}

		return array_keys( $post_types );
	}

	/**
	 * Key for a page of results, or for the selection's total when per_page and
	 * paged are omitted.
	 *
	 * @param array<string, list<string>> $selections
	 * @param list<string>                $post_types
	 */
	private function get_cache_key( array $selections, array $post_types, int $per_page = 0, int $paged = 0 ): string {
		ksort( $selections );

		foreach ( $selections as &$terms ) {
			sort( $terms );
		}

		unset( $terms );
		sort( $post_types );

		return 'tribe_facets_' . $this->get_cache_version() . '_'
			. md5( (string) wp_json_encode( [ $selections, $post_types, $per_page, $paged ] ) );
	}

	private function get_cache_version(): int {
		return (int) get_option( self::CACHE_VERSION_OPTION, 1 );
	}

	private function bump_cache_version(): void {
		update_option( self::CACHE_VERSION_OPTION, $this->get_cache_version() + 1, false );
	}

	/**
	 * At most one bump per request.
	 *
	 * A bump invalidates every cached result set, so the second and third
	 * calls within one save (or one bulk edit) buy nothing but option writes.
	 *
	 * ponytail: a facet query running between two writes in the same request
	 * could cache against the already-bumped version. Nothing does that today
	 * — indexing happens on save, querying on render. Upgrade path is bumping
	 * on shutdown instead.
	 */
	private function bump_cache_version_once(): void {
		if ( $this->cache_bumped ) {
			return;
		}

		$this->cache_bumped = true;

		$this->bump_cache_version();
	}

	/**
	 * Deterministic order, so two row sets can be compared with ===.
	 *
	 * @param list<array{int, string, string, int, string}> $rows
	 *
	 * @return list<array{int, string, string, int, string}>
	 */
	private static function sort_rows( array $rows ): array {
		// usort reindexes, so the result is already a list.
		usort( $rows, static fn ( array $a, array $b ): int => $a <=> $b );

		return $rows;
	}

}
