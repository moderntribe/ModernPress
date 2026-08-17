<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media\Svg;

/**
 * Class Set_Attachment_Metadata
 *
 * Sets size metadata on SVG image attachments
 */
class Set_Attachment_Metadata
{
    /**
     * @param array $metadata
     * @param int   $attachment_id
     *
     * @return array
     * @filter wp_generate_attachment_metadata
     */
    public function generate_metadata(array $metadata, int $attachment_id) : array
    {
        $mime = \get_post_mime_type($attachment_id);
        if ($mime !== 'image/svg+xml') {
            return $metadata;
        }
        $svg_path = \get_attached_file($attachment_id);
        $filename = \basename($svg_path);
        $dimensions = $this->svg_dimensions($svg_path);
        if (!$dimensions) {
            return $metadata;
        }
        [$width, $height] = $dimensions;
        $metadata = ['width' => $width, 'height' => $height, 'file' => \_wp_relative_upload_path($svg_path), 'sizes' => []];
        $all_sizes = \wp_get_registered_image_subsizes();
        $calculator = new Size_Calculator($width, $height);
        /**
         * Return true for this filter to force SVG sizes to use scale
         * instead of crop dimensions.
         *
         * @param bool $never_crop Whether to disable cropping. Defaults to `false`
         */
        $never_crop = \apply_filters('tribe/libs/svg/never_crop', \false);
        foreach ($all_sizes as $size_name => $size) {
            if ($never_crop || empty($size['crop'])) {
                [$scaled_width, $scaled_height] = $calculator->scale((int) $size['width'], (int) $size['height']);
            } else {
                [$scaled_width, $scaled_height] = $calculator->crop((int) $size['width'], (int) $size['height']);
            }
            $metadata['sizes'][$size_name] = ['file' => $filename, 'width' => $scaled_width, 'height' => $scaled_height, 'mime-type' => $mime];
        }
        return $metadata;
    }
    /**
     * Get SVG size from the width/height or viewBox.
     *
     * @param string $file_path Path to the SVG file.
     *
     * @return int[]|bool [ $width, $height ], or false if it can't be extracted
     */
    protected function svg_dimensions(string $file_path)
    {
        $filedata = \file_get_contents($file_path);
        if ($this->is_gzipped($filedata)) {
            $svg = @\simplexml_load_string(\gzdecode($filedata));
        } else {
            $svg = @\simplexml_load_string($filedata);
        }
        $width = 0;
        $height = 0;
        if ($svg) {
            $attributes = $svg->attributes();
            if (isset($attributes->width, $attributes->height) && \is_numeric((string) $attributes->width) && \is_numeric((string) $attributes->height)) {
                $width = (float) $attributes->width;
                $height = (float) $attributes->height;
            } elseif (isset($attributes->viewBox)) {
                $sizes = \explode(' ', (string) $attributes->viewBox);
                if (isset($sizes[2], $sizes[3])) {
                    $width = (float) $sizes[2];
                    $height = (float) $sizes[3];
                }
            } else {
                return \false;
            }
        }
        return [(int) \round($width), (int) \round($height)];
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
