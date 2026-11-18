<?php
/**
 * Context Builder.
 *
 * Orchestrates the 3-tier context system, combining block registry data
 * with site usage patterns and concrete examples while respecting token budgets.
 *
 * @package Tribe\AI
 */

namespace Tribe\AI\AI;

/**
 * Class Context_Builder
 */
class Context_Builder {
	/**
	 * Maximum tokens for Tier 1 (Registry).
	 */
	private const TIER_1_TOKEN_BUDGET = 3000;

	/**
	 * Maximum tokens for Tier 2 (Patterns).
	 */
	private const TIER_2_TOKEN_BUDGET = 1500;

	/**
	 * Maximum tokens for Tier 3 (Examples).
	 */
	private const TIER_3_TOKEN_BUDGET = 1500;

	/**
	 * Block registry analyzer.
	 *
	 * @var Block_Registry_Analyzer
	 */
	private Block_Registry_Analyzer $registry_analyzer;

	/**
	 * Site analyzer.
	 *
	 * @var Site_Analyzer
	 */
	private Site_Analyzer $site_analyzer;

	/**
	 * Constructor.
	 *
	 * @param Block_Registry_Analyzer $registry_analyzer Block registry analyzer instance.
	 * @param Site_Analyzer           $site_analyzer Site analyzer instance.
	 */
	public function __construct( Block_Registry_Analyzer $registry_analyzer, Site_Analyzer $site_analyzer ) {
		$this->registry_analyzer = $registry_analyzer;
		$this->site_analyzer     = $site_analyzer;
	}

	/**
	 * Build complete context for AI prompt.
	 *
	 * Combines registry data, usage patterns, and concrete examples
	 * into a structured 3-tier context system.
	 *
	 * @return array Structured context data with tier_1_registry, tier_2_patterns, tier_3_examples, and metadata.
	 */
	public function build_context(): array {
		// Get raw data from both analyzers.
		$registry_data = $this->registry_analyzer->analyze_registry();
		$site_data     = $this->site_analyzer->analyze_site();

		// Prioritize blocks: custom > frequently-used > core.
		$prioritized = $this->prioritize_blocks(
			$registry_data['blocks'],
			$site_data['block_usage'] ?? []
		);

		// Build each tier within budget.
		$tier_1 = $this->build_tier_1_registry( $prioritized, $registry_data );
		$tier_1 = $this->trim_to_budget( $tier_1, self::TIER_1_TOKEN_BUDGET );

		$tier_2 = $this->build_tier_2_patterns( $site_data );
		$tier_2 = $this->trim_to_budget( $tier_2, self::TIER_2_TOKEN_BUDGET );

		$tier_3 = $this->build_tier_3_examples( $site_data, $prioritized );
		$tier_3 = $this->trim_to_budget( $tier_3, self::TIER_3_TOKEN_BUDGET );

		// Count custom blocks.
		$custom_blocks = array_filter(
			$prioritized,
			fn( $block_name ) => strpos( $block_name, 'core/' ) !== 0,
			ARRAY_FILTER_USE_KEY
		);

		return [
			'tier_1_registry' => $tier_1,
			'tier_2_patterns' => $tier_2,
			'tier_3_examples' => $tier_3,
			'metadata'        => [
				'total_blocks'  => count( $registry_data['blocks'] ),
				'custom_blocks' => count( $custom_blocks ),
				'generated_at'  => time(),
			],
		];
	}

	/**
	 * Build Tier 1: Available blocks with schemas.
	 *
	 * @param array $prioritized_blocks Prioritized block list.
	 * @param array $registry_data Full registry data.
	 * @return array Registry context.
	 */
	private function build_tier_1_registry( array $prioritized_blocks, array $registry_data ): array {
		// Compress schemas to save tokens.
		$compressed_blocks = $this->registry_analyzer->compress_schemas( $prioritized_blocks );

		return [
			'blocks'           => $compressed_blocks,
			'container_blocks' => $registry_data['container_blocks'] ?? [],
			'total_available'  => count( $prioritized_blocks ),
		];
	}

	/**
	 * Build Tier 2: Usage patterns.
	 *
	 * @param array $site_data Site analysis data.
	 * @return array Pattern context.
	 */
	private function build_tier_2_patterns( array $site_data ): array {
		return [
			'usage_counts'       => $site_data['top_blocks'] ?? [],
			'attribute_patterns' => $site_data['attribute_patterns'] ?? [],
			'nesting_patterns'   => $site_data['nesting_patterns'] ?? [],
			'heading_levels'     => $site_data['patterns']['heading_levels'] ?? [],
			'custom_blocks_used' => $site_data['custom_blocks_used'] ?? [],
		];
	}

	/**
	 * Build Tier 3: Concrete examples.
	 *
	 * @param array $site_data Site analysis data.
	 * @param array $prioritized_blocks Prioritized block list.
	 * @return array Example context.
	 */
	private function build_tier_3_examples( array $site_data, array $prioritized_blocks ): array {
		$examples = $site_data['block_examples'] ?? [];

		// Prioritize custom block examples.
		$custom_examples = [];
		$core_examples   = [];

		foreach ( $examples as $block_name => $block_examples ) {
			if ( strpos( $block_name, 'core/' ) !== 0 ) {
				// Custom block - include all examples.
				$custom_examples[ $block_name ] = $block_examples;
			} else {
				// Core block - include if in top usage.
				if ( isset( $prioritized_blocks[ $block_name ] ) ) {
					$core_examples[ $block_name ] = $block_examples;
				}
			}
		}

		return [
			'custom_blocks' => $custom_examples,
			'core_blocks'   => $core_examples,
			'tone_samples'  => $site_data['tone_samples'] ?? [],
		];
	}

	/**
	 * Prioritize blocks for context inclusion.
	 *
	 * Priority order:
	 * 1. Custom blocks (non-core/*)
	 * 2. Frequently used blocks
	 * 3. Core blocks
	 *
	 * @param array $blocks All available blocks.
	 * @param array $usage_counts Block usage from site analysis.
	 * @return array Prioritized block list.
	 */
	private function prioritize_blocks( array $blocks, array $usage_counts ): array {
		$custom_blocks = [];
		$used_blocks   = [];
		$other_blocks  = [];

		foreach ( $blocks as $block_name => $block_data ) {
			if ( strpos( $block_name, 'core/' ) !== 0 ) {
				// Custom block - highest priority.
				$custom_blocks[ $block_name ] = $block_data;
			} elseif ( isset( $usage_counts[ $block_name ] ) && $usage_counts[ $block_name ] > 0 ) {
				// Used core block - medium priority.
				$used_blocks[ $block_name ] = [
					'data'  => $block_data,
					'count' => $usage_counts[ $block_name ],
				];
			} else {
				// Unused core block - lowest priority.
				$other_blocks[ $block_name ] = $block_data;
			}
		}

		// Sort used blocks by usage count descending.
		uasort(
			$used_blocks,
			function ( $a, $b ) {
				return $b['count'] <=> $a['count'];
			}
		);

		// Extract just the block data from used_blocks.
		$used_blocks = array_map(
			fn( $item ) => $item['data'],
			$used_blocks
		);

		// Combine in priority order.
		return array_merge( $custom_blocks, $used_blocks, $other_blocks );
	}

	/**
	 * Estimate token count for content.
	 *
	 * Uses rough approximation: ~3.5 characters per token for JSON/code.
	 *
	 * @param string|array $content Content to measure.
	 * @return int Estimated tokens.
	 */
	private function estimate_tokens( $content ): int {
		$text = is_array( $content ) ? wp_json_encode( $content ) : $content;
		return (int) ceil( strlen( $text ) / 3.5 );
	}

	/**
	 * Trim context to fit within budget.
	 *
	 * Intelligently reduces context size while preserving essential information.
	 * Custom blocks are never trimmed.
	 *
	 * @param array $context Context data.
	 * @param int   $budget Token budget.
	 * @return array Trimmed context.
	 */
	private function trim_to_budget( array $context, int $budget ): array {
		$current_tokens = $this->estimate_tokens( $context );

		if ( $current_tokens <= $budget ) {
			return $context;
		}

		// If we're over budget, try to reduce context intelligently.
		// For Tier 1: Keep all custom blocks, reduce core blocks.
		if ( isset( $context['blocks'] ) ) {
			$custom_blocks = [];
			$core_blocks   = [];

			foreach ( $context['blocks'] as $block_name => $block_data ) {
				if ( strpos( $block_name, 'core/' ) !== 0 ) {
					$custom_blocks[ $block_name ] = $block_data;
				} else {
					$core_blocks[ $block_name ] = $block_data;
				}
			}

			// Gradually remove core blocks until we're under budget.
			$keep_count = count( $core_blocks );
			while ( $current_tokens > $budget && $keep_count > 20 ) {
				$keep_count = (int) ( $keep_count * 0.8 );
				$reduced_core = array_slice( $core_blocks, 0, $keep_count, true );
				$test_context = $context;
				$test_context['blocks'] = array_merge( $custom_blocks, $reduced_core );
				$test_context['total_available'] = count( $test_context['blocks'] );
				$current_tokens = $this->estimate_tokens( $test_context );
			}

			$context['blocks'] = array_merge( $custom_blocks, array_slice( $core_blocks, 0, $keep_count, true ) );
			$context['total_available'] = count( $context['blocks'] );
		}

		// For Tier 2: Reduce nesting patterns if needed.
		if ( isset( $context['nesting_patterns'] ) ) {
			$current_tokens = $this->estimate_tokens( $context );
			if ( $current_tokens > $budget ) {
				// Keep only top 10 parent blocks in nesting patterns.
				$context['nesting_patterns'] = array_slice( $context['nesting_patterns'], 0, 10, true );
			}
		}

		// For Tier 3: Reduce core examples but keep all custom examples.
		if ( isset( $context['core_blocks'] ) ) {
			$current_tokens = $this->estimate_tokens( $context );
			if ( $current_tokens > $budget ) {
				// Reduce core examples to 1 per block.
				foreach ( $context['core_blocks'] as $block_name => $examples ) {
					$context['core_blocks'][ $block_name ] = array_slice( $examples, 0, 1 );
				}
			}
		}

		return $context;
	}
}
