<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\ACF;

class Block
{
    /**
     * @var array
     */
    protected $attributes;
    /**
     * Block constructor.
     *
     * @param string $name
     * @param array  $attributes
     */
    public function __construct($name, $attributes = [])
    {
        $this->attributes = $attributes;
        $this->attributes['name'] = $name;
    }
    /**
     * @param string $key
     * @param mixed $value
     */
    public function set($key, $value)
    {
        $this->attributes[$key] = $value;
    }
    /**
     * @param array $attributes
     */
    public function set_attributes(array $attributes)
    {
        $this->attributes = $attributes;
    }
    /**
     * @return array
     */
    public function get_attributes() : array
    {
        return $this->attributes;
    }
}
