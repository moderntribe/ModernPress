<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Queues\CLI;

use Throwable;
use Tribe\Alert_Scoped\Tribe\Libs\CLI\Command;
use Tribe\Alert_Scoped\Tribe\Libs\Container\MutableContainer;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts\Task;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Queue_Collection;
use Tribe\Alert_Scoped\WP_CLI;
use function WP_CLI\Utils\get_flag_value;
/**
 * Run a job from the queue.
 *
 * @see Process
 */
class Run extends Command
{
    public const ARG_TASK_ARGS = 'arguments';
    public const OPTION_JOB_ID = 'id';
    public const OPTION_QUEUE = 'queue';
    public const OPTION_TASK = 'task';
    /**
     * @var Queue_Collection
     */
    protected $queues;
    /**
     * @var \DI\FactoryInterface|\Tribe\Libs\Container\MutableContainer
     */
    protected $container;
    public function __construct(Queue_Collection $queue_collection, $container)
    {
        $this->queues = $queue_collection;
        $this->container = $container;
        parent::__construct();
    }
    public function command() : string
    {
        return 'queues run';
    }
    public function description() : string
    {
        return \__('Run a job from a queue', 'tribe');
    }
    public function arguments() : array
    {
        return [['type' => self::ARGUMENT, 'name' => self::ARG_TASK_ARGS, 'optional' => \true, 'description' => \__('The task arguments', 'tribe'), 'repeating' => \true], ['type' => self::OPTION, 'name' => self::OPTION_JOB_ID, 'optional' => \false, 'description' => \__('The job ID to run', 'tribe')], ['type' => self::OPTION, 'name' => self::OPTION_TASK, 'optional' => \false, 'description' => \__('The fully qualified Task class with namespace', 'tribe')], ['type' => self::OPTION, 'name' => self::OPTION_QUEUE, 'optional' => \true, 'description' => \__('The name of the queue processing this task', 'tribe'), 'default' => 'default']];
    }
    /**
     * Run the queue task.
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @throws WP_CLI\ExitException
     *
     * @return void
     */
    public function run_command($args, $assoc_args) : void
    {
        $task_class = get_flag_value($assoc_args, self::OPTION_TASK);
        $queue_name = get_flag_value($assoc_args, self::OPTION_QUEUE);
        $job_id = get_flag_value($assoc_args, self::OPTION_JOB_ID);
        WP_CLI::debug(\sprintf(\__('Running Task "%s" on Queue "%s", with Job ID "%s"', 'tribe'), $task_class, $queue_name, $job_id));
        try {
            $queue = $this->queues->get($queue_name);
        } catch (Throwable $e) {
            WP_CLI::error($e->getMessage());
        }
        try {
            // No subprocess support, utilize the mutable container to ensure fresh instances.
            if ($this->container instanceof MutableContainer) {
                /** @var Task $task */
                $task = $this->container->makeFresh($task_class);
            } else {
                /** @var Task $task */
                $task = $this->container->make($task_class);
            }
        } catch (Throwable $e) {
            WP_CLI::debug(\sprintf(\__('Unable to create task instance for class "%s". Error: %s', 'tribe'), $task_class, $e->getMessage()));
            $queue->nack($job_id);
            return;
        }
        if (empty($args)) {
            $args = [];
        }
        if ($task->handle($args)) {
            // Acknowledge.
            WP_CLI::debug(\sprintf('ACK: %s (%s)', $job_id, $task_class));
            $queue->ack($job_id);
        } else {
            WP_CLI::debug(\sprintf('NACK: %s (%s)', $job_id, $task_class));
            $queue->nack($job_id);
        }
    }
}
