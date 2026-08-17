<?php

declare (strict_types=1);
namespace Tribe\Alert\Components\Alert\Rules;

use Closure;
use Tribe\Alert\Components\Alert\Rule;
use Tribe\Alert\Meta\Alert_Meta;
use Tribe\Alert\Rule_Processing\Processor_Factory;
class Included_Posts_Rule implements Rule
{
    protected Processor_Factory $processor_factory;
    public function __construct(Processor_Factory $processor_factory)
    {
        $this->processor_factory = $processor_factory;
    }
    /**
     * Show only on these posts/pages/archives.
     *
     * @inheritDoc
     */
    public function handle(bool $display, Closure $next, array $rules) : bool
    {
        $type = $rules[Alert_Meta::FIELD_RULES_DISPLAY_TYPE] ?? '';
        if ($type === Alert_Meta::OPTION_INCLUDE) {
            $processor = $this->processor_factory->get_processor($rules);
            // No processor found, do not include this page.
            if (!$processor) {
                return \false;
            }
            return $processor->process($rules);
        }
        return $next($display);
    }
}
