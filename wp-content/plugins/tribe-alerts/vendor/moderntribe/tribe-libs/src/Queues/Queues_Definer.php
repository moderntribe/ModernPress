<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Queues;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
use Tribe\Alert_Scoped\Tribe\Libs\CLI\CLI_Definer;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Container;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
use Tribe\Alert_Scoped\Tribe\Libs\Container\MutableContainer;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Backends\WP_Cache;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\CLI\Add_Tasks;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\CLI\Cleanup;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\CLI\List_Queues;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\CLI\Process;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\CLI\Run;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts\Backend;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts\Queue;
class Queues_Definer implements Definer_Interface
{
    public function define() : array
    {
        return [
            MutableContainer::class => static function (ContainerInterface $c) {
                return (new Container())->wrap($c);
            },
            Backend::class => DI\create(WP_Cache::class),
            Queue::class => DI\autowire(DefaultQueue::class),
            Queue_Collection::class => DI\create()->method('add', DI\get(Queue::class)),
            Run::class => static function (ContainerInterface $c) : Run {
                $container = DI\FactoryInterface::class;
                // No subprocess support, utilize the mutable container to ensure fresh instances.
                if (!$c->get(Subprocess_Checker::class)->enabled()) {
                    $container = MutableContainer::class;
                }
                return new Run($c->get(Queue_Collection::class), $c->get($container));
            },
            /**
             * Add commands for the CLI subscriber to register
             */
            CLI_Definer::COMMANDS => DI\add([DI\get(List_Queues::class), DI\get(Add_Tasks::class), DI\get(Cleanup::class), DI\get(Process::class), DI\get(Run::class)]),
        ];
    }
}
