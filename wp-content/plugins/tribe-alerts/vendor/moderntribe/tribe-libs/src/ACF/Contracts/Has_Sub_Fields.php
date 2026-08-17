<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\ACF\Contracts;

use Tribe\Alert_Scoped\Tribe\Libs\ACF\Field;
/**
 * Used in combination with the subfield trait.
 *
 * @see \Tribe\Libs\ACF\Traits\With_Sub_Fields
 */
interface Has_Sub_Fields
{
    /**
     * Add a field to a field type that supports subfields.
     *
     * @param  \Tribe\Libs\ACF\Field  $field
     *
     * @return static
     */
    public function add_field(Field $field);
    /**
     * @return \Tribe\Libs\ACF\Field[]
     */
    public function get_fields() : array;
    /**
     * @return array[]
     */
    public function get_sub_field_attributes() : array;
}
