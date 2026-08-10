<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Container;

use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
interface Subscriber_Interface
{
    /**
     * Register action/filter listeners to hook into WordPress
     *
     * @return void
     */
    public function register() : void;
}
