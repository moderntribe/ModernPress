<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Cache;

use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
class Cache_Subscriber extends Abstract_Subscriber
{
    public function register() : void
    {
        $this->purge();
    }
    protected function purge() : void
    {
        \add_action('init', function () {
            $this->container->get(Purger::class)->maybe_purge_cache();
        }, 9, 0);
        \add_action('admin_bar_menu', function ($admin_bar) {
            $this->container->get(Purger::class)->add_admin_bar_button($admin_bar);
        }, 100, 1);
    }
}
