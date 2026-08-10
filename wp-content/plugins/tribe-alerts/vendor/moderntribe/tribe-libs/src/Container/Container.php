<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Container;

use Tribe\Alert_Scoped\DI\FactoryInterface;
use Tribe\Alert_Scoped\Invoker\InvokerInterface;
use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
use ReflectionObject;
use ReflectionProperty;
/**
 * A mutable container wrapper around PHP-DI.
 */
class Container extends \Tribe\Alert_Scoped\DI\Container implements MutableContainer, ContainerInterface, FactoryInterface, InvokerInterface
{
    /**
     * A reflection of the wrapped container.
     */
    protected ?ReflectionObject $reflectionContainer = null;
    /**
     * Wrap an existing container and create a new instance of this container.
     *
     * Items in the wrapped container are still resolved in this container.
     *
     * @return MutableContainer|\DI\Container|ContainerInterface|FactoryInterface|InvokerInterface
     */
    public function wrap(?ContainerInterface $container = null)
    {
        if ($container) {
            $container = clone $container;
        }
        $this->delegateContainer = $container ?: $this;
        $object = new self([], null, $container);
        $object->resolvedEntries = $this->resetEntries();
        return $object->setReflectionContainer($container);
    }
    /**
     * Make a completely fresh object, including all of its dependencies.
     *
     * @template T
     *
     * @param class-string<T> $name Entry name or a class name.
     * @param array           $parameters Optional parameters to use to build the entry.
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \ReflectionException
     *
     * @return mixed|T
     */
    public function makeFresh(string $name, array $parameters = [])
    {
        return $this->flush()->make($name, $parameters);
    }
    /**
     * Flush the container of all bindings and resolved instances in this
     * container and the wrapped container.
     *
     * @throws \ReflectionException
     *
     * @return MutableContainer|\DI\Container|ContainerInterface|FactoryInterface|InvokerInterface
     */
    public function flush()
    {
        $this->resetEntries();
        if ($this->reflectionContainer === null) {
            return $this;
        }
        $resolved_entries = $this->reflectionContainer->getProperty('resolvedEntries');
        $this->set_property_value($resolved_entries, $this->delegateContainer, []);
        return $this;
    }
    /**
     * Reset resolved entries to the default.
     *
     * @return array<string, mixed>
     */
    protected function resetEntries() : array
    {
        return $this->resolvedEntries = [MutableContainer::class => $this];
    }
    /**
     * Create a reflection object of the wrapped container.
     *
     * @return MutableContainer|\DI\Container|ContainerInterface|FactoryInterface|InvokerInterface
     */
    protected function setReflectionContainer(?ContainerInterface $container = null)
    {
        $this->reflectionContainer = $container ? new ReflectionObject($container) : null;
        return $this;
    }
    /**
     * @param mixed $object
     * @param mixed $value
     */
    private function set_property_value(ReflectionProperty $property, $object, $value) : void
    {
        // Required for older PHP-DI property visibility; no-op on public props in PHP 8.1+.
        if (!$property->isPublic()) {
            $property->setAccessible(\true);
        }
        $property->setValue($object, $value);
    }
}
