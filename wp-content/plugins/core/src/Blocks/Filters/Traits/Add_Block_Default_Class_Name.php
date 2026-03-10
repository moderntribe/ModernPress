<?php declare(strict_types=1);

namespace Tribe\Plugin\Blocks\Filters\Traits;

trait Add_Block_Default_Class_Name {

	/**
	 * Ensures there's a `wp-block-<block name>` CSS class rendered for the specific blocks mentioned above.
	 *
	 * This filter is a polyfill for core blocks that don't render their own CSS class name.
	 * Without the class name on the block, CSS styling becomes much more problematic.
	 *
	 * Eventually WP Core should handle this for us and we can remove this filter.
	 *
	 * Related outstanding issues in Gutenberg:
	 *  * https://github.com/WordPress/gutenberg/pull/42269
	 *  * https://github.com/WordPress/gutenberg/issues/50486
	 *  * https://github.com/WordPress/gutenberg/pull/47282
	 *
	 * @param string $block_content Rendered block content.
	 * @param array  $parsed_block  The block being rendered.
	 * @param object $block         Block object.
	 *
	 * @return string                Updated block content.
	 */
	public function add_class_name( string $block_content, array $parsed_block, object $block ): string {
		if ( ! $block_content ) {
			return $block_content;
		}

		if ( ! $block->block_type || $block->block_type->is_dynamic() ) {
			return $block_content;
		}

		$tags = new \WP_HTML_Tag_Processor( $block_content );
		if ( $tags->next_tag() ) {
			$tags->add_class( wp_get_block_default_classname( $block->name ) );
		}

		return $tags->get_updated_html();
	}

}
