<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Whoops;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
use Tribe\Alert_Scoped\Whoops\Handler\PrettyPageHandler;
class Whoops_Definer implements Definer_Interface
{
    public function define() : array
    {
        return [\Tribe\Alert_Scoped\Whoops\Run::class => DI\autowire()->constructor(null)->method('pushHandler', DI\get(PrettyPageHandler::class))];
    }
}
