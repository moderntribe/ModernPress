<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media;

use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Oembed\YouTube_Oembed_Filter;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Enable_Uploads;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Sanitize_Uploads;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Set_Attachment_Metadata;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store\Svg_Store_Handler;
class Media_Subscriber extends Abstract_Subscriber
{
    public function register() : void
    {
        $this->full_size_gif();
        $this->svg_uploads();
        $this->disable_responsive_images();
        $this->oembed();
    }
    private function full_size_gif() : void
    {
        if (\defined('FORCE_FULL_SIZE_GIFS') && FORCE_FULL_SIZE_GIFS === \false) {
            return;
        }
        \add_filter('image_downsize', function ($data, $id, $size) {
            return $this->container->get(Full_Size_Gif::class)->full_size_only_gif($data, $id, $size);
        }, 10, 3);
    }
    private function svg_uploads() : void
    {
        if (\defined('TRIBE_ENABLE_SVG_UPLOADS') && TRIBE_ENABLE_SVG_UPLOADS === \false) {
            return;
        }
        \add_filter('mime_types', function ($mimes) {
            return $this->container->get(Enable_Uploads::class)->set_svg_mimes($mimes);
        }, 10, 1);
        \add_filter('upload_mimes', function ($mimes) {
            return $this->container->get(Enable_Uploads::class)->set_svg_mimes($mimes);
        }, 10, 1);
        \add_filter('wp_image_editors', function ($editors) {
            return $this->container->get(Enable_Uploads::class)->filter_image_editors($editors);
        }, 10, 1);
        \add_filter('wp_check_filetype_and_ext', function ($data, $file) {
            return $this->container->get(Enable_Uploads::class)->set_upload_mime($data, $file);
        }, 10, 2);
        \add_filter('wp_handle_upload_prefilter', function ($file) {
            return $this->container->get(Sanitize_Uploads::class)->filter_svg_uploads($file);
        }, 10, 1);
        \add_filter('wp_generate_attachment_metadata', function ($metadata, $attachment_id) {
            return $this->container->get(Set_Attachment_Metadata::class)->generate_metadata($metadata, (int) $attachment_id);
        }, 10, 2);
        $this->svg_inline_storage();
    }
    /**
     * Store SVG inline markup in post meta when an SVG is uploaded to the media library.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    private function svg_inline_storage() : void
    {
        if (\defined('TRIBE_ENABLE_SVG_INLINE_STORAGE') && TRIBE_ENABLE_SVG_INLINE_STORAGE === \false) {
            return;
        }
        \add_filter('update_attached_file', function ($file, $attachment_id) : string {
            return $this->container->get(Svg_Store_Handler::class)->store((string) $file, (int) $attachment_id);
        }, 90, 2);
    }
    private function disable_responsive_images() : void
    {
        if (\defined('DISABLE_WP_RESPONSIVE_IMAGES') && DISABLE_WP_RESPONSIVE_IMAGES === \false) {
            return;
        }
        \add_filter('wp_get_attachment_image_attributes', function ($attr) {
            return $this->container->get(WP_Responsive_Image_Disabler::class)->filter_image_attributes($attr);
        }, 999, 1);
        \add_action('after_setup_theme', function () {
            $this->container->get(WP_Responsive_Image_Disabler::class)->disable_wordpress_filters();
        }, 10, 0);
    }
    private function oembed() : void
    {
        if (\defined('TRIBE_ENABLE_YOUTUBE_NOCOOKIE_URI') && TRIBE_ENABLE_YOUTUBE_NOCOOKIE_URI === \false) {
            return;
        }
        \add_filter('oembed_dataparse', fn($html) => $this->container->get(YouTube_Oembed_Filter::class)->force_youtube_no_cookie_embed((string) $html), 999, 1);
    }
}
