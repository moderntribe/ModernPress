<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Queues_Mysql\CLI;

use Tribe\Alert_Scoped\Tribe\Libs\CLI\Command;
use Tribe\Alert_Scoped\Tribe\Libs\Queues_Mysql\Backends\MySQL;
class MySQL_Table extends Command
{
    /**
     * @var MySQL
     */
    protected $backend;
    public function __construct(MySQL $backend)
    {
        $this->backend = $backend;
        parent::__construct();
    }
    public function command()
    {
        return 'queues add-table';
    }
    public function arguments()
    {
        return [];
    }
    public function description()
    {
        return \__('Adds the required MySQL table.', 'tribe');
    }
    public function run_command($args, $assoc_args)
    {
        if ('Tribe\\Libs\\Queues_Mysql\\Backends\\MySQL' !== \get_class($this->backend)) {
            \Tribe\Alert_Scoped\WP_CLI::error(\__('You cannot add a table a non-MySQL backend'));
        }
        if ($this->backend->table_exists()) {
            \Tribe\Alert_Scoped\WP_CLI::success(\__('Task table already exists.', 'tribe'));
            return;
        }
        \do_action('tribe/project/queues/mysql/init_table');
        \Tribe\Alert_Scoped\WP_CLI::success(\__('Task table successfully created.', 'tribe'));
    }
}
