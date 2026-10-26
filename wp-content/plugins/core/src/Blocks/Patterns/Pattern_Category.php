<?php declare(strict_types=1);

namespace Tribe\Plugin\Blocks\Patterns;

class Pattern_Category {

	public function get_pattern_categories(): array {
		return [
			[
				'slug' => 'cta',
				'name' => __( 'Calls to Action', 'tribe' ),
			],
			[
				'slug' => 'cards',
				'name' => __( 'Cards', 'tribe' ),
			],
			[
				'slug' => 'headers',
				'name' => __( 'Headers', 'tribe' ),
			],
			[
				'slug' => 'media',
				'name' => __( 'Media', 'tribe' ),
			],
			[
				'slug' => 'pages',
				'name' => __( 'Pages', 'tribe' ),
			],
		];
	}

	public function register_pattern_categories(): void {
		foreach ( $this->get_pattern_categories() as $category ) {
			register_block_pattern_category(
				$category['slug'],
				[
					'label' => esc_html( $category['name'] ),
				]
			);
		}
	}

}
