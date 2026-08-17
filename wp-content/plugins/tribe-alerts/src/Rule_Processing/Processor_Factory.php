<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing;

use Tribe\Alert_Scoped\Tribe\Libs\Pipeline\Contracts\Pipeline;
class Processor_Factory
{
    protected Pipeline $pipeline;
    public function __construct(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }
    /**
     * Get the correct Processor that we'll use based on which page the user is currently
     * viewing.
     *
     * @param mixed[] $rules The ACF rule group data.
     */
    public function get_processor(array $rules) : ?\Tribe\Alert\Rule_Processing\Processor
    {
        if (!$rules) {
            return null;
        }
        return $this->pipeline->via('make')->send(null, [$rules])->thenReturn();
    }
}
