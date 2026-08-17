<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\ACF;

use Tribe\Alert_Scoped\Tribe\Libs\ACF\Contracts\Has_Sub_Fields;
use Tribe\Alert_Scoped\Tribe\Libs\ACF\Traits\With_Sub_Fields;
class Repeater extends Field implements ACF_Aggregate, Has_Sub_Fields
{
    use With_Sub_Fields;
    protected $key_prefix = 'field';
    public function __construct($key, $attributes = [])
    {
        parent::__construct($key, $attributes);
        $this->attributes['type'] = 'repeater';
    }
    public function get_attributes()
    {
        $attributes = $this->attributes;
        $attributes['sub_fields'] = $this->get_sub_field_attributes();
        return [$attributes];
    }
}
