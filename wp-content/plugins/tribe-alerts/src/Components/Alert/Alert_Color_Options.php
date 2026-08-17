<?php

declare (strict_types=1);
namespace Tribe\Alert\Components\Alert;

class Alert_Color_Options implements \Tribe\Alert\Components\Alert\Color_Options_Manager
{
    public const CSS_CLASS_PREFIX = 'tribe-alerts__theme';
    /**
     * @var array<string, array{name: string, class: string}>
     */
    protected array $color_options;
    public function __construct(array $color_options)
    {
        $this->color_options = $color_options;
    }
    public function get_all_options() : array
    {
        return $this->color_options;
    }
    /**
     * @return array<string, string>
     */
    public function get_acf_options() : array
    {
        return \wp_list_pluck($this->color_options, 'name');
    }
    /**
     * @param string $hex Six-digit hex color code in lowercase, e.g., #ffffff.
     */
    public function get_color_class(string $hex) : string
    {
        $color = $this->color_options[$hex] ?? '';
        if (!isset($color['class'])) {
            return '';
        }
        /**
         * Filter the css class prefix.
         *
         * @param string
         */
        $prefix = \apply_filters('tribe/alerts/color_options/css_class_prefix', self::CSS_CLASS_PREFIX);
        return \sanitize_html_class(\sprintf("{$prefix}-%s", $color['class']));
    }
}
