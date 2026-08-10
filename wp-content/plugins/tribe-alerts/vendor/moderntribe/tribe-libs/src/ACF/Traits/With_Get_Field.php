<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\ACF\Traits;

trait With_Get_Field
{
    /**
     * Retrieve data from an ACF field.
     *
     * Check to support nullable type properties in components.
     * ACF will in some cases return and empty string when we may want it to be null.
     * This allows us to always determine the default.
     *
     * @param  int|string  $key         ACF key identifier.
     * @param  mixed       $default     The default value if the field doesn't exist.
     * @param  mixed       $identifier  The ACF Identifier, e.g. post_id.
     *
     * @return mixed
     */
    public function get($key, $default = \false, $identifier = \false)
    {
        $value = \get_field($key, $identifier);
        return !empty($value) ? $value : $default;
    }
}
