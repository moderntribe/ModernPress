<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing\Factories;

use Closure;
use Tribe\Alert_Scoped\DI\FactoryInterface;
use Tribe\Alert\Rule_Processing\Factory;
use Tribe\Alert\Rule_Processing\Processor;
/**
 * Abstract to create Processors based on a specific set of conditions.
 */
abstract class Factory_Handler implements Factory
{
    protected FactoryInterface $container;
    /**
     * Makes a Processor or otherwise passes to the next stage in the pipeline for processing.
     *
     * @param \Tribe\Alert\Rule_Processing\Processor|null $processor Which processor was selected, if any.
     * @param \Closure                                    $next      The next stage in the pipeline.
     * @param mixed[]                                     $rules     The ACF rule group data.
     */
    public abstract function make(?Processor $processor, Closure $next, array $rules) : ?Processor;
    public function __construct(FactoryInterface $container)
    {
        $this->container = $container;
    }
}
