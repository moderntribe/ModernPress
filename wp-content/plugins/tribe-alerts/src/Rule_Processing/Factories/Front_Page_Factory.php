<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing\Factories;

use Closure;
use Tribe\Alert\Rule_Processing\Processor;
use Tribe\Alert\Rule_Processing\Processors\Front_Page_Processor;
class Front_Page_Factory extends \Tribe\Alert\Rule_Processing\Factories\Factory_Handler
{
    public function make(?Processor $processor, Closure $next, array $rules) : ?Processor
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        // Not the home page, or we're on the search results. Pass to next stage.
        if (\is_search() || \strtok($uri, '?') !== '/') {
            return $next($processor);
        }
        return $this->container->make(Front_Page_Processor::class);
    }
}
