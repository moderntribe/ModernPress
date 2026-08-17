<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Nav;

class JSON_Nav_Walker extends Object_Nav_Walker
{
    /**
     * Translate the menu items to JSON.
     *
     * @param array $menu_items
     * @return string
     */
    protected function format_output($menu_items)
    {
        $output = parent::format_output($menu_items);
        return \wp_json_encode($output);
    }
}
