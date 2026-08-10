<?php

declare (strict_types=1);
namespace Tribe\Alert\Activation;

/**
 * Implement for activators/deactivators.
 *
 * @package Tribe\Alert
 */
interface Operable
{
    public const OPTION_NAME = 'tribe_alert';
    /**
     * @param  bool  $network_wide Pass via WordPress if this is a network activation
     */
    public function __invoke(bool $network_wide = \false) : void;
}
