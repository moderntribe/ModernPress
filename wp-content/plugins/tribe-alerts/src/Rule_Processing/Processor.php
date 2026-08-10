<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing;

/**
 * The Processor performs a very specific test to determine if the currently viewed
 * page passes the test based on the rules provided to it.
 */
interface Processor
{
    /**
     * Determine if this processor passes based on the rules provided to it.
     *
     * @param mixed[] $rules The ACF rule group data.
     */
    public function process(array $rules) : bool;
}
