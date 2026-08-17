<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\ACF\Traits;

/**
 * @mixin \Tribe\Project\Block_Middleware\Contracts\Abstract_Field_Middleware
 */
trait With_Field_Finder
{
    /**
     * Find a field in a sea of recursive fields. Keys are most often prefixed with "field_", but depending on the
     * type it could be "group_", "section_", "layout" etc. You must pass the full ACF key to find, including the prefix.
     *
     * @note This is a recursive method!
     *
     * @param  \Tribe\Libs\ACF\Field[]|\Tribe\Libs\ACF\Contracts\Has_Sub_Fields[]  $fields  The fields to search.
     * @param  string                                                              $key     The prefixed key to search for.
     *
     * @return \Tribe\Libs\ACF\Field|\Tribe\Libs\ACF\Contracts\Has_Sub_Fields|null
     */
    protected function find_field(array $fields, string $key)
    {
        $subfields = [];
        // Check all top level fields first
        foreach ($fields as $field) {
            $field_key = $field->get('key');
            if ($field_key === $key) {
                return $field;
            }
            // Collect any subfields to process after
            $subfields = \array_merge($subfields, \method_exists($field, 'get_fields') ? $field->get_fields() : (array) $field->get('fields'));
        }
        if ($subfields) {
            return $this->find_field($subfields, $key);
        }
        return null;
    }
}
