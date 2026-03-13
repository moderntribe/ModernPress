<?php declare(strict_types=1);

namespace Tribe\Plugin\Integrations;

use Tribe\Plugin\Core\Abstract_Subscriber;
use Tribe\Plugin\Integrations\ACF_Fields\Color_Picker\Color_Picker_Render;

class Integrations_Subscriber extends Abstract_Subscriber {

	public function register(): void {

		add_filter( 'acf/settings/show_admin', function ( $show ): bool {
			return $this->container->get( ACF::class )->show_acf_menu_item( (bool) $show );
		}, 10, 1 );

		add_filter( 'wpseo_accessible_post_types', function ( $post_types ): array {
			if ( ! is_array( $post_types ) ) {
				return [];
			}

			return $this->container->get( YoastSEO::class )->exclude_post_types( $post_types );
		}, 100 );

		add_filter( 'rank_math/excluded_post_types', function ( $post_types ): array {
			if ( ! is_array( $post_types ) ) {
				return [];
			}

			return $this->container->get( RankMath::class )->exclude_post_types( $post_types );
		}, 100 );

		add_action('acf/render_field/type=color_picker_tribe', function ($field): void {
			$this->container->get( Color_Picker_Render::class )->render_color_picker( $field );
		}, 10, 1 );

		add_filter( 'block_editor_settings_all', function ( $settings ): array {
			$settings['facetwpFacets'] = $this->container->get( FacetWP::class )->get_facets();

			return $settings;
		}, 10, 1 );

		// Add id to the first <select> or <input> so label[for] targets the focusable control.
		add_filter( 'facetwp_facet_html', function ( $output, $params ): string {
			return $this->container->get( FacetWP::class )->add_facet_control_id( $output, $params );
		}, 10, 2 );

		// Remove counts from certain facet types.
		add_filter( 'facetwp_facet_html', function ( $output, $params ): string {
			return $this->container->get( FacetWP::class )->remove_facetwp_counts( $output, $params );
		}, 10, 2 );

		// Hide the counts in the fSelect facet dropdown.
		add_filter( 'facetwp_facet_dropdown_show_counts', '__return_false' );

		// Register the pagination facet.
		add_filter( 'facetwp_facets', function ( $facets ): array {
			return $this->container->get( FacetWP::class )->register_custom_facets( $facets );
		}, 10, 1 );

		// Rewrite the pagination link tags.
		add_filter( 'facetwp_facet_pager_link', function ( $html, $params ): string {
			return $this->container->get( FacetWP::class )->rewrite_pagination_link_tags( $html, $params );
		}, 10, 2 );
	}

}
