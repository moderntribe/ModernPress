<?php declare(strict_types=1);

namespace Tribe\Plugin\Blocks;

use Tribe\Plugin\Assets\Traits\Assets;

abstract class Block_Base {

	use Assets;

	protected string $assets_path;
	protected string $assets_path_uri;

	abstract public function get_block_name(): string;

	public function __construct( string $assets_folder = 'dist/assets/' ) {
		$this->assets_path     = trailingslashit( get_stylesheet_directory() ) . $assets_folder;
		$this->assets_path_uri = trailingslashit( get_stylesheet_directory_uri() ) . $assets_folder;
	}

	public function get_block_handle(): string {
		return sanitize_title( $this->get_block_name() );
	}

	public function get_block_path(): string {
		return $this->get_block_name();
	}

	public function get_block_style_handle(): string {
		return str_replace( 'core/', 'wp-block-', $this->get_block_name() );
	}

	/**
	 * Block styles to be defined in extending class
	 */
	public function get_block_styles(): array {
		return [];
	}

	/**
	 * Block dependencies to be defined in extending class
	 */
	public function get_block_dependencies(): array {
		return [];
	}

	/**
	 * Allows registration of additional block variations (called "Block Styles")
	 * Not to be confused with CSS Styles
	 */
	public function register_core_block_variations(): void {
		if ( ! function_exists( 'register_block_style' ) ) {
			return;
		}

		foreach ( $this->get_block_styles() as $name => $label ) {
			register_block_style( $this->get_block_name(), [
				'name'  => $name,
				'label' => $label,
			] );
		}
	}

	/**
	 * Enqueue core block styles
	 *
	 * On the public site, CSS is inlined onto WordPress's registered style handle for that block
	 * (e.g. `wp-block-paragraph`) so it only prints when that block's stylesheet is enqueued.
	 *
	 * In wp-admin (block / site editor), styles are loaded as `<link>` tags so they work in the
	 * editor shell and iframed canvas without relying on core's per-block queue on the front end.
	 *
	 * On the front end, if the core style handle is not registered, styles are skipped and
	 * `_doing_it_wrong()` is triggered so the misconfiguration is visible during development.
	 */
	public function enqueue_core_block_public_styles(): void {
		$block    = $this->get_block_handle();
		$path     = $this->get_block_path();
		$args     = $this->get_asset_file_args( get_theme_file_path( "dist/blocks/$path/editor.asset.php" ) );
		$src_path = get_theme_file_path( "dist/blocks/$path/style-index.css" );

		if ( ! file_exists( $src_path ) ) {
			return;
		}

		if ( is_admin() ) {
			$src = get_theme_file_uri( "dist/blocks/$path/style-index.css" );

			wp_enqueue_style(
				"tribe-$block",
				$src,
				[],
				$args['version'] ?? false,
				'all'
			);

			return;
		}

		$css = file_get_contents( $src_path );

		if ( $css === false ) {
			return;
		}

		$core_handle = $this->get_block_style_handle();

		if ( ! wp_style_is( $core_handle, 'registered' ) ) {
			_doing_it_wrong(
				__METHOD__,
				sprintf(
					'Extended block "%1$s" has no registered stylesheet handle "%2$s" when attaching front-end styles. Override get_block_style_handle() to match the block\'s registered style, or ensure the block registers its stylesheet on init before wp_enqueue_scripts.',
					$this->get_block_name(),
					$core_handle
				),
				''
			);

			return;
		}

		wp_add_inline_style( $core_handle, $css );
	}

	/**
	 * Enqueue editor-specific styles
	 *
	 * These are the editor specific style overrides for the block.
	 * Styles are added as a `<link>` file for both block & site editor regardless of if it is inline or iframed.
	 */
	public function enqueue_core_block_editor_styles(): void {
		if ( ! is_admin() ) {
			return;
		}

		$block    = $this->get_block_handle();
		$path     = $this->get_block_path();
		$args     = $this->get_asset_file_args( get_theme_file_path( "dist/blocks/$path/editor.asset.php" ) );
		$src_path = get_theme_file_path( "dist/blocks/$path/editor.css" );
		$src      = get_theme_file_uri( "dist/blocks/$path/editor.css" );

		if ( ! file_exists( $src_path ) ) {
			return;
		}

		wp_enqueue_style(
			"tribe-editor-$block",
			$src,
			[],
			$args['version'] ?? false,
			'all'
		);
	}

	public function enqueue_core_block_editor_scripts(): void {
		if ( ! is_admin() ) {
			return;
		}

		$block    = $this->get_block_handle();
		$path     = $this->get_block_path();
		$args     = $this->get_asset_file_args( get_theme_file_path( "dist/blocks/$path/editor.asset.php" ) );
		$src_path = get_theme_file_path( "dist/blocks/$path/editor.js" );
		$src      = get_theme_file_uri( "dist/blocks/$path/editor.js" );

		if ( ! file_exists( $src_path ) ) {
			return;
		}

		wp_enqueue_script(
			"tribe-editor-$block",
			$src,
			$args['dependencies'] ?? [],
			$args['version'] ?? false,
			true
		);
	}

}
