<?php

declare (strict_types=1);
namespace Tribe\Alert\Components\Alert;

interface Color_Options_Manager
{
    /**
     * Returns multidimensional array of color options.
     *
     * The key is a six-digit hex color code in lowercase, e.g., #ffffff.
     * The value is a 2 elements array: color name and class name.
     *
     * @return array<string, array{name: string, class: string}>
     */
    public function get_all_options() : array;
    /**
     * Returns an array where the key is a hex color and the value is a color name.
     *
     * @return array<string, string>
     */
    public function get_acf_options() : array;
    /**
     * Returns css color class name.
     *
     * @param string $hex Six-digit hex color code in lowercase, e.g., #ffffff.
     */
    public function get_color_class(string $hex) : string;
}
