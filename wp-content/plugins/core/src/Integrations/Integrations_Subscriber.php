<?php declare(strict_types=1);

namespace Tribe\Plugin\Integrations;

use Tribe\Plugin\Core\Abstract_Subscriber;
use Tribe\Plugin\Integrations\ACF_Fields\Color_Picker\Color_Picker_Render;

class Integrations_Subscriber extends Abstract_Subscriber {

	public function register(): void {

		add_filter( 'acf/settings/show_admin', function ( $show ): bool {
			return $this->container->get( ACF::class )->show_acf_menu_item( (bool) $show );
		}, 10, 1 );

		add_filter( 'wpseo_accessible_post_types', function ( $post_types ) {
			if ( ! is_array( $post_types ) ) {
				return [];
			}

			return $this->container->get( YoastSEO::class )->exclude_post_types( $post_types );
		}, 100 );

		add_filter( 'rank_math/excluded_post_types', function ( $post_types ) {
			if ( ! is_array( $post_types ) ) {
				return [];
			}

			return $this->container->get( RankMath::class )->exclude_post_types( $post_types );
		}, 100 );

		add_action('acf/render_field/type=color_picker_tribe', function ($field): void {
			$this->container->get( Color_Picker_Render::class )->render_color_picker( $field );
		}, 10, 1 );
	}

}
