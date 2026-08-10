<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Utils;

class Query_Utils
{
    /**
     * Gets a comma separated list of quoted elements ready to be used for
     * an `IN` clause like `... IN ($list)`.
     *
     * @param array $elements An array of mysql-escaped string variables to concatenate.
     *
     * @return string A comma separated list of single quoted strings.
     */
    public static function get_quoted_string_list(array $elements, string $quote = '\'') : string
    {
        return \implode(',', \array_map(function ($string) use($quote) {
            return $quote . \esc_sql($string) . $quote;
        }, $elements));
    }
    /**
     * Gets a comma separated list of numeric elements ready to be used for
     * an `IN` clause like `... IN ($list)`.
     *
     * @param int[] $elements An array of int variables to concatenate.
     *
     * @return string A comma separated list of int vars.
     */
    public static function get_int_list(array $elements) : string
    {
        return \implode(',', \array_map('intval', $elements));
    }
}
