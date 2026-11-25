<?php declare(strict_types=1);

namespace Tribe\Plugin\Blocks\Helpers;

use Tribe\Plugin\Assets\Editor_Assets_Enqueuer;

class Custom_Alignments {

	/** @var array<string, mixed>|null */
	private ?array $config = null;

	/**
	 * Load and cache custom alignments and block restrictions from the theme.
	 *
	 * @return array<string, mixed> Validated layouts and include/exclude lists.
	 */
	public function get_config(): array {
		if ( $this->config !== null ) {
			return $this->config;
		}

		$theme    = wp_json_file_decode( get_stylesheet_directory() . '/theme.json', [ 'associative' => true ] );
		$settings = $theme['settings'] ?? [];
		$layouts  = $settings['_experimentalLayout'] ?? [];

		$this->config = [
			'layouts' => $this->validate_layouts( $layouts ),
			'include' => $settings['_experimentalLayoutInclude'] ?? [],
			'exclude' => $settings['_experimentalLayoutExclude'] ?? [],
		];

		foreach ( [ 'include', 'exclude' ] as $key ) {
			if ( is_array( $this->config[ $key ] ) && array_is_list( $this->config[ $key ] ) && count( array_filter( $this->config[ $key ], 'is_string' ) ) === count( $this->config[ $key ] ) ) {
				continue;
			}

			trigger_error( "Invalid custom alignment $key list in theme.json.", E_USER_WARNING );
			$this->config[ $key ] = [];
		}

		return $this->config;
	}

	/**
	 * Validate layout definitions before using them in CSS and block schemas.
	 *
	 * @param mixed $layouts Layout definitions from theme.json.
	 *
	 * @return array<int, array<string, string>> Valid definitions.
	 */
	public function validate_layouts( mixed $layouts ): array {
		if ( ! is_array( $layouts ) || ! array_is_list( $layouts ) ) {
			trigger_error( 'Invalid custom alignments in theme.json.', E_USER_WARNING );

			return [];
		}

		$valid = [];
		$slugs = [ 'left', 'center', 'right', 'wide', 'full', 'none' ];
		foreach ( $layouts as $layout ) {
			$slug  = $layout['slug'] ?? null;
			$width = $layout['width'] ?? null;
			$name  = $layout['name'] ?? null;
			$icon  = $layout['icon'] ?? null;
			if (
				// Slugs must be safe to use as CSS class names.
				! is_string( $slug ) || ! preg_match( '/^[a-zA-Z][a-zA-Z0-9_-]*$/D', $slug ) ||
				// Reject native alignment names and previously used slugs.
				in_array( $slug, $slugs, true ) ||
				// Every option needs a nonempty label.
				! is_string( $name ) || $name === '' ||
				// Icons must be named JavaScript exports, not SVG paths.
				! is_string( $icon ) || ! preg_match( '/^[a-zA-Z_$][a-zA-Z0-9_$]*$/D', $icon ) ||
				// Widths cannot contain CSS declaration or markup delimiters.
				! is_string( $width ) || $width === '' || preg_match( '/[;{}<>]/', $width ) ||
				// WordPress must also accept the entire width as safe CSS.
				safecss_filter_attr( 'max-width:' . $width ) !== 'max-width:' . $width
			) {
				trigger_error( 'Invalid or duplicate custom alignment in theme.json.', E_USER_WARNING );
				continue;
			}

			$slugs[] = $slug;
			$valid[] = [
				'slug'  => $slug,
				'name'  => $name,
				'icon'  => $icon,
				'width' => $width,
			];
		}

		return $valid;
	}

	/**
	 * Add custom slugs to supported blocks' align schemas without changing support.
	 *
	 * @param array<string, mixed> $args Block registration arguments.
	 *
	 * @return array<string, mixed> Updated registration arguments.
	 */
	public function register_attribute( array $args ): array {
		if ( empty( $args['supports']['align'] ) ) {
			return $args;
		}

		$slugs = array_column( $this->get_config()['layouts'], 'slug' );
		if ( ! isset( $args['attributes']['align'] ) ) {
			$args['attributes']['align'] = [
				'type' => 'string',
				'enum' => [ 'left', 'center', 'right', 'wide', 'full', '', ...$slugs ],
			];
		} elseif ( isset( $args['attributes']['align']['enum'] ) ) {
			$args['attributes']['align']['enum'] = array_values( array_unique( [
				...$args['attributes']['align']['enum'],
				...$slugs,
			] ) );
		}

		return $args;
	}

	/**
	 * Supply editor configuration and custom-width styles to the editor and frontend.
	 */
	public function enqueue_assets(): void {
		$config = $this->get_config();
		if ( is_admin() ) {
			wp_add_inline_script(
				Editor_Assets_Enqueuer::EDITOR,
				'window.tribeCustomAlignments = ' . wp_json_encode( $config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';',
				'before',
			);
		}

		$css = '';
		foreach ( $config['layouts'] as $layout ) {
			$slug  = $layout['slug'];
			$width = $layout['width'];
			$css  .= ":root{--tribe--style--global--$slug-size:$width;}";
			$css  .= ":is(.is-layout-constrained,.is-root-container) > .align$slug.align$slug{max-width:$width;}";
			$css  .= "body.editor-styles-wrapper .align$slug > .components-resizable-box__container{max-width:100%!important;max-height:unset!important;}";
		}

		if ( $css === '' ) {
			return;
		}

		wp_register_style( 'tribe-custom-alignments', false );
		wp_enqueue_style( 'tribe-custom-alignments' );
		wp_add_inline_style( 'tribe-custom-alignments', $css );
	}

}
