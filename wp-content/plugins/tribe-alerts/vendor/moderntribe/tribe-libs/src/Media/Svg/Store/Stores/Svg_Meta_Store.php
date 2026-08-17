<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store\Stores;

use Tribe\Alert_Scoped\enshrined\svgSanitize\Sanitizer;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store\Contracts\Svg_Store;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store\Svg_Parser_Factory;
/**
 * Stores SVG markup in WordPress attachment meta.
 */
class Svg_Meta_Store implements Svg_Store
{
    protected string $meta_key;
    protected Svg_Parser_Factory $parser_factory;
    protected Sanitizer $sanitizer;
    public function __construct(string $meta_key, Svg_Parser_Factory $parser_factory, Sanitizer $sanitizer)
    {
        $this->sanitizer = $sanitizer;
        $this->parser_factory = $parser_factory;
        $this->meta_key = $meta_key;
    }
    public function save(string $file, int $attachment_id) : bool
    {
        $svg = (string) $this->parser_factory->make($file);
        \delete_post_meta($attachment_id, $this->meta_key, $svg);
        return (bool) \update_post_meta($attachment_id, $this->meta_key, $svg);
    }
    public function get(int $attachment_id, bool $remove_xml_tag = \true) : string
    {
        $dirty = (string) \get_post_meta($attachment_id, $this->meta_key, \true);
        $this->sanitizer->removeXMLTag($remove_xml_tag);
        return \trim($this->sanitizer->sanitize($dirty));
    }
}
