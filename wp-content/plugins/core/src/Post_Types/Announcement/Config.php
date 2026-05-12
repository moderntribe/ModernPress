<?php declare(strict_types=1);

namespace Tribe\Plugin\Post_Types\Announcement;

use Tribe\Plugin\Post_Types\Post_Type_Config;

class Config extends Post_Type_Config {

	protected string $post_type = Announcement::NAME;

	public function get_args(): array {
		return [
			'hierarchical'     => false,
			'has_archive'      => false,
			'public'           => false,
			'show_ui'          => true,
			'enter_title_here' => esc_html__( 'Enter Announcement Title', 'tribe' ),
			'map_meta_cap'     => true,
			'supports'         => [ 'title' ],
			'menu_icon'        => 'dashicons-megaphone',
			'capability_type'  => 'post',
		];
	}

	public function get_labels(): array {
		return [
			'singular' => esc_html__( 'Announcement', 'tribe' ),
			'plural'   => esc_html__( 'Announcements', 'tribe' ),
		];
	}

}
