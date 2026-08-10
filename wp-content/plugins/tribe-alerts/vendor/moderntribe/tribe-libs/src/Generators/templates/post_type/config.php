<?php

namespace Tribe\Project\Post_Types\%1$s;

use Tribe\Libs\Post_Type\Post_Type_Config;

class Config extends Post_Type_Config {
	protected $post_type = %1$s::NAME;

	public function get_args() {
		return [
			'hierarchical'     => false,
			'enter_title_here' => __( '%2$s title', 'tribe' ),
			'menu_icon'        => 'dashicons-warning',
			'map_meta_cap'     => true,
			'supports'         => [ 'title', 'editor', 'author', 'thumbnail', 'page-attributes', 'excerpt', 'revisions', ],
			'capability_type'  => 'post', // to use default WP caps
			'show_in_rest'     => true,
		];
	}

	public function get_labels() {
		return [
			'singular' => __( '%2$s', 'tribe' ),
			'plural'   => __( '%3$s', 'tribe' ),
			'slug'     => __( '%4$s', 'tribe' ),
		];
	}

}
