<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing\Factories;

use Closure;
use Tribe\Alert_Scoped\DI\FactoryInterface;
use Tribe\Alert\Meta\Alert_Meta;
use Tribe\Alert\Post_Fetcher\Post_Fetcher;
use Tribe\Alert\Rule_Processing\Processor;
use Tribe\Alert\Rule_Processing\Processors\Excluded_Post_Processor;
use Tribe\Alert\Rule_Processing\Processors\Included_Post_Processor;
class Post_Factory extends \Tribe\Alert\Rule_Processing\Factories\Factory_Handler
{
    protected Post_Fetcher $post_fetcher;
    public function __construct(FactoryInterface $container, Post_Fetcher $post_fetcher)
    {
        parent::__construct($container);
        $this->post_fetcher = $post_fetcher;
    }
    /**
     * Make the correct included/exclude post processor, if appropriate.
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function make(?Processor $processor, Closure $next, array $rules) : ?Processor
    {
        $post = $this->post_fetcher->get_post();
        // No post found, pass to next stage.
        if (!isset($post->ID)) {
            return $next($processor);
        }
        $type = $rules[Alert_Meta::FIELD_RULES_DISPLAY_TYPE] ?? '';
        if ($type === Alert_Meta::OPTION_INCLUDE) {
            return $this->container->make(Included_Post_Processor::class);
        }
        if ($type === Alert_Meta::OPTION_EXCLUDE) {
            return $this->container->make(Excluded_Post_Processor::class);
        }
        return null;
    }
}
