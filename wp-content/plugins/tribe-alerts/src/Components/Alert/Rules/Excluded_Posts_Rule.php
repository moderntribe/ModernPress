<?php

declare (strict_types=1);
namespace Tribe\Alert\Components\Alert\Rules;

use Closure;
use Tribe\Alert\Components\Alert\Rule;
use Tribe\Alert\Meta\Alert_Meta;
use Tribe\Alert\Rule_Processing\Processor_Factory;
class Excluded_Posts_Rule implements Rule
{
    protected Processor_Factory $processor_factory;
    public function __construct(Processor_Factory $processor_factory)
    {
        $this->processor_factory = $processor_factory;
    }
    /**
     * Show on every post/page/archive besides the ones included here.
     *
     * @inheritDoc
     */
    public function handle(bool $display, Closure $next, array $rules) : bool
    {
        $type = $rules[Alert_Meta::FIELD_RULES_DISPLAY_TYPE] ?? '';
        if ($type === Alert_Meta::OPTION_EXCLUDE) {
            $processor = $this->processor_factory->get_processor($rules);
            // No processor found, assume this page is not excluded.
            if (!$processor) {
                return \false;
            }
            // Note the negation here, since we're excluding.
            return !$processor->process($rules);
        }
        return $next($display);
    }
}
