<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Assets;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
class Assets_Definer implements Definer_Interface
{
    public function define() : array
    {
        return [Asset_Loader::class => DI\create()->constructor(DI\get('plugin.file'))];
    }
}
