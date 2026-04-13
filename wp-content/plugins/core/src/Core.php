<?php declare(strict_types=1);

namespace Tribe\Plugin;

use DI\ContainerBuilder;

class Core {

	public const PLUGIN_FILE = 'plugin.file';

	private \Psr\Container\ContainerInterface|\Invoker\InvokerInterface|\DI\FactoryInterface $container;

	/**
	 * @var string[] Names of classes implementing Definer_Interface.
	 */
	private array $definers = [
		Blocks\Blocks_Definer::class,
		Object_Meta\Meta_Definer::class,
		Settings\Settings_Definer::class,
	];

	/**
	 * @var string[] Names of classes extending Abstract_Subscriber.
	 */
	private array $subscribers = [
		Assets\Assets_Subscriber::class,
		Blocks\Blocks_Subscriber::class,
		Integrations\Integrations_Subscriber::class,
		Menus\Menu_Subscriber::class,
		Object_Meta\Meta_Subscriber::class,
		Settings\Settings_Subscriber::class,
		Theme_Config\Theme_Config_Subscriber::class,

		// Post Types
		Post_Types\Page\Page_Subscriber::class,
		Post_Types\Training\Training_Subscriber::class,
		Post_Types\Announcement\Announcement_Subscriber::class,
	];

	private static self $instance;

	public static function instance(): self {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * @throws \Exception
	 */
	public function init( string $plugin_path ): void {
		$this->init_container( $plugin_path );
	}

	public function container(): \Psr\Container\ContainerInterface|\Invoker\InvokerInterface|\DI\FactoryInterface {
		return $this->container;
	}

	public function get_subscribers(): array {
		return $this->subscribers;
	}

	/**
	 * @throws \Exception
	 */
	private function init_container( string $plugin_path ): void {

		/**
		 * Filter the list of definers that power the plugin.
		 *
		 * @param string[] $definers The class names of definers that will be instantiated
		 */
		$definers = apply_filters( 'tribe/plugin/definers', $this->definers );

		/**
		 * Filter the list subscribers that power the plugin
		 *
		 * @param string[] $subscribers The class names of subscribers that will be instantiated
		 */
		$subscribers = apply_filters( 'tribe/plugin/subscribers', $this->subscribers );

		$builder = new ContainerBuilder();
		$builder->useAutowiring( true );
		$builder->addDefinitions( [ self::PLUGIN_FILE => $plugin_path ] );
		$builder->addDefinitions( ...array_map( static fn ( $classname ) => ( new $classname() )->define(), $definers ) );

		$this->container = $builder->build();

		foreach ( $subscribers as $subscriber_class ) {
			( new $subscriber_class( $this->container ) )->register();
		}
	}

}
