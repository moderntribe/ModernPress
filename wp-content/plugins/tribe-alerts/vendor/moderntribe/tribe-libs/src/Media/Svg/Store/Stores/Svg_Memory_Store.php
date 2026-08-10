<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store\Stores;

use Tribe\Alert_Scoped\enshrined\svgSanitize\Sanitizer;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store\Contracts\Svg_Store;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store\Svg_Parser_Factory;
/**
 * Stores SVG markup in memory for as long as the request lives.
 */
class Svg_Memory_Store implements Svg_Store
{
    protected Svg_Parser_Factory $parser_factory;
    protected Sanitizer $sanitizer;
    protected array $items = [];
    public function __construct(Svg_Parser_Factory $parser_factory, Sanitizer $sanitizer, array $items = [])
    {
        $this->items = $items;
        $this->sanitizer = $sanitizer;
        $this->parser_factory = $parser_factory;
    }
    public function save(string $file, int $attachment_id) : bool
    {
        $this->items[$attachment_id] = (string) $this->parser_factory->make($file);
        return \true;
    }
    public function get(int $attachment_id, bool $remove_xml_tag = \true) : string
    {
        $dirty = $this->items[$attachment_id] ?? '';
        $this->sanitizer->removeXMLTag($remove_xml_tag);
        return \trim($this->sanitizer->sanitize($dirty));
    }
}
