<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Taxonomy;

use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
abstract class Taxonomy_Subscriber extends Abstract_Subscriber
{
    /**
     * @var class-string<\Tribe\Libs\Taxonomy\Taxonomy_Config> The taxonomy configuration class. Should extend Taxonomy_Config
     */
    protected $config_class;
    public function register() : void
    {
        if (!$this->config_class) {
            return;
        }
        \add_action('init', function () {
            $this->container->get($this->config_class)->register();
        }, 0, 0);
    }
}
