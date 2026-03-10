<?php declare(strict_types=1);

namespace Tribe\Theme\blocks\core\heading;

use Tribe\Plugin\Blocks\Block_Base;

class Heading extends Block_Base {

	public function __construct( string $assets_folder = 'dist/assets/' ) {
		parent::__construct( $assets_folder );

		add_filter( 'render_block', [ $this, 'render_heading_as_div' ], 10, 2 );
	}

	public function get_block_name(): string {
		return 'core/heading';
	}

	/**
	 * Transform heading tag to span if useSpanTag attribute is true
	 *
	 * @param string               $block_content The block content.
	 * @param array<string, mixed> $block         The full block, including name and attributes.
	 *
	 * @return string Modified block content.
	 */
	public function render_heading_as_div( string $block_content, array $block ): string {
		// Only process core/heading blocks
		if ( 'core/heading' !== $block['blockName'] ) {
			return $block_content;
		}

		// Get attributes with proper type checking
		$attrs = $block['attrs'] ?? [];
		if ( ! is_array( $attrs ) ) {
			return $block_content;
		}

		// Check if useSpanTag attribute is set to true
		if ( empty( $attrs['useDivTag'] ) ) {
			return $block_content;
		}

		// Replace the heading tag (opening and closing) with span
		// Matches any h1-h6 level since we transform based on rendered output
		$result = preg_replace( '/<(\/?)h[1-6]([^>]*)>/', '<$1div$2>', $block_content );

		// Handle preg_replace returning null on error
		return $result ?? $block_content;
	}

}
