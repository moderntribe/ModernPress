<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\CLI;

use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
class CLI_Subscriber extends Abstract_Subscriber
{
    public function register() : void
    {
        $this->commands();
    }
    protected function commands() : void
    {
        \add_action('init', function () {
            if (!\defined('WP_CLI') || !WP_CLI) {
                return;
            }
            foreach ($this->container->get(CLI_Definer::COMMANDS) as $command) {
                if ($command instanceof Command_Interface) {
                    $command->register();
                }
            }
        }, 0, 0);
    }
}
