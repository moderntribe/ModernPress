<?php declare(strict_types=1);

namespace PatternSync\Patterns;

class Pattern_Registry_Service {

	/** User-created pattern name prefix (wp_block post type). */
	public const WP_BLOCK_PREFIX = 'wp_block/';

	private const OPTION_STORED = 'pattern_sync_stored_patterns';

	private const NAME_PREFIX = 'pattern-sync/';

	/**
	 * Register all stored patterns with the block pattern registry.
	 * Call on init so patterns appear in the editor.
	 */
	public function register_stored_patterns(): void {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		$stored = $this->get_stored();
		foreach ( $stored as $name => $properties ) {
			$props = $this->normalize_properties_for_register( $properties );
			register_block_pattern( $name, $props );
		}
	}

	/**
	 * Get all patterns: code-registered (locked), user-created (wp_block), and plugin-stored, in a consistent schema.
	 * Includes 'syncable' (true = user-created or synced; false = code/locked) and 'origin' (code|user|pattern-sync).
	 *
	 * @return array<int, array{name: string, title: string, content: string, description: string, categories: array, keywords: array, viewport_width: int, inserter: bool, block_types: array, post_types: array, source: string, syncable: bool, origin: string}>
	 */
	public function list_all(): array {
		$from_registry = $this->list_from_registry();
		$from_wp_block = $this->list_from_wp_block();
		$from_stored   = $this->list_from_stored();

		return array_values( array_merge( $from_registry, $from_wp_block, $from_stored ) );
	}

	/**
	 * Store a pattern definition (from REST POST). Overwrites by name.
	 *
	 * @param array<string, mixed> $pattern_def Pattern with name, title, content, description, categories, keywords, etc.
	 */
	public function store( array $pattern_def ): void {
		$name = $this->get_pattern_name( $pattern_def );
		if ( $name === '' ) {
			return;
		}

		$stored          = $this->get_stored();
		$stored[ $name ] = $pattern_def;
		update_option( self::OPTION_STORED, $stored, true );
	}

	/**
	 * Get stored pattern definitions (raw, keyed by name).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	public function get_stored(): array {
		$value = get_option( self::OPTION_STORED, [] );

		return is_array( $value ) ? $value : [];
	}

	/**
	 * Get a single pattern by name (from registry or stored).
	 *
	 * @return array<string, mixed>|null
	 */
	public function get_by_name( string $name ): ?array {
		$all = $this->list_all();
		foreach ( $all as $pattern ) {
			if ( isset( $pattern['name'] ) && $pattern['name'] === $name ) {
				return $pattern;
			}
		}

		return null;
	}

	public function get_name_prefix(): string {
		return self::NAME_PREFIX;
	}

	/**
	 * List patterns from WP_Block_Patterns_Registry (code-registered only; locked, not syncable).
	 *
	 * @return array<int, array{name: string, title: string, content: string, description: string, categories: array, keywords: array, viewport_width: int, inserter: bool, block_types: array, post_types: array, source: string, syncable: bool, origin: string}>
	 */
	private function list_from_registry(): array {
		if ( ! class_exists( 'WP_Block_Patterns_Registry' ) ) {
			return [];
		}

		$registry = \WP_Block_Patterns_Registry::get_instance();
		if ( ! method_exists( $registry, 'get_all_registered' ) ) {
			return [];
		}

		$registered     = $registry->get_all_registered();
		$wp_block_names = $this->get_wp_block_pattern_names();
		$out            = [];
		foreach ( $registered as $pattern ) {
			$name = $pattern['name'] ?? '';
			if ( $name === '' ) {
				continue;
			}
			if ( isset( $wp_block_names[ $name ] ) ) {
				continue;
			}
			$out[] = $this->normalize_to_schema( $pattern, $this->infer_source( $name ), false, 'code' );
		}

		return $out;
	}

	/**
	 * List user-created patterns from wp_block post type (syncable).
	 *
	 * @return array<int, array{name: string, title: string, content: string, description: string, categories: array, keywords: array, viewport_width: int, inserter: bool, block_types: array, post_types: array, source: string, syncable: bool, origin: string}>
	 */
	private function list_from_wp_block(): array {
		$posts = get_posts( [
			'post_type'      => 'wp_block',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
		$out   = [];
		foreach ( $posts as $post ) {
			$name  = self::WP_BLOCK_PREFIX . $post->ID;
			$out[] = $this->normalize_to_schema( [
				'name'        => $name,
				'title'       => $post->post_title ?: __( '(Untitled)', 'pattern-sync' ),
				'content'     => $post->post_content,
				'description' => '',
				'categories'  => [],
				'keywords'    => [],
			], 'user', true, 'user' );
		}

		return $out;
	}

	/**
	 * Names of patterns that are backed by wp_block (so we exclude them from code list).
	 *
	 * @return array<string, true>
	 */
	private function get_wp_block_pattern_names(): array {
		$posts = get_posts( [
			'post_type'      => 'wp_block',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		] );
		$map   = [];
		foreach ( $posts as $id ) {
			$map[ self::WP_BLOCK_PREFIX . $id ] = true;
		}

		return $map;
	}

	/**
	 * List patterns from our stored option (synced from other sites; syncable).
	 *
	 * @return array<int, array{name: string, title: string, content: string, description: string, categories: array, keywords: array, viewport_width: int, inserter: bool, block_types: array, post_types: array, source: string, syncable: bool, origin: string}>
	 */
	private function list_from_stored(): array {
		$stored = $this->get_stored();
		$out    = [];
		foreach ( $stored as $name => $props ) {
			$out[] = $this->normalize_to_schema( array_merge( [ 'name' => $name ], $props ), 'pattern-sync', true, 'pattern-sync' );
		}

		return $out;
	}

	/**
	 * Normalize a pattern array to our REST schema (snake_case).
	 *
	 * @param array<string, mixed> $pattern   Raw pattern from registry, wp_block, or stored.
	 * @param string               $source    Source label: core, theme, plugin, pattern-sync, user.
	 * @param bool                 $syncable  Whether the pattern can be synced (user-created or synced).
	 * @param string               $origin    Origin: 'code' (locked), 'user' (wp_block), 'pattern-sync'.
	 *
	 * @return array{name: string, title: string, content: string, description: string, categories: array, keywords: array, viewport_width: int, inserter: bool, block_types: array, post_types: array, source: string, syncable: bool, origin: string}
	 */
	private function normalize_to_schema( array $pattern, string $source, bool $syncable = false, string $origin = 'code' ): array {
		return [
			'name'           => (string) ( $pattern['name'] ?? '' ),
			'title'          => (string) ( $pattern['title'] ?? '' ),
			'content'        => (string) ( $pattern['content'] ?? '' ),
			'description'    => (string) ( $pattern['description'] ?? '' ),
			'categories'     => isset( $pattern['categories'] ) && is_array( $pattern['categories'] ) ? $pattern['categories'] : [],
			'keywords'       => isset( $pattern['keywords'] ) && is_array( $pattern['keywords'] ) ? $pattern['keywords'] : [],
			'viewport_width' => (int) ( $pattern['viewportWidth'] ?? $pattern['viewport_width'] ?? 0 ),
			'inserter'       => (bool) ( $pattern['inserter'] ?? true ),
			'block_types'    => isset( $pattern['blockTypes'] ) && is_array( $pattern['blockTypes'] ) ? $pattern['blockTypes'] : ( isset( $pattern['block_types'] ) && is_array( $pattern['block_types'] ) ? $pattern['block_types'] : [] ),
			'post_types'     => isset( $pattern['postTypes'] ) && is_array( $pattern['postTypes'] ) ? $pattern['postTypes'] : ( isset( $pattern['post_types'] ) && is_array( $pattern['post_types'] ) ? $pattern['post_types'] : [] ),
			'source'         => $source,
			'syncable'       => $syncable,
			'origin'         => $origin,
		];
	}

	/**
	 * Convert schema (snake_case) to properties for register_block_pattern (camelCase where needed).
	 *
	 * @param array<string, mixed> $properties
	 *
	 * @return array<string, mixed>
	 */
	private function normalize_properties_for_register( array $properties ): array {
		$out      = [
			'title'       => (string) ( $properties['title'] ?? '' ),
			'content'     => (string) ( $properties['content'] ?? '' ),
			'description' => (string) ( $properties['description'] ?? '' ),
			'categories'  => isset( $properties['categories'] ) && is_array( $properties['categories'] ) ? $properties['categories'] : [],
			'keywords'    => isset( $properties['keywords'] ) && is_array( $properties['keywords'] ) ? $properties['keywords'] : [],
			'inserter'    => (bool) ( $properties['inserter'] ?? true ),
		];
		$viewport = $properties['viewportWidth'] ?? $properties['viewport_width'] ?? 0;
		if ( (int) $viewport > 0 ) {
			$out['viewportWidth'] = (int) $viewport;
		}
		$block_types = $properties['blockTypes'] ?? $properties['block_types'] ?? null;
		if ( $block_types !== null && $block_types !== '' && is_array( $block_types ) && $block_types !== [] ) {
			$out['blockTypes'] = $block_types;
		}
		$post_types = $properties['postTypes'] ?? $properties['post_types'] ?? null;
		if ( $post_types !== null && $post_types !== '' && is_array( $post_types ) && $post_types !== [] ) {
			$out['postTypes'] = $post_types;
		}

		return array_filter( $out, static fn ( $v ) => $v !== '' || is_array( $v ) );
	}

	private function get_pattern_name( array $pattern_def ): string {
		$name = $pattern_def['name'] ?? '';
		if ( $name !== '' ) {
			return $name;
		}
		$title = $pattern_def['title'] ?? '';
		if ( $title !== '' ) {
			$slug = sanitize_title( $title );

			return self::NAME_PREFIX . $slug;
		}

		return '';
	}

	private function infer_source( string $name ): string {
		if ( str_starts_with( $name, self::NAME_PREFIX ) ) {
			return 'pattern-sync';
		}
		if ( str_starts_with( $name, 'core/' ) ) {
			return 'core';
		}

		return 'plugin';
	}

}
