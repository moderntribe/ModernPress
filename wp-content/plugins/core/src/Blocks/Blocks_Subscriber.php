<?php declare(strict_types=1);

namespace Tribe\Plugin\Blocks;

use Tribe\Plugin\Blocks\Bindings\Binding_Registrar;
use Tribe\Plugin\Blocks\Filters\Contracts\Filter_Factory;
use Tribe\Plugin\Blocks\Helpers\Block_Animation_Attributes;
use Tribe\Plugin\Blocks\Patterns\Pattern_Category;
use Tribe\Plugin\Blocks\Patterns\Pattern_Registrar;
use Tribe\Plugin\Core\Abstract_Subscriber;
use Tribe\Plugin\Theme_Config\Theme_Support;

class Blocks_Subscriber extends Abstract_Subscriber {

	public function register(): void {

		add_action( 'init', function (): void {
			// Register blocks.
			foreach ( $this->container->get( Blocks_Definer::TYPES ) as $type ) {
				$this->container->get( Block_Registrar::class )->register( $type );
			}

			// Register block variations (called "Block Styles")
			foreach ( $this->container->get( Blocks_Definer::EXTENDED ) as $block ) {
				$block->register_core_block_variations();
			}

			// Register block pattern categories.
			$this->container->get( Pattern_Category::class )->register_pattern_categories();

			// Register block patterns.
			foreach ( $this->container->get( Blocks_Definer::PATTERNS ) as $pattern ) {
				$this->container->get( Pattern_Registrar::class )->register( $pattern );
			}

			// Register block bindings.
			foreach ( $this->container->get( Blocks_Definer::BINDINGS ) as $binding ) {
				$this->container->get( Binding_Registrar::class )->register( $binding );
			}
		}, 10, 0 );

		/**
		 * Enqueue styles on the public site for WP Core blocks
		 */
		add_action( 'wp_enqueue_scripts', function (): void {
			foreach ( $this->container->get( Blocks_Definer::EXTENDED ) as $block ) {
				// core block public styles
				$block->enqueue_core_block_public_styles();
			}
		}, 10, 0 );

		/**
		 * Enqueue block editor-only scripts
		 *
		 * Core blocks shouldn't ever have FE scripts and should only include
		 * editor scripts in order to override default block functionality
		 */
		add_action( 'enqueue_block_assets', function (): void {
			foreach ( $this->container->get( Blocks_Definer::EXTENDED ) as $block ) {
				// core block public styles
				$block->enqueue_core_block_public_styles();
				// core block editor-only styles
				$block->enqueue_core_block_editor_styles();
				// core block editor-only scripts
				$block->enqueue_core_block_editor_scripts();
			}
		}, 10, 0 );

		/**
		 * Register block categories.
		 */
		add_filter( 'block_categories_all', function ( array $categories ): array {
			return $this->container->get( Block_Category::class )->custom_block_category( $categories );
		} );

		/**
		 * Filter block content using the render_block filter
		 */
		add_filter( 'render_block', function ( string $block_content, array $parsed_block, object $block ): string {
			$filter = $this->container->get( Filter_Factory::class )->make( $parsed_block );

			return $filter ? $filter->filter_block_content( $block_content, $parsed_block, $block ) : $block_content;
		}, 10, 3 );

		/**
		 * Add support for block animation attributes in dynamic blocks.
		 */
		add_action( 'wp_loaded', function (): void {
			$this->container->get( Block_Animation_Attributes::class )->register_animation_attributes();
		}, 100, 0 );

		/**
		 * Disable default WP block patterns.
		 */
		add_action( 'after_setup_theme', function (): void {
			$this->container->get( Theme_Support::class )->disable_block_patterns();
		}, 10, 0 );

		/**
		 * Disable the WordPress patterns directory.
		 */
		add_filter( 'should_load_remote_block_patterns', '__return_false' );

		/**
		 * Handle Block Editor Settings
		 */
		add_filter( 'block_editor_settings_all', function ( array $settings ): array {
			// Disable openverse media category.
			$settings = $this->container->get( Theme_Support::class )->disable_openverse_media_category( $settings );

			// Disable the WP block editor font library.
			$settings = $this->container->get( Theme_Support::class )->disable_font_library_ui( $settings );

			return $settings;
		} );

		/**
		 * Allows all roles to publish content containing the Tribe Tabs block.
		 *
		 *  These attributes are stripped on save if the user doesn't have the `unfiltered_html` capability,
		 *  which admins & editors don't have by default on multisite.
		 *
		 * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/kses.php#L892
		 */
		add_filter( 'wp_kses_allowed_html', static function ( array $tags, string $context ): array {
			if ( $context !== 'post' ) {
				return $tags;
			}

			// Tribe Tabs Block needs these attributes to be allowed in the HTML.
			$tags['button']['tabindex']      = true;
			$tags['button']['aria-selected'] = true;
			$tags['div']['tabindex']         = true;

			return $tags;
		}, 10, 2 );

		/**
		 * Allow all roles to publish content containing `cubic-bezier()` timing function values in inline CSS.
		 * Example: `<div style="--tribe-animation-easing:cubic-bezier(0.4, 0, 0.2, 1);">`
		 *
		 * The Tribe Animation Library uses cubic-bezier functions to control the timing of animations.
		 *
		 * These values are stripped on save if the user doesn't have the `unfiltered_html` capability,
		 * which admins & editors don't have by default on multisite.
		 *
		 * @link https://github.com/WordPress/WordPress/blob/master/wp-includes/kses.php#L2703
		 */
		add_filter( 'safecss_filter_attr_allow_css', static function ( bool $allow_css, string $css_test_string ): bool {
			// Allow cubic-bezier timing function values in inline CSS
			if ( preg_match( '/cubic-bezier\(([\d.]+,\s*){3}[\d.]+\)/', $css_test_string ) ) {
				return true;
			}

			return $allow_css;
		}, 10, 2 );
	}

}
