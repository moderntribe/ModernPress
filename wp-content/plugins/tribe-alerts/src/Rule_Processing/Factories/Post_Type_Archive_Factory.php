<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing\Factories;

use Closure;
use Tribe\Alert\Rule_Processing\Processor;
use Tribe\Alert\Rule_Processing\Processors\Post_Type_Archive_Processor;
class Post_Type_Archive_Factory extends \Tribe\Alert\Rule_Processing\Factories\Factory_Handler
{
    public function make(?Processor $processor, Closure $next, array $rules) : ?Processor
    {
        // Note that the Taxonomy Archive Factory has a specific check for Post Type Archives.
        if (!\is_post_type_archive()) {
            return $next($processor);
        }
        return $this->container->make(Post_Type_Archive_Processor::class);
    }
}
