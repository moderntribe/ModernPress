<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Queues\Contracts;

interface Task
{
    public function handle(array $args) : bool;
}
