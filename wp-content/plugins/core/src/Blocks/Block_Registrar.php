<?php declare(strict_types=1);

namespace Tribe\Plugin\Blocks;

class Block_Registrar {

	public const BLOCKS_DIR = 'dist/blocks/';

	public function register( string $block_name, string $blocks_dir = self::BLOCKS_DIR ): void {
		$args = [];

		/**
		 * @todo BE to look into if we can edit this in a way where we can pull tribe/ custom blocks
		 * as well instead of only ACF custom blocks.
		 *
		 * @see https://github.com/moderntribe/modernpress/pull/63#discussion_r1269701151
		 */
		if ( ! str_contains( $block_name, 'tribe' ) ) {
			$args = [
				'render_callback' => [ $this, 'render_template' ],
			];
		}

		register_block_type_from_metadata( trailingslashit( get_stylesheet_directory() ) . $blocks_dir . $block_name . '/block.json', $args );
	}

	/**
	 * @param array          $block      The block attributes.
	 * @param string         $content    The block content.
	 * @param bool           $is_preview Whether the block is being rendered for editing preview.
	 * @param int            $post_id    The current post being edited or viewed.
	 * @param \WP_Block|null $wp_block   The block instance (since WP 5.5).
	 */
	public function render_template( array $block, string $content = '', bool $is_preview = false, int $post_id = 0, ?\WP_Block $wp_block = null ): void {
		$template = $block['render_template'];
		$path     = str_replace( 'dist/blocks/', 'blocks/', $block['path'] );

		if ( ! file_exists( "$path/$template" ) ) {
			return;
		}

		include "$path/$template"; // phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.NotAbsolutePath
	}

}
