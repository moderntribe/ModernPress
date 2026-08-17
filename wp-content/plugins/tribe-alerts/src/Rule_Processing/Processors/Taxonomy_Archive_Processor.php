<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing\Processors;

use Tribe\Alert\Meta\Alert_Meta;
use Tribe\Alert\Rule_Processing\Processor;
class Taxonomy_Archive_Processor implements Processor
{
    public function process(array $rules) : bool
    {
        $taxonomies = $rules[Alert_Meta::FIELD_TAXONOMY_ARCHIVES] ?? [];
        if (!$taxonomies) {
            return \false;
        }
        if (\is_category() && \in_array('category', $taxonomies)) {
            return \true;
        }
        if (\is_tag() && \in_array('post_tag', $taxonomies)) {
            return \true;
        }
        return \is_tax($taxonomies);
    }
}
