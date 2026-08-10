<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Post_Type;

use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
abstract class Post_Type_Subscriber extends Abstract_Subscriber
{
    /**
     * @var class-string<\Tribe\Libs\Post_Type\Post_Type_Config>
     */
    protected $config_class;
    public function register() : void
    {
        if (!$this->config_class) {
            return;
        }
        \add_action('init', function () : void {
            $this->container->get($this->config_class)->register();
        }, 0, 0);
    }
}
