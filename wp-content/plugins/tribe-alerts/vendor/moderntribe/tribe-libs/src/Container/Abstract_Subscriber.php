<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Container;

use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
abstract class Abstract_Subscriber implements Subscriber_Interface
{
    /**
     * @var ContainerInterface
     */
    protected $container;
    /**
     * Abstract_Subscriber constructor.
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
}
