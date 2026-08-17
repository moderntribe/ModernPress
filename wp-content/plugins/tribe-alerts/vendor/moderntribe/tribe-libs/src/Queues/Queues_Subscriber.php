<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Queues;

use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
class Queues_Subscriber extends Abstract_Subscriber
{
    public function register() : void
    {
        $this->cron();
    }
    protected function cron() : void
    {
        if (\defined('DISABLE_WP_CRON') && DISABLE_WP_CRON) {
            return;
        }
        \add_filter('cron_schedules', function ($schedules) {
            return $this->container->get(Cron::class)->add_interval($schedules);
        }, 10, 1);
        \add_action('admin_init', function () : void {
            $this->container->get(Cron::class)->schedule_cron();
        }, 10, 0);
        \add_action(Cron::CRON_ACTION, function () : void {
            foreach ($this->container->get(Queue_Collection::class)->queues() as $queue) {
                $this->container->get(Cron::class)->process_queues($queue);
                $this->container->get(Cron::class)->cleanup($queue);
            }
        }, 10, 0);
    }
}
