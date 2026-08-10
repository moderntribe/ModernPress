<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\CLI;

interface Command_Interface
{
    /**
     * Register the command with WP-CLI
     *
     * @return void
     * @see \WP_CLI::add_command()
     */
    public function register();
    /**
     * Runs the command
     *
     * @param array $args       Positional arguments from the command line
     * @param array $assoc_args Associative arguments and flags from the command line
     *
     * @return void
     */
    public function run_command($args, $assoc_args);
}
