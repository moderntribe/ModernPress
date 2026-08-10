<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Container;

use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
interface MutableContainer
{
    /**
     * Wrap an existing container and create a new instance of this container.
     *
     * Items in the wrapped container are still resolved in this container.
     *
     * @param  \Psr\Container\ContainerInterface|null  $container
     *
     * @return \Tribe\Libs\Container\MutableContainer|\DI\Container|ContainerInterface|\DI\FactoryInterface|\Invoker\InvokerInterface
     */
    public function wrap(?ContainerInterface $container = null);
    /**
     * Make a completely fresh object, including all of its dependencies.
     *
     * @template T
     *
     * @param  class-string<T>  $name               Entry name or a class name.
     * @param  array            $parameters         Optional parameters to use to build the entry. Use this to force
     *                                              specific parameters to specific values. Parameters not defined in this
     *                                              array will be resolved using the container.
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \ReflectionException
     *
     * @return mixed|T
     */
    public function makeFresh(string $name, array $parameters = []);
    /**
     * Flush the container of all bindings and resolved instances in this
     * container and the wrapped container.
     *
     * @return \Tribe\Libs\Container\MutableContainer|\DI\Container|ContainerInterface|\DI\FactoryInterface|\Invoker\InvokerInterface
     */
    public function flush();
}
