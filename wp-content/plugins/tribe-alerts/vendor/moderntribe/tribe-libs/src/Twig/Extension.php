<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Twig;

use Tribe\Alert_Scoped\Twig\Extension\AbstractExtension;
use Tribe\Alert_Scoped\Twig\TwigFilter;
use Tribe\Alert_Scoped\Twig\TwigFunction;
class Extension extends AbstractExtension
{
    public function getFilters()
    {
        return \apply_filters('tribe/libs/twig/filters', [new TwigFilter('strip_shortcodes', 'strip_shortcodes'), new TwigFilter('wp_trim_words', 'wp_trim_words'), new TwigFilter('sanitize_email', 'sanitize_email'), new TwigFilter('sanitize_html_class', 'sanitize_html_class'), new TwigFilter('sanitize_title', 'sanitize_title'), new TwigFilter('wpautop', 'wpautop'), new TwigFilter('apply_filters', static function ($tag, $args) {
            return \apply_filters_ref_array($tag, $args);
        }, ['is_variadic' => \true]), new TwigFilter('esc_url', 'esc_url'), new TwigFilter('esc_attr', 'esc_attr'), new TwigFilter('esc_html', 'esc_html'), new TwigFilter('esc_js', 'esc_js'), new TwigFilter('tag_escape', 'tag_escape')]);
    }
    public function getFunctions()
    {
        return \apply_filters('tribe/libs/twig/funcitons', [new TwigFunction('do_action', 'do_action'), new TwigFunction('do_shortcode', 'do_shortcode'), new TwigFunction('__', static function ($string) {
            return $string;
            // for multilingual projects, use: return __( $string, 'tribe' );
        })]);
    }
}
