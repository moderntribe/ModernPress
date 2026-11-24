<?php
/**
 * Site Analyzer.
 *
 * @package Tribe\AI
 */

namespace Tribe\AI\AI;

/**
 * Class Site_Analyzer
 */
class Site_Analyzer {
	/**
	 * Analyze site content and cache results.
	 *
	 * Enhanced to include:
	 * - Block examples for Tier 3
	 * - Attribute patterns for Tier 2
	 * - Nesting patterns for Tier 2
	 *
	 * @return array Site context data.
	 */
	public function analyze_site(): array {
		// Check cache first.
		$cached = get_transient( 'tribe_ai_site_context' );
		if ( $cached && is_array( $cached ) ) {
			return $cached;
		}

		// Fetch recent posts/pages ONCE and reuse for all analysis.
		$posts = $this->fetch_content_posts();

		$block_usage      = [];
		$content_samples  = [];
		$total_blocks     = 0;
		$heading_levels   = [];
		$block_examples   = [];
		$nesting_patterns = [];

		// Single pass through all posts for efficiency.
		foreach ( $posts as $post ) {
			$blocks = parse_blocks( $post->post_content );

			// Count blocks and track heading levels.
			$this->count_blocks( $blocks, $block_usage, $total_blocks, $heading_levels );

			// Collect block examples (up to 3 per block type).
			$this->collect_examples( $blocks, $block_examples, 3 );

			// Track nesting patterns.
			$this->track_nesting( $blocks, $nesting_patterns );

			// Extract content sample for tone.
			$sample = $this->extract_sample( $blocks );
			if ( ! empty( $sample ) ) {
				$content_samples[] = $sample;
			}
		}

		// Analyze attribute patterns from collected examples.
		$attribute_patterns = $this->analyze_attribute_patterns( $block_examples );

		$context = [
			// Original fields
			'top_blocks'      => $this->get_top_blocks( $block_usage, 20 ),
			'block_usage'     => $block_usage,
			'tone_samples'    => array_slice( $content_samples, 0, 5 ),
			'patterns'        => [
				'avg_block_count'  => count( $posts ) > 0 ? round( $total_blocks / count( $posts ), 2 ) : 0,
				'heading_levels'   => $heading_levels,
				'total_posts'      => count( $posts ),
			],

			// New fields for enhanced context
			'block_examples'      => $block_examples,
			'attribute_patterns'  => $attribute_patterns,
			'nesting_patterns'    => $nesting_patterns,
			'custom_blocks_used'  => $this->get_custom_blocks_used( $block_usage ),

			'generated_at'    => time(),
		];

		// Cache for 24 hours.
		set_transient( 'tribe_ai_site_context', $context, DAY_IN_SECONDS );

		return $context;
	}

	/**
	 * Fetch content posts for analysis.
	 *
	 * @return array Array of WP_Post objects.
	 */
	private function fetch_content_posts(): array {
		return get_posts(
			[
				'post_type'      => [ 'post', 'page' ],
				'posts_per_page' => 100,
				'post_status'    => 'publish',
				'orderby'        => 'modified',
				'order'          => 'DESC',
			]
		);
	}

	/**
	 * Count blocks recursively.
	 *
	 * @param array $blocks Block array.
	 * @param array &$counter Block counter reference.
	 * @param int   &$total Total blocks reference.
	 * @param array &$heading_levels Heading levels reference.
	 */
	private function count_blocks( array $blocks, array &$counter, int &$total, array &$heading_levels ): void {
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) ) {
				$block_name = $block['blockName'];
				$counter[ $block_name ] = ( $counter[ $block_name ] ?? 0 ) + 1;
				$total++;

				// Track heading levels.
				if ( 'core/heading' === $block_name && isset( $block['attrs']['level'] ) ) {
					$level = $block['attrs']['level'];
					$heading_levels[ $level ] = ( $heading_levels[ $level ] ?? 0 ) + 1;
				}
			}

			// Recurse for inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->count_blocks( $block['innerBlocks'], $counter, $total, $heading_levels );
			}
		}
	}

	/**
	 * Extract content sample from blocks.
	 *
	 * @param array $blocks Block array.
	 * @return string Content sample.
	 */
	private function extract_sample( array $blocks ): string {
		foreach ( $blocks as $block ) {
			if ( 'core/paragraph' === $block['blockName'] && ! empty( $block['innerHTML'] ) ) {
				$text = wp_strip_all_tags( $block['innerHTML'] );
				$text = trim( $text );
				
				// Only return non-empty paragraphs with reasonable length.
				if ( strlen( $text ) > 20 ) {
					return substr( $text, 0, 500 );
				}
			}

			// Recurse for inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$sample = $this->extract_sample( $block['innerBlocks'] );
				if ( ! empty( $sample ) ) {
					return $sample;
				}
			}
		}

		return '';
	}

	/**
	 * Get top N blocks by usage count.
	 *
	 * @param array $block_usage Block usage counts.
	 * @param int   $limit Number of blocks to return.
	 * @return array Top blocks.
	 */
	private function get_top_blocks( array $block_usage, int $limit ): array {
		// Sort by count descending.
		arsort( $block_usage );

		// Return top N.
		return array_slice( $block_usage, 0, $limit, true );
	}

	/**
	 * Extract block examples with full markup.
	 *
	 * Prioritizes blocks from recently published and high-quality content.
	 *
	 * @param int $limit Maximum examples per block type.
	 * @return array Block examples keyed by block name.
	 */
	public function extract_block_examples( int $limit = 3 ): array {
		$examples = [];
		$posts    = $this->fetch_content_posts();

		foreach ( $posts as $post ) {
			$blocks = parse_blocks( $post->post_content );
			$this->collect_examples( $blocks, $examples, $limit );
		}

		return $examples;
	}

	/**
	 * Recursively collect block examples.
	 *
	 * @param array $blocks Block array from parse_blocks().
	 * @param array &$examples Examples array to populate (passed by reference).
	 * @param int   $limit Maximum examples per block type.
	 * @return void
	 */
	private function collect_examples( array $blocks, array &$examples, int $limit ): void {
		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			$name = $block['blockName'];

			// Initialize if needed.
			if ( ! isset( $examples[ $name ] ) ) {
				$examples[ $name ] = [];
			}

			// Add example if under limit.
			if ( count( $examples[ $name ] ) < $limit ) {
				$examples[ $name ][] = [
					'attrs'      => $block['attrs'] ?? [],
					'markup'     => serialize_block( $block ),
					'has_inner'  => ! empty( $block['innerBlocks'] ),
					'inner_html' => $block['innerHTML'] ?? '',
				];
			}

			// Recurse into inner blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->collect_examples( $block['innerBlocks'], $examples, $limit );
			}
		}
	}

	/**
	 * Analyze attribute patterns across content.
	 *
	 * Identifies common attribute combinations for each block type.
	 *
	 * @param array $block_examples Block examples from extract_block_examples().
	 * @return array Attribute usage patterns keyed by block name.
	 */
	public function analyze_attribute_patterns( array $block_examples ): array {
		$patterns = [];

		foreach ( $block_examples as $block_name => $examples ) {
			$attr_sets = [];

			foreach ( $examples as $example ) {
				$attrs = $example['attrs'] ?? [];
				if ( empty( $attrs ) ) {
					continue;
				}

				$attr_keys = array_keys( $attrs );
				sort( $attr_keys );
				$key = implode( ',', $attr_keys );

				$attr_sets[ $key ] = ( $attr_sets[ $key ] ?? 0 ) + 1;
			}

			// Get most common attribute set.
			if ( ! empty( $attr_sets ) ) {
				arsort( $attr_sets );
				$patterns[ $block_name ] = [
					'common_attrs' => explode( ',', array_key_first( $attr_sets ) ),
					'variations'   => count( $attr_sets ),
				];
			}
		}

		return $patterns;
	}

	/**
	 * Analyze block nesting patterns.
	 *
	 * Tracks parent-child relationships to understand common block structures.
	 *
	 * @return array Nesting pattern data keyed by parent block.
	 */
	public function analyze_nesting_patterns(): array {
		$nesting = [];
		$posts   = $this->fetch_content_posts();

		foreach ( $posts as $post ) {
			$blocks = parse_blocks( $post->post_content );
			$this->track_nesting( $blocks, $nesting );
		}

		return $nesting;
	}

	/**
	 * Recursively track nesting patterns.
	 *
	 * @param array       $blocks Block array.
	 * @param array       &$nesting Nesting patterns array (passed by reference).
	 * @param string|null $parent Parent block name.
	 * @return void
	 */
	private function track_nesting( array $blocks, array &$nesting, ?string $parent = null ): void {
		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}

			$name = $block['blockName'];

			// Track parent-child relationship.
			if ( $parent ) {
				if ( ! isset( $nesting[ $parent ] ) ) {
					$nesting[ $parent ] = [];
				}
				$nesting[ $parent ][ $name ] = ( $nesting[ $parent ][ $name ] ?? 0 ) + 1;
			}

			// Recurse with current block as parent.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->track_nesting( $block['innerBlocks'], $nesting, $name );
			}
		}
	}

	/**
	 * Get custom blocks (non-core) that are used in content.
	 *
	 * @param array $block_usage Block usage counts.
	 * @return array Custom block names with usage counts.
	 */
	private function get_custom_blocks_used( array $block_usage ): array {
		$custom_blocks = [];

		foreach ( $block_usage as $block_name => $count ) {
			// Custom blocks don't start with 'core/'.
			if ( strpos( $block_name, 'core/' ) !== 0 ) {
				$custom_blocks[ $block_name ] = $count;
			}
		}

		return $custom_blocks;
	}

	/**
	 * Get high-performing content blocks.
	 *
	 * Prioritizes blocks from:
	 * - Recently published content
	 * - Pages with high engagement (if metrics available)
	 * - Featured/sticky posts
	 *
	 * @return array High-quality block examples.
	 */
	public function get_high_quality_examples(): array {
		$high_quality_posts = get_posts(
			[
				'post_type'      => [ 'post', 'page' ],
				'posts_per_page' => 20,
				'post_status'    => 'publish',
				'meta_query'     => [
					'relation' => 'OR',
					[
						'key'   => '_featured',
						'value' => '1',
					],
				],
				'orderby'        => 'modified',
				'order'          => 'DESC',
			]
		);

		// Also get sticky posts (only if there are any).
		$sticky_ids   = get_option( 'sticky_posts', [] );
		$sticky_posts = [];

		if ( ! empty( $sticky_ids ) ) {
			$sticky_posts = get_posts(
				[
					'post_type'      => 'post',
					'posts_per_page' => 10,
					'post_status'    => 'publish',
					'post__in'       => $sticky_ids,
				]
			);
		}

		$all_quality_posts = array_merge( $high_quality_posts, $sticky_posts );
		$examples          = [];

		foreach ( $all_quality_posts as $post ) {
			$blocks = parse_blocks( $post->post_content );
			$this->collect_examples( $blocks, $examples, 2 );
		}

		return $examples;
	}
}
