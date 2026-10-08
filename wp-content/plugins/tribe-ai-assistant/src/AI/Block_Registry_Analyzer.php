<?php
/**
 * Block Registry Analyzer.
 *
 * Introspects WordPress's WP_Block_Type_Registry to extract comprehensive
 * block metadata including schemas, attributes, and capabilities.
 *
 * @package Tribe\AI
 */

namespace Tribe\AI\AI;

/**
 * Class Block_Registry_Analyzer
 */
class Block_Registry_Analyzer {
	/**
	 * Cache key prefix for block registry data.
	 */
	private const CACHE_KEY_PREFIX = 'tribe_ai_block_registry';

	/**
	 * Cache duration in seconds (1 hour).
	 */
	private const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Analyze all registered blocks.
	 *
	 * @return array Block registry data with 'blocks', 'container_blocks', and 'total' keys.
	 */
	public function analyze_registry(): array {
		// Check cache first.
		$cache_key = $this->get_cache_key();
		$cached    = get_transient( $cache_key );

		if ( $cached && is_array( $cached ) ) {
			return $cached;
		}

		// Get all registered blocks from WordPress.
		$registry        = \WP_Block_Type_Registry::get_instance();
		$registered      = $registry->get_all_registered();
		$blocks          = [];
		$container_blocks = [];

		foreach ( $registered as $block_name => $block_type ) {
			$block_data = $this->extract_block_data( $block_type );
			$blocks[ $block_name ] = $block_data;

			// Track container blocks (those that support inner blocks).
			if ( $this->is_container_block( $block_type ) ) {
				$container_blocks[] = $block_name;
			}
		}

		$registry_data = [
			'blocks'           => $blocks,
			'container_blocks' => $container_blocks,
			'total'            => count( $blocks ),
			'generated_at'     => time(),
		];

		// Cache the results.
		set_transient( $cache_key, $registry_data, self::CACHE_TTL );

		return $registry_data;
	}

	/**
	 * Get schema for a specific block.
	 *
	 * @param string $block_name Block name (e.g., 'core/paragraph').
	 * @return array|null Block schema or null if not found.
	 */
	public function get_block_schema( string $block_name ): ?array {
		$registry = $this->analyze_registry();

		if ( ! isset( $registry['blocks'][ $block_name ] ) ) {
			return null;
		}

		return $registry['blocks'][ $block_name ];
	}

	/**
	 * Get blocks by category.
	 *
	 * @param string $category Category slug (e.g., 'text', 'media', 'design').
	 * @return array Blocks in the specified category.
	 */
	public function get_blocks_by_category( string $category ): array {
		$registry = $this->analyze_registry();
		$blocks   = [];

		foreach ( $registry['blocks'] as $block_name => $block_data ) {
			if ( isset( $block_data['category'] ) && $block_data['category'] === $category ) {
				$blocks[ $block_name ] = $block_data;
			}
		}

		return $blocks;
	}

	/**
	 * Get blocks that support inner blocks.
	 *
	 * @return array Container blocks with allowed children information.
	 */
	public function get_container_blocks(): array {
		$registry = $this->analyze_registry();
		$containers = [];

		foreach ( $registry['container_blocks'] as $block_name ) {
			if ( isset( $registry['blocks'][ $block_name ] ) ) {
				$containers[ $block_name ] = $registry['blocks'][ $block_name ];
			}
		}

		return $containers;
	}

	/**
	 * Extract compressed schema for AI context.
	 *
	 * Reduces token usage while preserving essential information.
	 *
	 * @param array $blocks Block list to compress.
	 * @return array Compressed schemas.
	 */
	public function compress_schemas( array $blocks ): array {
		$compressed = [];

		foreach ( $blocks as $block_name => $block_data ) {
			$compressed[ $block_name ] = [
				'name'     => $block_name,
				'title'    => $block_data['title'] ?? '',
				'category' => $block_data['category'] ?? '',
				'attrs'    => array_keys( $block_data['attributes'] ?? [] ),
			];

			// Include parent constraint if exists.
			if ( ! empty( $block_data['parent'] ) ) {
				$compressed[ $block_name ]['parent'] = $block_data['parent'];
			}

			// Include support flags (simplified).
			if ( ! empty( $block_data['supports'] ) ) {
				$compressed[ $block_name ]['supports'] = array_keys( $block_data['supports'] );
			}

			// Mark if it's a container block.
			if ( ! empty( $block_data['is_container'] ) ) {
				$compressed[ $block_name ]['is_container'] = true;
			}
		}

		return $compressed;
	}

	/**
	 * Invalidate registry cache.
	 *
	 * Call this when plugins/themes are activated, deactivated, or updated.
	 *
	 * @return void
	 */
	public function invalidate_cache(): void {
		$cache_key = $this->get_cache_key();
		delete_transient( $cache_key );
	}

	/**
	 * Check if registry cache exists.
	 *
	 * @return bool True if cache exists.
	 */
	public function is_cached(): bool {
		$cache_key = $this->get_cache_key();
		return false !== get_transient( $cache_key );
	}

	/**
	 * Extract block data from WP_Block_Type.
	 *
	 * @param \WP_Block_Type $block_type Block type object.
	 * @return array Normalized block data.
	 */
	private function extract_block_data( \WP_Block_Type $block_type ): array {
		return [
			'title'        => $block_type->title ?? '',
			'category'     => $block_type->category ?? '',
			'description'  => $block_type->description ?? '',
			'parent'       => $block_type->parent ?? null,
			'attributes'   => $this->extract_attributes( $block_type->attributes ?? [] ),
			'supports'     => $block_type->supports ?? [],
			'is_container' => $this->is_container_block( $block_type ),
			'icon'         => $block_type->icon ?? '',
		];
	}

	/**
	 * Extract and normalize block attributes.
	 *
	 * @param array $attributes Raw attributes from block type.
	 * @return array Normalized attributes.
	 */
	private function extract_attributes( array $attributes ): array {
		$normalized = [];

		foreach ( $attributes as $attr_name => $attr_config ) {
			$normalized[ $attr_name ] = [
				'type'    => $attr_config['type'] ?? 'string',
				'default' => $attr_config['default'] ?? null,
			];

			// Include enum values if present.
			if ( isset( $attr_config['enum'] ) ) {
				$normalized[ $attr_name ]['enum'] = $attr_config['enum'];
			}
		}

		return $normalized;
	}

	/**
	 * Check if a block supports inner blocks (is a container).
	 *
	 * @param \WP_Block_Type $block_type Block type object.
	 * @return bool True if block supports inner blocks.
	 */
	private function is_container_block( \WP_Block_Type $block_type ): bool {
		// Common container blocks check render_callback or known patterns.
		$container_patterns = [
			'core/group',
			'core/column',
			'core/columns',
			'core/cover',
			'core/buttons',
			'core/navigation',
			'core/navigation-submenu',
			'core/post-content',
			'core/query',
			'core/post-template',
		];

		// Check if it's a known container.
		if ( in_array( $block_type->name, $container_patterns, true ) ) {
			return true;
		}

		// Check if uses_context includes innerBlocks or similar patterns.
		if ( ! empty( $block_type->uses_context ) ) {
			foreach ( $block_type->uses_context as $context ) {
				if ( strpos( $context, 'inner' ) !== false ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Generate cache key based on active plugins and theme.
	 *
	 * This ensures the cache is invalidated when plugins/themes change.
	 *
	 * @return string Cache key.
	 */
	private function get_cache_key(): string {
		$active_plugins = get_option( 'active_plugins', [] );
		$stylesheet     = get_stylesheet();
		$template       = get_template();
		$theme_version  = wp_get_theme()->get( 'Version' );

		$hash = md5(
			implode( ',', $active_plugins ) .
			$stylesheet .
			$template .
			$theme_version
		);

		return self::CACHE_KEY_PREFIX . '_' . $hash;
	}
}
