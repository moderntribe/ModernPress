<?php

declare (strict_types=1);
namespace Tribe\Alert\Activation;

use Tribe\Alert\Core;
/**
 * Invoked during plugin activation.
 *
 * @package Tribe\Alert\Activation
 */
class Activator implements \Tribe\Alert\Activation\Operable
{
    public function __invoke(bool $network_wide = \false) : void
    {
        \update_option(self::OPTION_NAME, Core::VERSION);
    }
}
