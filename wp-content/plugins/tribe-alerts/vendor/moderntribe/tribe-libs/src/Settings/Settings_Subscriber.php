<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Settings;

use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
class Settings_Subscriber extends Abstract_Subscriber
{
    public function register() : void
    {
        \add_action('init', function () {
            foreach ($this->container->get(Settings_Definer::PAGES) as $page) {
                $page->hook();
            }
        }, 0, 0);
    }
}
