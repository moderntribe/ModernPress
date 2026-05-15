<?php declare(strict_types=1);

namespace PatternSync;

use DI\ContainerBuilder;
use PatternSync\Admin\Admin_Definer;
use PatternSync\Admin\Admin_Subscriber;
use PatternSync\Connections\Connections_Definer;
use PatternSync\Patterns\Patterns_Subscriber;
use PatternSync\Rest\Rest_Definer;
use PatternSync\Rest\Rest_Subscriber;
use PatternSync\Sync\Sync_Definer;

class Core {

	public const PLUGIN_FILE = 'plugin.file';

	private \DI\FactoryInterface|\Invoker\InvokerInterface|\Psr\Container\ContainerInterface $container;

	/**
	 * @var string[] Names of classes implementing Definer_Interface.
	 */
	private array $definers = [
		Connections_Definer::class,
		Sync_Definer::class,
		Rest_Definer::class,
		Admin_Definer::class,
	];

	/**
	 * @var string[] Names of classes extending Abstract_Subscriber.
	 */
	private array $subscribers = [
		Patterns_Subscriber::class,
		Rest_Subscriber::class,
		Admin_Subscriber::class,
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

	public function container(): \DI\FactoryInterface|\Invoker\InvokerInterface|\Psr\Container\ContainerInterface {
		return $this->container;
	}

	/**
	 * @throws \Exception
	 */
	private function init_container( string $plugin_path ): void {
		$definers    = apply_filters( 'pattern_sync/definers', $this->definers );
		$subscribers = apply_filters( 'pattern_sync/subscribers', $this->subscribers );

		$builder = new ContainerBuilder();
		$builder->useAutowiring( true );
		$builder->addDefinitions( [ self::PLUGIN_FILE => $plugin_path ] );
		$builder->addDefinitions( ...array_map( static fn ( string $classname ) => ( new $classname() )->define(), $definers ) );

		$this->container = $builder->build();

		foreach ( $subscribers as $subscriber_class ) {
			( new $subscriber_class( $this->container ) )->register();
		}
	}

}
