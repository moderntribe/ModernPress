<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Queues_Mysql;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Tribe\Libs\CLI\CLI_Definer;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
use Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts\Backend;
use Tribe\Alert_Scoped\Tribe\Libs\Queues_Mysql\Backends\MySQL;
use Tribe\Alert_Scoped\Tribe\Libs\Queues_Mysql\CLI\MySQL_Table;
class Mysql_Backend_Definer implements Definer_Interface
{
    public function define() : array
    {
        return [
            Backend::class => DI\get(MySQL::class),
            /**
             * Add commands for the CLI subscriber to register
             */
            CLI_Definer::COMMANDS => DI\add([DI\get(MySQL_Table::class)]),
        ];
    }
}
