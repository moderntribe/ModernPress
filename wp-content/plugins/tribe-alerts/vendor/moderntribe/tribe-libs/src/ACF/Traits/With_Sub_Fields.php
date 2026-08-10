<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\ACF\Traits;

use Tribe\Alert_Scoped\Tribe\Libs\ACF\ACF_Configuration;
use Tribe\Alert_Scoped\Tribe\Libs\ACF\Field;
/**
 * @mixin Field
 * @mixin \Tribe\Libs\ACF\Contracts\Has_Sub_Fields
 */
trait With_Sub_Fields
{
    /** @var \Tribe\Libs\ACF\Field[] */
    protected $fields = [];
    /**
     * @param Field $field
     *
     * @return static
     */
    public function add_field(Field $field)
    {
        $this->fields[] = $field;
        return $this;
    }
    /**
     * @return \Tribe\Libs\ACF\Field[]
     */
    public function get_fields() : array
    {
        return $this->fields;
    }
    /**
     * Get the attributes of all subfields.
     *
     * @return array[]
     */
    public function get_sub_field_attributes() : array
    {
        return \array_merge([], ...\array_map(static function (ACF_Configuration $field) {
            return $field->get_attributes();
        }, $this->fields));
    }
}
