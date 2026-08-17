<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Whoops;

use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
class Whoops_Subscriber extends Abstract_Subscriber
{
    public function register() : void
    {
        if (\defined('WHOOPS_ENABLE') && WHOOPS_ENABLE) {
            \add_action('init', function () {
                $this->container->get(\Tribe\Alert_Scoped\Whoops\Run::class)->register();
            }, -10, 0);
        }
    }
}
