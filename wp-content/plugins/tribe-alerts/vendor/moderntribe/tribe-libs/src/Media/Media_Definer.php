<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Media;

use Tribe\Alert_Scoped\DI;
use DOMDocument;
use Tribe\Alert_Scoped\enshrined\svgSanitize\Sanitizer;
use Tribe\Alert_Scoped\Tribe\Libs\CLI\CLI_Definer;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
use Tribe\Alert_Scoped\Tribe\Libs\Media\CLI\Svg_Command;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store\Contracts\Svg_Store;
use Tribe\Alert_Scoped\Tribe\Libs\Media\Svg\Store\Stores\Svg_Meta_Store;
class Media_Definer implements Definer_Interface
{
    public const SVG_INLINE_META_KEY = 'media.svg.store.meta_key';
    public function define() : array
    {
        return [
            // The post meta key for storing SVG markup.
            self::SVG_INLINE_META_KEY => '_tribe_svg_markup',
            DOMDocument::class => static fn() => new DOMDocument(),
            Sanitizer::class => static function () {
                $sanitizer = new Sanitizer();
                $sanitizer->minify(\true);
                $sanitizer->setXMLOptions(0);
                return $sanitizer;
            },
            Svg_Meta_Store::class => DI\autowire()->constructorParameter('meta_key', DI\get(self::SVG_INLINE_META_KEY)),
            Svg_Store::class => DI\get(Svg_Meta_Store::class),
            // CLI command to add/remove SVG markup from the database.
            Svg_Command::class => DI\autowire()->constructorParameter('meta_key', DI\get(self::SVG_INLINE_META_KEY)),
            CLI_Definer::COMMANDS => DI\add([DI\get(Svg_Command::class)]),
        ];
    }
}
