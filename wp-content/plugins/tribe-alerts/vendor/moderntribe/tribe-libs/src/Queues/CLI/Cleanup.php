<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Queues\CLI;

use Exception;
use Tribe\Alert_Scoped\Tribe\Libs\CLI\Command;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Queue_Collection;
use Tribe\Alert_Scoped\WP_CLI;
class Cleanup extends Command
{
    /**
     * @var Queue_Collection
     */
    protected $queues;
    public function __construct(Queue_Collection $queue_collection)
    {
        $this->queues = $queue_collection;
        parent::__construct();
    }
    public function command()
    {
        return 'queues cleanup';
    }
    public function arguments()
    {
        return [['type' => self::ARGUMENT, 'name' => 'queue', 'optional' => \false, 'description' => \__('The name of the Queue.', 'tribe')]];
    }
    public function description()
    {
        return \__('Runs the cleanup command for a given queue.', 'tribe');
    }
    public function run_command($args, $assoc_args)
    {
        [$queue_name] = $args;
        if (!\array_key_exists($queue_name, $this->queues->queues())) {
            WP_CLI::error(\__("That queue name doesn't appear to be valid.", 'tribe'));
        }
        try {
            $queue = $this->queues->get($queue_name);
        } catch (Exception $e) {
            WP_CLI::error($e->getMessage());
        }
        $queue->cleanup();
        WP_CLI::success(\sprintf(\__('Cleaned queue: %s', 'tribe'), $queue_name));
    }
}
