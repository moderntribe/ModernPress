<?php

namespace Tribe\Alert_Scoped\League\Plates\Extension;

use Tribe\Alert_Scoped\League\Plates\Engine;
/**
 * A common interface for extensions.
 */
interface ExtensionInterface
{
    public function register(Engine $engine);
}
