<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Queues\CLI;

use Tribe\Alert_Scoped\DI;
use Exception;
use Tribe\Alert_Scoped\Tribe\Libs\CLI\Command;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Message;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Queue_Collection;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Subprocess_Checker;
use Tribe\Alert_Scoped\WP_CLI;
use function WP_CLI\Utils\get_flag_value;
/**
 * Check the Queue for jobs to run.
 */
class Process extends Command
{
    public const ARG_QUEUE = 'queue';
    public const OPTION_TIMELIMIT = 'timelimit';
    public const DEFAULT_TIMELIMIT = 300;
    /**
     * @var Queue_Collection
     */
    protected $queues;
    /**
     * @var \DI\FactoryInterface
     */
    protected $container;
    /**
     * @var int How long the process should run, in seconds. If 0,
     *          the process will run until it meets a fatal error
     *          (e.g., out of memory).
     */
    private $timelimit;
    /**
     * Whether this server supports sub processes.
     *
     * @var bool
     */
    private $proc_enabled;
    public function __construct(Queue_Collection $queue_collection, DI\FactoryInterface $container, Subprocess_Checker $subprocess_checker)
    {
        $this->queues = $queue_collection;
        $this->container = $container;
        $this->proc_enabled = $subprocess_checker->enabled();
        parent::__construct();
    }
    public function command() : string
    {
        return 'queues process';
    }
    public function description() : string
    {
        return \__('Process the queue for the provided queue name.', 'tribe');
    }
    public function arguments() : array
    {
        return [['type' => self::ARGUMENT, 'name' => self::ARG_QUEUE, 'optional' => \false, 'description' => \__('The name of the Queue.', 'tribe')], ['type' => self::OPTION, 'name' => self::OPTION_TIMELIMIT, 'optional' => \true, 'description' => \__('The max process execution in seconds.', 'tribe'), 'default' => self::DEFAULT_TIMELIMIT]];
    }
    /**
     * Execute the command.
     *
     * @param array $args
     * @param array $assoc_args
     *
     * @throws WP_CLI\ExitException
     * @return void
     */
    public function run_command($args, $assoc_args)
    {
        if (!isset($args[0])) {
            WP_CLI::error(\__('You must specify which queue you wish to process.', 'tribe'));
        }
        [$queue_name] = $args;
        $this->timelimit = (int) get_flag_value($assoc_args, self::OPTION_TIMELIMIT, self::DEFAULT_TIMELIMIT);
        if (!\array_key_exists($queue_name, $this->queues->queues())) {
            WP_CLI::error(\__("That queue name doesn't appear to be valid.", 'tribe'));
        }
        try {
            $queue = $this->queues->get($queue_name);
        } catch (Exception $e) {
            WP_CLI::error($e->getMessage());
        }
        $endtime = \time() + $this->timelimit;
        $runtime_message = \sprintf(\__('The queue will run for %d seconds', 'tribe'), $this->timelimit);
        if ($this->timelimit === 0) {
            $runtime_message = \__('Timelimit set to 0. The queue will run indefinitely or until memory is exhausted', 'tribe');
        }
        WP_CLI::log(\sprintf(\__('Processing queue: %s. %s. Run with the --debug option to see additional details.', 'tribe'), $queue_name, $runtime_message));
        // Run forever.
        while ($this->timelimit === 0 || \time() < $endtime) {
            // If the queue is empty, sleep on it and then check it again.
            if (!$queue->count()) {
                WP_CLI::debug(\__('Queue is empty. Sleeping...', 'tribe'));
                \sleep(1);
                continue;
            }
            try {
                $job = $queue->reserve();
            } catch (Exception $e) {
                WP_CLI::debug(\__('Unable to reserve task from queue. Sleeping...', 'tribe'));
                \sleep(1);
                continue;
            }
            $task_class = $job->get_task_handler();
            if (!$this->proc_enabled) {
                WP_CLI::warning(\__('The PHP functions `proc_open()` and/or `proc_close()` are disabled. Please check your PHP ini directive `disable_functions` or suhosin settings.', 'tribe'));
                WP_CLI::debug(\__('Running task via WP_CLI::run_command() instead, which runs in the same process but will utilize the mutable container.', 'tribe'));
                WP_CLI::run_command(\array_merge(['s1', 'queues', 'run'], $job->get_args()), [Run::OPTION_JOB_ID => $job->get_job_id(), Run::OPTION_QUEUE => $queue_name, Run::OPTION_TASK => $task_class]);
            } else {
                $options = $this->build_command_options($job->get_job_id(), $queue_name, $task_class);
                $arguments = $this->build_command_arguments($job);
                // Run in a child process, resetting state
                WP_CLI::runcommand(\sprintf('s1 queues run %s %s', $options, $arguments));
            }
        }
    }
    protected function build_command_options(string $job_id, string $queue_name, string $task_class) : string
    {
        $args = [Run::OPTION_JOB_ID => $job_id, Run::OPTION_QUEUE => $queue_name, Run::OPTION_TASK => $task_class];
        $options = '';
        foreach ($args as $option => $value) {
            $options .= \sprintf('--%s=%s ', $option, \escapeshellarg($value));
        }
        return $options;
    }
    protected function build_command_arguments(Message $job) : string
    {
        return \implode(' ', $job->get_args());
    }
}
