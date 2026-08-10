<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store\Contracts;

interface Svg_Store
{
    /**
     * Store the markup of an SVG.
     *
     * @param string $file          The server path to the file.
     * @param int    $attachment_id The attachment/post ID.
     *
     * @return bool Whether we were able to save.
     */
    public function save(string $file, int $attachment_id) : bool;
    /**
     * Fetch the sanitized markup of an SVG.
     *
     * @param  int   $attachment_id  The attachment/post ID.
     * @param  bool  $remove_xml_tag Strip XML from SVG markup.
     *
     * @return string The sanitized markup.
     */
    public function get(int $attachment_id, bool $remove_xml_tag = \true) : string;
}
