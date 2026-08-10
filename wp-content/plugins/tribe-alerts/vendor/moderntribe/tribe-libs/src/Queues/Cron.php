<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Queues;

use Exception;
use Throwable;
use Tribe\Alert_Scoped\Tribe\Libs\Container\MutableContainer;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts\Queue;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts\Task;
/**
 * Process queues via the WordPress cron
 *
 * @package Tribe\Libs\Queues
 */
class Cron
{
    public const CRON_ACTION = 'tribe_queue_cron';
    public const FREQUENCY = 'tribe_queue_frequency';
    /**
     * @var MutableContainer
     */
    protected $container;
    /**
     * @var int
     */
    private $frequency_in_seconds;
    /**
     * @var int
     */
    private $timelimit_in_seconds;
    /**
     * Cron constructor.
     *
     * @param  \Tribe\Libs\Container\MutableContainer  $container
     * @param  int                                     $frequency
     * @param  int                                     $timelimit
     */
    public function __construct(MutableContainer $container, int $frequency = 60, int $timelimit = 15)
    {
        $this->container = $container;
        $this->frequency_in_seconds = $frequency;
        $this->timelimit_in_seconds = $timelimit;
    }
    /**
     * Process a Queue's tasks.
     *
     * @param  \Tribe\Libs\Queues\Contracts\Queue  $queue
     *
     * @action self::CRON_ACTION
     */
    public function process_queues(Queue $queue) : void
    {
        $end_time = \time() + $this->timelimit_in_seconds;
        while (\time() < $end_time) {
            if (!$queue->count()) {
                return;
            }
            try {
                $job = $queue->reserve();
            } catch (Exception $e) {
                return;
            }
            $task_class = $job->get_task_handler();
            try {
                /** @var Task $task */
                $task = $this->container->makeFresh($task_class);
            } catch (Throwable $e) {
                $queue->nack($job->get_job_id());
                return;
            }
            if ($task->handle($job->get_args())) {
                // Acknowledge.
                $queue->ack($job->get_job_id());
            } else {
                $queue->nack($job->get_job_id());
            }
        }
    }
    /**
     * Schedule a cron job.
     *
     * @action admin_init
     */
    public function schedule_cron() : void
    {
        if (!\wp_next_scheduled(self::CRON_ACTION)) {
            \wp_schedule_event(\time(), self::FREQUENCY, self::CRON_ACTION);
        }
    }
    /**
     * Filter existing WordPress cron schedules.
     *
     * @param  array  $cron_schedules
     *
     * @return array
     *
     * @filter cron_schedules
     */
    public function add_interval(array $cron_schedules = []) : array
    {
        $cron_schedules[self::FREQUENCY] = ['interval' => $this->frequency_in_seconds, 'display' => \__('Queue Cron Schedule', 'tribe')];
        return $cron_schedules;
    }
    /**
     * Remove completed and timed out tasks from the queue after a certain ttl.
     *
     * @param  \Tribe\Libs\Queues\Contracts\Queue  $queue
     */
    public function cleanup(Queue $queue) : void
    {
        $queue->cleanup();
    }
}
