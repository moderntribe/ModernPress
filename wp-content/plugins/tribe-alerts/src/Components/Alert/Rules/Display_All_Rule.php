<?php

declare (strict_types=1);
namespace Tribe\Alert\Components\Alert\Rules;

use Closure;
use Tribe\Alert\Components\Alert\Rule;
use Tribe\Alert\Meta\Alert_Meta;
class Display_All_Rule implements Rule
{
    /**
     * Show across the entire site.
     *
     * @inheritDoc
     */
    public function handle(bool $display, Closure $next, array $rules) : bool
    {
        $type = $rules[Alert_Meta::FIELD_RULES_DISPLAY_TYPE] ?? '';
        if ($type === Alert_Meta::OPTION_EVERY_PAGE) {
            return \true;
        }
        return $next($display);
    }
}
