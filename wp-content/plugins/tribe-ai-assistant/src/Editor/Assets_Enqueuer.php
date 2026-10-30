<?php
/**
 * Editor Assets Enqueuer.
 *
 * @package Tribe\AI
 */

namespace Tribe\AI\Editor;

/**
 * Class Assets_Enqueuer
 */
class Assets_Enqueuer {
	/**
	 * Enqueue editor assets.
	 */
	public function enqueue(): void {
		$asset_file = TRIBE_AI_DIR . 'assets/js/build/editor.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = include $asset_file;
		$dependencies = $asset['dependencies'] ?? [];

		if ( ! in_array( 'wp-block-editor', $dependencies, true ) ) {
			$dependencies[] = 'wp-block-editor';
		}

		wp_enqueue_script(
			'tribe-ai-editor',
			TRIBE_AI_URL . 'assets/js/build/editor.js',
			$dependencies,
			$asset['version'] ?? TRIBE_AI_VERSION,
			true
		);

		wp_enqueue_style(
			'tribe-ai-editor',
			TRIBE_AI_URL . 'assets/css/editor.css',
			[],
			(string) filemtime( TRIBE_AI_DIR . 'assets/css/editor.css' )
		);

		wp_set_script_translations(
			'tribe-ai-editor',
			'tribe-ai-assistant'
		);
	}
}
