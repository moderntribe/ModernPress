<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media\Svg;

use Tribe\Alert_Scoped\enshrined\svgSanitize\Sanitizer;
/**
 * Class Sanitize_Uploads
 *
 * Sanitize uploaded SVGs to remove possibly unsafe data
 */
class Sanitize_Uploads
{
    /**
     * @var Sanitizer
     */
    private $sanitizer;
    public function __construct(Sanitizer $sanitizer)
    {
        $this->sanitizer = $sanitizer;
    }
    /**
     * @param string[] $file An array of data for a single file
     *
     * @return string[]
     * @filter wp_handle_upload_prefilter
     */
    public function filter_svg_uploads(array $file) : array
    {
        if ($file['type'] === 'image/svg+xml') {
            $sanitized = $this->sanitize($file['tmp_name']);
            if (!$sanitized) {
                $file['error'] = \__("Sorry, this file could not be sanitized.", 'tribe');
            }
        }
        return $file;
    }
    /**
     * @param string $file Path to the file
     *
     * @return bool
     */
    private function sanitize($file) : bool
    {
        $dirty = \file_get_contents($file);
        // Is the SVG gzipped? If so we try and decode the string
        $is_zipped = $this->is_gzipped($dirty);
        if ($is_zipped) {
            $dirty = \gzdecode($dirty);
            // If decoding fails, bail as we're not secure
            if ($dirty === \false) {
                return \false;
            }
        }
        /** @var string|false $clean */
        $clean = $this->sanitizer->sanitize($dirty);
        if ($clean === \false) {
            return \false;
        }
        // If we were gzipped, we need to re-zip
        if ($is_zipped) {
            $clean = \gzencode($clean);
        }
        \file_put_contents($file, $clean);
        return \true;
    }
    /**
     * Check if the contents are gzipped
     *
     * @see http://www.gzip.org/zlib/rfc-gzip.html#member-format
     *
     * @param string $contents
     *
     * @return bool
     */
    protected function is_gzipped($contents) : bool
    {
        if (\function_exists('mb_strpos')) {
            return 0 === \mb_strpos($contents, "\x1f" . "\x8b" . "\x08");
        }
        return 0 === \strpos($contents, "\x1f" . "\x8b" . "\x08");
    }
}
