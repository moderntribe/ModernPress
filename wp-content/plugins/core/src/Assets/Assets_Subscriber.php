<?php declare(strict_types=1);

namespace Tribe\Plugin\Assets;

use Tribe\Plugin\Core\Abstract_Subscriber;

class Assets_Subscriber extends Abstract_Subscriber {

	public function register(): void {
		add_action( 'wp_enqueue_scripts', function (): void {
			$this->container->get( Public_Assets_Enqueuer::class )->register();
		}, 10, 0 );

		add_action( 'wp_enqueue_scripts', function (): void {
			$this->container->get( Print_Assets_Enqueuer::class )->register();
		}, 10, 0 );

		add_action( 'admin_enqueue_scripts', function (): void {
			$this->container->get( Admin_Assets_Enqueuer::class )->register();
		}, 10, 0 );

		add_action( 'enqueue_block_assets', function (): void {
			$this->container->get( Editor_Assets_Enqueuer::class )->register();
		}, 10, 0 );

		 add_action( 'login_enqueue_scripts', function (): void {
			$this->container->get( Admin_Assets_Enqueuer::class )->enqueue_login_styles();
		 }, 10, 0 );

		add_action( 'login_headerurl', function (): void {
			$this->container->get( Admin_Assets_Enqueuer::class )->update_login_header_url();
		}, 10, 0 );

		add_action( 'login_head', function (): void {
			$this->container->get( Admin_Assets_Enqueuer::class )->update_login_header();
		}, 10, 0 );
	}

}
