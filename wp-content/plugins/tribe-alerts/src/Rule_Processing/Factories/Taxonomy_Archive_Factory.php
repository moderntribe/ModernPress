<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing\Factories;

use Closure;
use Tribe\Alert\Rule_Processing\Processor;
use Tribe\Alert\Rule_Processing\Processors\Taxonomy_Archive_Processor;
class Taxonomy_Archive_Factory extends \Tribe\Alert\Rule_Processing\Factories\Factory_Handler
{
    public function make(?Processor $processor, Closure $next, array $rules) : ?Processor
    {
        // Careful to note that if viewing a Post Type Archive, is_archive() returns true,
        // We must ensure we exclude Post Type Archives, so they can be created by the correct
        // Factory further down the Pipeline.
        if (!\is_archive() || \is_post_type_archive()) {
            return $next($processor);
        }
        return $this->container->make(Taxonomy_Archive_Processor::class);
    }
}
