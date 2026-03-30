<?php declare(strict_types=1);

namespace Tribe\Plugin\Menus;

use Tribe\Plugin\Post_Types\Announcement\Announcement;
use Tribe\Plugin\Post_Types\Page\Page;
use Tribe\Plugin\Post_Types\Training\Training;

class Admin_Menu_Order {

	public function custom_menu_order(): array {
		$cpts   = $this->get_post_types();
		$before = [
			'index.php',
			'separator1',
			'edit.php?post_type=' . Page::NAME,
			'edit.php',
		];

		foreach ( $cpts as $post_type ) {
			$before[] = 'edit.php?post_type=' . $post_type;
		}

		$after = [
			'edit.php?post_type=' . Announcement::NAME,
			'upload.php',
			'gf_edit_forms',
			'separator2',
			'themes.php',
			'plugins.php',
			'users.php',
			'tools.php',
			'options-general.php',
			'edit.php?post_type=' . Training::NAME,
			'separator-last',
			'edit.php?post_type=acf-field-group',
			'wpseo_dashboard',
			'rank-math',
		];

		return array_merge( $before, $after );
	}

	/**
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	protected function get_post_types(): array {
		try {
			// get all post type subscribers
			$subscribers = array_filter( tribe_project()->get_subscribers(), function ( $subscriber ) {
				return str_contains( $subscriber, 'Post_Types\\' );
			} );

			$post_types = [];
			foreach ( $subscribers as $subscriber ) {
				// there is no post subscriber so there's no need to define it here to skip
				$skip_post_types = [
					'Tribe\\Plugin\\Post_Types\\Page\\Page_Subscriber',
					'Tribe\\Plugin\\Post_Types\\Training\\Training_Subscriber',
					'Tribe\\Plugin\\Post_Types\\Announcement\\Announcement_Subscriber',
				];

				// skip std / predefined post types
				if ( in_array( $subscriber, $skip_post_types, true ) ) {
					continue;
				}

				$parts = explode( '\\', $subscriber );
				array_pop( $parts );

				$config_class = implode( '\\', $parts ) . '\\Config';
				$post_type    = tribe_project()->container()->get( $config_class )->post_type();

				/**
				 * @var \WP_Post_Type $post_object
				 */
				$post_object = get_post_type_object( $post_type );

				if ( ! $post_object->show_ui || ! $post_object->public ) {
					continue;
				}

				$post_types[ $post_object->menu_position ] = $post_type;
			}

			ksort( $post_types );

			return $post_types;
		} catch ( \Throwable $exception ) {
			return [];
		}
	}

}
