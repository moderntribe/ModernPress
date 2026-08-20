<?php declare(strict_types=1);

namespace Tribe\Plugin\Theme_Config;

class Icons {

	public const COLLECTION = 'tribe';

	private const SVG_DIR = 'assets/media/icons/picker';

	public function register_icon_collection(): void {
		\wp_register_icon_collection(
			self::COLLECTION,
			[
				'label'       => __( 'Modern Tribe', 'tribe' ),
				'description' => __( 'Icons provided by Modern Tribe', 'tribe' ),
			]
		);
	}

	public function register_icons(): void {
		$dir = get_theme_file_path( self::SVG_DIR );

		foreach ( glob( $dir . '/*.svg' ) ?: [] as $file ) {
			$slug = basename( $file, '.svg' );

			\wp_register_icon(
				self::COLLECTION . '/' . $slug,
				[
					'label'     => ucwords( str_replace( '-', ' ', $slug ) ),
					'file_path' => $file,
				]
			);
		}
	}

}
