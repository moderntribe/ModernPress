<?php declare(strict_types=1);

namespace Tribe\Plugin\Post_Types\Location;

use Tribe\Plugin\Post_Types\Post_Type_Config;

class Config extends Post_Type_Config {

	protected string $post_type = Location::NAME;

	public function get_args(): array {
		return [
			'capability_type'     => 'post',
			'delete_with_user'    => false,
			'exclude_from_search' => false,
			'has_archive'         => true,
			'hierarchical'        => false,
			'map_meta_cap'        => true,
			'menu_icon'           => 'dashicons-location-alt',
			'menu_position'       => 21,
			'public'              => true,
			'publicly_queryable'  => true,
			'rewrite'             => [
				'slug'       => $this->post_type,
				'with_front' => false,
			],
			'show_in_nav_menus'   => true,
			'show_in_rest'        => true,
			'supports'            => [ 'title', 'editor', 'thumbnail', 'revisions' ],
		];
	}

	public function get_labels(): array {
		return [
			'singular'  => esc_html__( 'Location', 'tribe' ),
			'plural'    => esc_html__( 'Locations', 'tribe' ),
			'menu_name' => esc_html__( 'Locations', 'tribe' ),
			'slug'      => $this->post_type,
		];
	}

}
