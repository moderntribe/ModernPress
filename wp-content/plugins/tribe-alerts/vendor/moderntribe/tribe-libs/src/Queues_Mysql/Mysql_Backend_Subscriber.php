<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Queues_Mysql;

use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
use Tribe\Alert_Scoped\Tribe\Libs\Queues_Mysql\Backends\MySQL;
class Mysql_Backend_Subscriber extends Abstract_Subscriber
{
    public function register() : void
    {
        $this->backend();
    }
    /**
     * Register the backend
     */
    protected function backend()
    {
        \add_action('tribe/project/queues/mysql/init_table', function () {
            $this->container->get(MySQL::class)->initialize_table();
        }, 10, 0);
        \add_action('admin_init', function () {
            if (\defined('WP_DEBUG') && \WP_DEBUG) {
                \do_action('tribe/project/queues/mysql/init_table');
            }
        }, 0, 0);
    }
}
