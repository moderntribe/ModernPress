<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Object_Meta;

use Tribe\Alert_Scoped\Tribe\Libs\Object_Meta\Meta_Repository;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
class Object_Meta_Subscriber extends Abstract_Subscriber
{
    public function register() : void
    {
        \add_filter(Meta_Repository::GET_REPO_FILTER, function ($repo) {
            return $this->container->get(Meta_Repository::class)->filter_global_instance($repo);
        }, 10, 1);
        \add_action('acf/init', function () {
            foreach ($this->container->get(Object_Meta_Definer::GROUPS) as $group) {
                $group->register_group();
            }
        }, 10, 0);
    }
}
