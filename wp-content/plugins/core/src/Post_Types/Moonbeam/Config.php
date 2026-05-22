<?php declare(strict_types=1);

namespace Tribe\Plugin\Post_Types\Moonbeam;

use Tribe\Plugin\Post_Types\Post_Type_Config;

class Config extends Post_Type_Config {

	protected string $post_type = Moonbeam::NAME;

	public function get_args(): array {
		return [
			'capability_type'  => 'post',
			'delete_with_user' => false,
			'has_archive'      => true,
			'hierarchical'     => false,
			'map_meta_cap'     => true,
			'menu_icon'        => 'dashicons-star-filled',
			'menu_position'    => 20,
			'public'           => true,
			'show_in_menu'     => true,
			'show_in_rest'     => true,
			'supports'         => [ 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ],
			'taxonomies'       => [ 'category', 'post_tag' ],
		];
	}

	public function get_labels(): array {
		return [
			'singular'  => esc_html__( 'Moonbeam', 'tribe' ),
			'plural'    => esc_html__( 'Moonbeams', 'tribe' ),
			'menu_name' => esc_html__( 'Moonbeams', 'tribe' ),
			'slug'      => $this->post_type,
		];
	}

}
