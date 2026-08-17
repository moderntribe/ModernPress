<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
use Tribe\Alert\Post_Fetcher\Post_Fetcher;
use Tribe\Alert\Post_Fetcher\Post_Fetcher_Factory;
use Tribe\Alert\Rule_Processing\Factories\Front_Page_Factory;
use Tribe\Alert\Rule_Processing\Factories\Post_Factory;
use Tribe\Alert\Rule_Processing\Factories\Post_Type_Archive_Factory;
use Tribe\Alert\Rule_Processing\Factories\Taxonomy_Archive_Factory;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
use Tribe\Alert_Scoped\Tribe\Libs\Pipeline\Contracts\Pipeline;
class Rule_Processor_Definer implements Definer_Interface
{
    public function define() : array
    {
        return [Post_Fetcher::class => static fn(ContainerInterface $c) => $c->get(Post_Fetcher_Factory::class)->make(), \Tribe\Alert\Rule_Processing\Processor_Factory::class => DI\autowire()->constructorParameter('pipeline', static function (DI\FactoryInterface $c) {
            $pipeline = $c->make(Pipeline::class);
            // Pass the different page detectors through the pipeline, the order matters!
            return $pipeline->through([$c->get(Front_Page_Factory::class), $c->get(Taxonomy_Archive_Factory::class), $c->get(Post_Type_Archive_Factory::class), $c->get(Post_Factory::class)]);
        })];
    }
}
