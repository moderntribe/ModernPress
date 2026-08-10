<?php

declare (strict_types=1);
namespace Tribe\Alert\Activation;

/**
 * Invoked during plugin deactivation.
 *
 * @package Tribe\Alert\Activation
 */
class Deactivator implements \Tribe\Alert\Activation\Operable
{
    public function __invoke(bool $network_wide = \false) : void
    {
        \delete_option(self::OPTION_NAME);
    }
}
