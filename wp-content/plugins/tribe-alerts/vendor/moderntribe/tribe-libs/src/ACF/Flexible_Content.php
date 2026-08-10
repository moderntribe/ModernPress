<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\ACF;

class Flexible_Content extends Field
{
    /** @var Layout[] */
    protected $layouts = [];
    public function __construct($key, $attributes = [])
    {
        parent::__construct($key, $attributes);
        $this->attributes['type'] = 'flexible_content';
    }
    public function add_layout(Layout $layout)
    {
        $this->layouts[] = $layout;
        return $this;
    }
    public function get_attributes()
    {
        $attributes = $this->attributes;
        $attributes['layouts'] = [];
        foreach ($this->layouts as $layout) {
            $attributes['layouts'][] = $layout->get_attributes();
        }
        return [$attributes];
    }
}
