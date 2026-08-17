<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Utils;

class Markup_Utils
{
    public static function concat_attrs(?array $attrs = null, $prefix = '')
    {
        if (empty($attrs)) {
            return '';
        }
        $out = [];
        $prefix = empty($prefix) ? '' : \rtrim($prefix, '-') . '-';
        foreach ($attrs as $key => $value) {
            if (\is_array($value)) {
                $out[] = self::concat_attrs($value, $key);
            } else {
                $out[] = \sprintf('%s="%s"', $prefix . $key, \esc_attr($value));
            }
        }
        return \implode(' ', $out);
    }
    /**
     * Convert an array into an HTML class attribute string
     *
     * @param array $classes
     * @param bool  $attribute
     *
     * @return string
     */
    public static function class_attribute($classes, $attribute = \true)
    {
        if (empty($classes)) {
            return '';
        }
        $classes = \array_unique(\array_map('sanitize_html_class', $classes));
        return \sprintf('%s%s%s', $attribute ? ' class="' : '', \implode(' ', $classes), $attribute ? '"' : '');
    }
    /**
     * Truncate an HTML block to the given number of words. Cleanly strips out
     * shortcodes to avoid truncating in the middle of one.
     *
     * @param string      $html  The HTML to truncate
     * @param int         $words The number of words to keep
     * @param string|null $more  What to append if the text is truncated. Defaults to &hellip; if null
     * @param bool        $autop Whether to apply wpautop to the output
     *
     * @return string
     */
    public static function truncate_html(string $html = '', int $words = 55, ?string $more = null, bool $autop = \true) : string
    {
        $result = \wp_trim_words(\strip_shortcodes($html), $words, $more);
        if ($autop) {
            $result = \wpautop($result);
        }
        return $result;
    }
}
