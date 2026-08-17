<?php

declare (strict_types=1);
namespace Tribe\Alert;

use Tribe\Alert_Scoped\DI\ContainerBuilder;
use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
use Throwable;
use Tribe\Alert\Components\Alert\Alert_Definer;
use Tribe\Alert\Meta\Meta_Definer;
use Tribe\Alert\Resources\Resource_Definer;
use Tribe\Alert\Resources\Resource_Subscriber;
use Tribe\Alert\Rule_Processing\Rule_Processor_Definer;
use Tribe\Alert\Settings\Settings_Definer;
use Tribe\Alert\Theme\Theme_Subscriber;
use Tribe\Alert\View\View_Definer;
use Tribe\Alert_Scoped\Tribe\Libs\Object_Meta\Object_Meta_Definer;
use Tribe\Alert_Scoped\Tribe\Libs\Object_Meta\Object_Meta_Subscriber;
use Tribe\Alert_Scoped\Tribe\Libs\Pipeline\Pipeline_Definer;
use Tribe\Alert_Scoped\Tribe\Libs\Settings\Settings_Subscriber;
/**
 * Class Core
 *
 * @package Tribe\Alert
 */
final class Core
{
    public const PLUGIN_FILE = 'plugin.file';
    public const VERSION_DEFINITION = 'plugin.version';
    public const VERSION = '1.8';
    public const RESOURCES_PATH = 'plugin.resources_path';
    public const RESOURCES_URI = 'plugin.resources_uri';
    public const DIST_DIR_PATH = 'plugin.dist_dir_path';
    public const DIST_DIR_URI = 'plugin.dist_dir_uri';
    protected const RESOURCES_DIR = 'resources';
    protected const DIST_DIR = 'dist';
    private ContainerInterface $container;
    /**
     * @var \Tribe\Libs\Container\Definer_Interface[]
     */
    private array $definers = [Rule_Processor_Definer::class, Object_Meta_Definer::class, Meta_Definer::class, Pipeline_Definer::class, Resource_Definer::class, Settings_Definer::class, View_Definer::class, Alert_Definer::class];
    /**
     * @var \Tribe\Libs\Container\Abstract_Subscriber[]
     */
    private array $subscribers = [
        Object_Meta_Subscriber::class,
        Resource_Subscriber::class,
        Settings_Subscriber::class,
        Theme_Subscriber::class,
        // Custom Post Types
        \Tribe\Alert\Post_Types\Alert\Subscriber::class,
    ];
    private static self $instance;
    private function __construct()
    {
    }
    /**
     * Singleton constructor.
     */
    public static function instance() : self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    /**
     * @param  string  $plugin_path  The server path to the main plugin file.
     */
    public function init(string $plugin_path) : void
    {
        // Build an auto-wiring service container.
        $builder = new ContainerBuilder();
        $builder->useAutowiring(\true);
        $builder->useAttributes(\false);
        $builder->addDefinitions([self::PLUGIN_FILE => $plugin_path]);
        $builder->addDefinitions([self::VERSION_DEFINITION => self::VERSION]);
        $builder->addDefinitions([self::RESOURCES_PATH => \plugin_dir_path($plugin_path) . self::RESOURCES_DIR]);
        $builder->addDefinitions([self::RESOURCES_URI => \plugin_dir_url($plugin_path) . self::RESOURCES_DIR]);
        $builder->addDefinitions([self::DIST_DIR_PATH => \plugin_dir_path($plugin_path) . \sprintf('%s/%s', self::RESOURCES_DIR, self::DIST_DIR)]);
        $builder->addDefinitions([self::DIST_DIR_URI => \plugin_dir_url($plugin_path) . \sprintf('%s/%s', self::RESOURCES_DIR, self::DIST_DIR)]);
        $builder->addDefinitions(...\array_map(static function ($classname) {
            return (new $classname())->define();
        }, $this->definers));
        try {
            $this->container = $builder->build();
        } catch (Throwable $e) {
            return;
        }
        foreach ($this->subscribers as $subscriber_class) {
            (new $subscriber_class($this->container))->register();
        }
    }
    /**
     * Returns the PHP-DI container.
     *
     * @return \Psr\Container\ContainerInterface|\DI\FactoryInterface|\Invoker\InvokerInterface
     */
    public function get_container() : ContainerInterface
    {
        return $this->container;
    }
    private function __clone()
    {
    }
}
