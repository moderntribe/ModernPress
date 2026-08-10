<?php

declare (strict_types=1);
namespace Tribe\Alert\Settings;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
class Settings_Definer implements Definer_Interface
{
    public function define() : array
    {
        return [
            // add acf settings screens
            \Tribe\Alert_Scoped\Tribe\Libs\Settings\Settings_Definer::PAGES => DI\add([DI\get(\Tribe\Alert\Settings\Alert_Settings::class)]),
        ];
    }
}
