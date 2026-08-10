<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Pipeline;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
use Tribe\Alert_Scoped\Tribe\Libs\Pipeline\Contracts\Pipeline as PipelineContract;
class Pipeline_Definer implements Definer_Interface
{
    public function define() : array
    {
        return [PipelineContract::class => DI\autowire(Pipeline::class)];
    }
}
