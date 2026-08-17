<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Pipeline;

use Closure;
use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Tribe\Libs\Pipeline\Contracts\Pipeline as PipelineContract;
/**
 * A remake of Laravel's illuminate/pipeline, customized
 * to support additional parameters and PHP-DI.
 *
 * @link https://github.com/illuminate/pipeline
 */
class Pipeline implements PipelineContract
{
    /**
     * The container implementation.
     *
     * @var \DI\FactoryInterface
     */
    protected $container;
    /**
     * The object being passed through the pipeline.
     *
     * @var mixed
     */
    protected $passable;
    /**
     * Additional array of parameters to pass to the handle method.
     *
     * @var mixed[]
     */
    protected $parameters;
    /**
     * The array of class pipes.
     *
     * @var mixed[]
     */
    protected $pipes = [];
    /**
     * The method to call on each pipe.
     *
     * @var string
     */
    protected $method = 'handle';
    /**
     * Create a new class instance.
     *
     * @param  \DI\FactoryInterface $container
     */
    public function __construct(DI\FactoryInterface $container)
    {
        $this->container = $container;
    }
    /**
     * Set the object being sent through the pipeline.
     *
     * @param mixed $passable
     * @param array $parameters
     *
     * @return $this
     */
    public function send($passable, array $parameters = []) : PipelineContract
    {
        $this->passable = $passable;
        $this->parameters = $parameters;
        return $this;
    }
    /**
     * Set the array of pipes.
     *
     * @param  array|mixed  $pipes
     *
     * @return $this
     */
    public function through($pipes) : PipelineContract
    {
        $this->pipes = \is_array($pipes) ? $pipes : \func_get_args();
        return $this;
    }
    /**
     * Set the method to call on the pipes.
     *
     * @param  string  $method
     *
     * @return $this
     */
    public function via(string $method) : PipelineContract
    {
        $this->method = $method;
        return $this;
    }
    /**
     * Run the pipeline with a final destination callback.
     *
     * @param  \Closure  $destination
     *
     * @return mixed
     */
    public function then(Closure $destination)
    {
        $pipeline = \array_reduce(\array_reverse($this->pipes()), $this->carry(), $this->prepareDestination($destination));
        return $pipeline($this->passable);
    }
    /**
     * Run the pipeline and return the result.
     *
     * @return mixed
     */
    public function thenReturn()
    {
        return $this->then(static function ($passable) {
            return $passable;
        });
    }
    /**
     * Set the container instance.
     *
     * @param  \DI\FactoryInterface $container
     *
     * @return $this
     */
    public function setContainer(DI\FactoryInterface $container) : PipelineContract
    {
        $this->container = $container;
        return $this;
    }
    /**
     * Get the final piece of the Closure onion.
     *
     * @param  \Closure  $destination
     *
     * @return \Closure
     */
    protected function prepareDestination(Closure $destination) : Closure
    {
        return function ($passable) use($destination) {
            return $destination($passable);
        };
    }
    /**
     * Get a Closure that represents a slice of the application onion.
     *
     * @return \Closure
     */
    protected function carry() : Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use($stack, $pipe) {
                // Merge default parameters with additional parameters
                $parameters = \array_merge([$passable, $stack], $this->parameters);
                if (\is_callable($pipe)) {
                    // If the pipe is a callable, then we will call it directly, but otherwise we
                    // will resolve the pipes out of the dependency container and call it with
                    // the appropriate method and arguments, returning the results back out.
                    return $pipe(...$parameters);
                }
                if (!\is_object($pipe)) {
                    // If the pipe is a string we will parse the string and resolve the class out
                    // of the dependency injection container. We can then build a callable and
                    // execute the pipe function giving in the parameters that are required.
                    $pipe = $this->getContainer()->make($pipe);
                }
                $carry = \method_exists($pipe, $this->method) ? $pipe->{$this->method}(...$parameters) : $pipe(...$parameters);
                return $this->handleCarry($carry);
            };
        };
    }
    /**
     * Get the array of configured pipes.
     *
     * @return array
     */
    protected function pipes() : array
    {
        return $this->pipes;
    }
    /**
     * Get the container instance.
     *
     * @return \DI\FactoryInterface
     */
    protected function getContainer() : DI\FactoryInterface
    {
        return $this->container;
    }
    /**
     * Handle the value returned from each pipe before passing it to the next.
     *
     * @param  mixed  $carry
     *
     * @return mixed
     */
    protected function handleCarry($carry)
    {
        return $carry;
    }
}
