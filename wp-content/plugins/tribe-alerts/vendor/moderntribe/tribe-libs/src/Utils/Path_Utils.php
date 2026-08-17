<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Utils;

abstract class Path_Utils
{
    /**
     * Make a best-effort at extracting the file extension from a URL
     *
     * @param string $uri
     *
     * @return string
     */
    public static function file_extension($uri) : string
    {
        if (empty($uri)) {
            return '';
        }
        $path = \parse_url($uri, \PHP_URL_PATH);
        if (empty($path)) {
            return '';
        }
        return \pathinfo($path, \PATHINFO_EXTENSION);
    }
}
