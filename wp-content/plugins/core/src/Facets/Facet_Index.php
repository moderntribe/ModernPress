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
	 * Matching post IDs for the given facet selections.
	 *
	 * @param array<string, list<string>> $selections   Facet slug => selected term slugs.
	 * @param list<string>                $post_types
	 *
	 * @return list<int>|null Null when no facet constraints are active.
	 */
	public function get_post_ids( array $selections, array $post_types ): ?array {
		$active = array_filter( $selections, static fn ( array $terms ): bool => [] !== $terms );

		if ( [] === $active ) {
			return null;
		}

		$cache_key = $this->get_cache_key( $active, $post_types );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return array_map( 'intval', $cached );
		}

		$ids = $this->query_post_ids( $active, $post_types );

		set_transient( $cache_key, $ids, self::CACHE_TTL );

		return $ids;
	}

	/**
	 * Reindex a single post.
	 */
	public function index_post( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$this->delete_post( $post_id );

		if ( 'publish' !== $post->post_status ) {
			$this->bump_cache_version();

			return;
		}

		$facets = $this->get_facets_for_post_type( $post->post_type );

		if ( [] === $facets ) {
			$this->bump_cache_version();

			return;
		}

		$rows = [];

		foreach ( $this->group_by_taxonomy( $facets ) as $taxonomy => $taxonomy_facets ) {
			$terms = wp_get_object_terms( $post_id, $taxonomy );

			if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
				continue;
			}

			foreach ( $taxonomy_facets as $facet ) {
				foreach ( $terms as $term ) {
					$rows[] = [
						$post_id,
						$post->post_type,
						$facet['slug'],
						(int) $term->term_id,
						$term->slug,
					];
				}
			}
		}

		$this->insert_rows( $rows );
		$this->bump_cache_version();
	}

	public function delete_post( int $post_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $this->table_name(), [ 'post_id' => $post_id ], [ '%d' ] );
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

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( 'TRUNCATE TABLE ' . $this->table_name() );

		if ( [] === $post_types ) {
			update_option( self::BUILT_OPTION, false, false );
			$this->bump_cache_version();

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
				$this->index_post( (int) $id );
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
		if ( ! $this->is_indexed_taxonomy( $taxonomy ) ) {
			return;
		}

		$object_ids = get_objects_in_term( $term_id, $taxonomy );

		if ( is_wp_error( $object_ids ) ) {
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
	 * @param array<string, list<string>> $selections
	 * @param list<string>                $post_types
	 *
	 * @return list<int>
	 */
	private function query_post_ids( array $selections, array $post_types ): array {
		global $wpdb;

		$clauses = [];
		$values  = [];

		foreach ( $selections as $slug => $terms ) {
			$placeholders = implode( ', ', array_fill( 0, count( $terms ), '%s' ) );
			$clauses[]    = "( facet_slug = %s AND term_slug IN ( {$placeholders} ) )";
			$values[]     = $slug;

			foreach ( $terms as $term ) {
				$values[] = $term;
			}
		}

		$where = '( ' . implode( ' OR ', $clauses ) . ' )';

		if ( [] !== $post_types ) {
			$type_placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
			$where            .= " AND post_type IN ( {$type_placeholders} )";

			foreach ( $post_types as $post_type ) {
				$values[] = $post_type;
			}
		}

		$values[] = count( $selections );

		$sql = 'SELECT post_id FROM ' . $this->table_name()
			. " WHERE {$where}"
			. ' GROUP BY post_id HAVING COUNT( DISTINCT facet_slug ) = %d';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_col( $wpdb->prepare( $sql, $values ) );

		return array_map( 'intval', $results );
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
	 * @param array<string, list<string>> $selections
	 * @param list<string>                $post_types
	 */
	private function get_cache_key( array $selections, array $post_types ): string {
		ksort( $selections );

		foreach ( $selections as &$terms ) {
			sort( $terms );
		}

		unset( $terms );
		sort( $post_types );

		return 'tribe_facets_' . $this->get_cache_version() . '_' . md5( (string) wp_json_encode( [ $selections, $post_types ] ) );
	}

	private function get_cache_version(): int {
		return (int) get_option( self::CACHE_VERSION_OPTION, 1 );
	}

	private function bump_cache_version(): void {
		update_option( self::CACHE_VERSION_OPTION, $this->get_cache_version() + 1, false );
	}

}
