<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\CLI;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
class CLI_Definer implements Definer_Interface
{
    public const COMMANDS = 'libs.cli.commands';
    public function define() : array
    {
        return [
            /**
             * Use \DI\add() from other definers to add commands. Any commands
             * thus added will be registered with WP_CLI.
             */
            self::COMMANDS => DI\add([]),
        ];
    }
}
