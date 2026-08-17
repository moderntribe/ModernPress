<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store;

use DOMDocument;
use RuntimeException;
use Stringable;
class Svg_Parser implements Stringable
{
    protected string $svg;
    protected DOMDocument $xml_document;
    public function __construct(DOMDocument $xml_document)
    {
        $this->xml_document = $xml_document;
    }
    /**
     * Load an SVG from file.
     *
     * @param string $file_path The server path to the SVG file.
     */
    public function load_file(string $file_path) : self
    {
        if (!\is_readable($file_path)) {
            throw new RuntimeException(\sprintf('Unable to read file: %s', $file_path));
        }
        $svg = \file_get_contents($file_path);
        if ($svg === \false) {
            throw new RuntimeException(\sprintf('Unable to get contents of file: %s', $file_path));
        }
        if ($this->is_gzipped($svg)) {
            $svg = \gzdecode($svg);
            if ($svg === \false) {
                throw new RuntimeException(\sprintf('Unable to gzip decode file: %s', $file_path));
            }
        }
        $this->svg = $svg;
        return $this;
    }
    /**
     * Load an SVG from XML markup.
     *
     * @param string $svg The SVG XML.
     */
    public function load_string(string $svg) : self
    {
        if ($this->is_gzipped($svg)) {
            $decoded = \gzdecode($svg);
            if ($decoded === \false) {
                throw new RuntimeException(\sprintf('Unable to gzip decode SVG: %s', $svg));
            }
            $svg = $decoded;
        }
        $this->svg = $svg;
        return $this;
    }
    /**
     * Convert the SVG to a DOMDocument object so it can
     * be manipulated.
     */
    public function toDom() : ?DOMDocument
    {
        $this->maybe_throw_exception();
        $loaded = $this->xml_document->loadXML($this->svg);
        return $loaded ? $this->xml_document : null;
    }
    /**
     * @throws \RuntimeException
     */
    protected function maybe_throw_exception() : void
    {
        if (!isset($this->svg)) {
            throw new RuntimeException(\sprintf('The $svg property is not set, did you run %s::parse_file() or %s::parse_string()?', self::class, self::class));
        }
    }
    /**
     * Check if the contents are gzipped.
     *
     * @see http://www.gzip.org/zlib/rfc-gzip.html#member-format
     *
     * @param string $markup The SVG markup.
     */
    protected function is_gzipped(string $markup) : bool
    {
        $headers = "\x1f" . "\x8b" . "\x08";
        if (\function_exists('mb_strpos')) {
            return 0 === \mb_strpos($markup, $headers);
        }
        return \str_starts_with($markup, $headers);
    }
    /**
     * Return the SVG XML markup.
     */
    public function __toString() : string
    {
        $this->maybe_throw_exception();
        return $this->svg;
    }
}
