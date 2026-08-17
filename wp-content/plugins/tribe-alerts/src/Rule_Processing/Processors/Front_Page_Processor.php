<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing\Processors;

use Tribe\Alert\Meta\Alert_Meta;
use Tribe\Alert\Rule_Processing\Processor;
/**
 * Determine if front page rule processing is enabled for the active Alert.
 */
class Front_Page_Processor implements Processor
{
    public function process(array $rules) : bool
    {
        return (bool) ($rules[Alert_Meta::FIELD_RULES_APPLY_TO_FRONT_PAGE] ?? \false);
    }
}
