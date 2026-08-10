<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\ACF;

use Tribe\Alert_Scoped\Tribe\Libs\ACF\Contracts\Has_Sub_Fields;
use Tribe\Alert_Scoped\Tribe\Libs\ACF\Traits\With_Sub_Fields;
class Field_Section extends Field implements ACF_Aggregate, Has_Sub_Fields
{
    use With_Sub_Fields;
    /**
     * @var string
     */
    protected $key_prefix = 'section_';
    /**
     * Field_Section constructor.
     *
     * @param string $name
     * @param string $label
     * @param string $type
     */
    public function __construct(string $name, string $label, string $type)
    {
        parent::__construct($name, ['type' => $type, 'label' => $label, 'name' => $name]);
    }
    /**
     * @return array[]
     */
    public function get_attributes()
    {
        return \array_merge([$this->attributes], $this->get_sub_field_attributes());
    }
}
