<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Container;

use Tribe\Alert_Scoped\DI;
interface Definer_Interface
{
    public function define() : array;
}
