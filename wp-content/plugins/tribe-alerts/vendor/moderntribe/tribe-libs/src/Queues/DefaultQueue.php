<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Queues;

use Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts\Queue;
class DefaultQueue extends Queue
{
    const NAME = 'default';
    public function get_name() : string
    {
        return self::NAME;
    }
}
