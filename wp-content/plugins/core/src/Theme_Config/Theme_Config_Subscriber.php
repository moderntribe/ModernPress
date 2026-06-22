<?php declare(strict_types=1);

namespace Tribe\Plugin\Theme_Config;

use Tribe\Plugin\Core\Abstract_Subscriber;
use Tribe\Plugin\Menus\Menu_Registrar;

class Theme_Config_Subscriber extends Abstract_Subscriber {

	public function register(): void {
		add_action( 'after_setup_theme', function (): void {
			$this->container->get( Theme_Support::class )->add_theme_supports();

			$this->container->get( Menu_Registrar::class )->register();

			$this->container->get( Image_Sizes::class )->register_sizes();
		}, 10, 0 );

		add_filter( 'excerpt_more', static function (): string {
			return '...';
		});

		// Handle admin functions for disabling comments
		add_action( 'admin_init', function (): void {
			$this->container->get( Comment_Support::class )->admin_comment_page_redirect();

			$this->container->get( Comment_Support::class )->remove_recent_comments_metabox();

			$this->container->get( Comment_Support::class )->disable_post_type_comment_support();
		});

		// Close comments on the front-end
		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );

		// Hide existing comments
		add_filter( 'comments_array', '__return_empty_array', 10 );

		// Remove comments page in menu
		add_action( 'admin_menu', function (): void {
			$this->container->get( Comment_Support::class )->remove_comments_menu_item();
		});

		// Remove comments links from admin bar
		add_action( 'init', function (): void {
			$this->container->get( Comment_Support::class )->remove_admin_bar_comments();
		});

		/**
		 * Disable XML-RPC authentication support.
		 *
		 * @see wp-includes/class-wp-xmlrpc-server.php:219
		 */
		add_filter( 'xmlrpc_enabled', '__return_false' );
	}

}
