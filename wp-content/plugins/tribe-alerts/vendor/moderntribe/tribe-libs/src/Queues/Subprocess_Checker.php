<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Queues;

/**
 * Checks if this system supports subprocess spawning.
 */
class Subprocess_Checker
{
    public function enabled() : bool
    {
        return \function_exists('proc_open') && \function_exists('proc_close');
    }
}
