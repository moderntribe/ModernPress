<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Media\Svg;

/**
 * Class Image_Editor_Svg
 *
 * This is a stub implementation of \WP_Image_Editor. It's purpose
 * is to allow metadata regeneration when wp-cli's media regenerate
 * command is run. Without this, SVG images are completely skipped.
 */
class Image_Editor_Svg extends \WP_Image_Editor
{
    /**
     * Assert that this editor can handle SVG images
     *
     * @param string $mime_type
     *
     * @return bool
     */
    public static function supports_mime_type($mime_type)
    {
        return $mime_type === 'image/svg+xml';
    }
    /**
     * Assert that this editor only handles SVG images
     *
     * @param array $args
     *
     * @return bool
     */
    public static function test($args = [])
    {
        return isset($args['mime_type']) && $args['mime_type'] === 'image/svg+xml';
    }
    /*
     * The following methods should never be called, but are required to extend \WP_Image_Editor
     */
    public function load()
    {
        return new \WP_Error('image_is_svg', \__('SVG metadata will be regenerated, but no files will be updated', 'tribe'));
    }
    public function save($destfilename = null, $mime_type = null)
    {
        return new \WP_Error('method_not_implemented', \__('SVG image editing is not supported', 'tribe'));
    }
    public function resize($max_w, $max_h, $crop = \false)
    {
        return new \WP_Error('method_not_implemented', \__('SVG image editing is not supported', 'tribe'));
    }
    public function multi_resize($sizes)
    {
        return [];
    }
    public function crop($src_x, $src_y, $src_w, $src_h, $dst_w = null, $dst_h = null, $src_abs = \false)
    {
        return new \WP_Error('method_not_implemented', \__('SVG image editing is not supported', 'tribe'));
    }
    public function rotate($angle)
    {
        return new \WP_Error('method_not_implemented', \__('SVG image editing is not supported', 'tribe'));
    }
    public function flip($horz, $vert)
    {
        return new \WP_Error('method_not_implemented', \__('SVG image editing is not supported', 'tribe'));
    }
    public function stream($mime_type = null)
    {
        return new \WP_Error('method_not_implemented', \__('SVG image editing is not supported', 'tribe'));
    }
}
