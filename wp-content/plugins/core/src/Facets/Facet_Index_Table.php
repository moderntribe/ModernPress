<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

/**
 * Schema and state of the facet index table, shared by its writer and reader.
 */
class Facet_Index_Table {

	public const string TABLE = 'tribe_facet_index';

	private const string SCHEMA_VERSION_OPTION = 'tribe_facet_index_schema';
	private const string BUILT_OPTION          = 'tribe_facet_index_built';
	private const string CACHE_VERSION_OPTION  = 'tribe_facet_index_version';
	private const string PROGRESS_OPTION       = 'tribe_facet_index_rebuild';
	private const string SCHEMA_VERSION        = '1';

	public function __construct(
		private \wpdb $wpdb,
	) {
	}

	public function table_name(): string {
		return $this->wpdb->prefix . self::TABLE;
	}

	public function maybe_create(): void {
		if ( get_option( self::SCHEMA_VERSION_OPTION ) === self::SCHEMA_VERSION ) {
			return;
		}

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
		) {$this->wpdb->get_charset_collate()}" );

		update_option( self::SCHEMA_VERSION_OPTION, self::SCHEMA_VERSION, false );
	}

	public function truncate(): void {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->wpdb->query( 'TRUNCATE TABLE ' . $this->table_name() );
	}

	/**
	 * Whether the table holds a complete index. Reads must check this first:
	 * before the first build the table may not exist.
	 */
	public function is_built(): bool {
		return (bool) get_option( self::BUILT_OPTION, false );
	}

	/**
	 * Whether rows may be written: the index is built, or a rebuild is filling
	 * it. Otherwise the next rebuild covers the change anyway.
	 */
	public function is_writable(): bool {
		return $this->is_built() || null !== $this->get_progress();
	}

	/**
	 * State of a running rebuild, or null when none is running.
	 *
	 * @return array{cursor: int, indexed: int, total: int}|null
	 */
	public function get_progress(): ?array {
		$progress = get_option( self::PROGRESS_OPTION );

		return is_array( $progress ) ? $progress : null;
	}

	/**
	 * @param array{cursor: int, indexed: int, total: int}|null $progress Null clears it.
	 */
	public function set_progress( ?array $progress ): void {
		if ( null === $progress ) {
			delete_option( self::PROGRESS_OPTION );

			return;
		}

		update_option( self::PROGRESS_OPTION, $progress, false );
	}

	public function set_built( bool $built ): void {
		update_option( self::BUILT_OPTION, $built, false );
	}

	public function get_cache_version(): int {
		return (int) get_option( self::CACHE_VERSION_OPTION, 1 );
	}

	public function bump_cache_version(): void {
		update_option( self::CACHE_VERSION_OPTION, $this->get_cache_version() + 1, false );
	}

}
