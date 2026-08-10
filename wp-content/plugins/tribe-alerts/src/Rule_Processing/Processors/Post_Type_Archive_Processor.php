<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing\Processors;

use Tribe\Alert\Meta\Alert_Meta;
use Tribe\Alert\Rule_Processing\Processor;
/**
 * Determine if the currently viewed Post Type Archive is configured
 * for the active Alert.
 */
class Post_Type_Archive_Processor implements Processor
{
    public function process(array $rules) : bool
    {
        $post_types = $rules[Alert_Meta::FIELD_POST_TYPE_ARCHIVES] ?? [];
        if (!$post_types) {
            return \false;
        }
        return \is_post_type_archive($post_types);
    }
}
