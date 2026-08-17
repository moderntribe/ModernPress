<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Queues\Tasks;

use Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts\Task;
class Noop implements Task
{
    public function handle(array $args) : bool
    {
        $success = \rand(0, 10);
        if ($success) {
            \Tribe\Alert_Scoped\WP_CLI::line('Noop task ' . $args['noop'] . ' processed');
            return \true;
        }
        \Tribe\Alert_Scoped\WP_CLI::line('Noop task failed, releasing ack');
        return \false;
    }
}
