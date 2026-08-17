<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Twig;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
use Tribe\Alert_Scoped\Twig\Cache\FilesystemCache;
use Tribe\Alert_Scoped\Twig\Environment;
use Tribe\Alert_Scoped\Twig\Extension\DebugExtension;
use Tribe\Alert_Scoped\Twig\Loader\FilesystemLoader;
use Tribe\Alert_Scoped\Twig\Loader\LoaderInterface;
class Twig_Definer implements Definer_Interface
{
    public const OPTIONS = 'libs.twig.options';
    public function define() : array
    {
        return [Twig_Cache::class => DI\create()->constructor($this->twig_cache_dir(), FilesystemCache::FORCE_BYTECODE_INVALIDATION), self::OPTIONS => static function (ContainerInterface $container) {
            return \apply_filters('tribe/libs/twig/options', ['debug' => \WP_DEBUG, 'cache' => \defined('TWIG_CACHE') && TWIG_CACHE === \false ? \false : $container->get(Twig_Cache::class), 'autoescape' => \false, 'auto_reload' => \true]);
        }, LoaderInterface::class => static function () {
            $stylesheet_path = \get_stylesheet_directory();
            $template_path = \get_template_directory();
            $loader = new FilesystemLoader([$stylesheet_path]);
            if ($template_path !== $stylesheet_path) {
                $loader->addPath($template_path);
            }
            return $loader;
        }, Environment::class => static function (ContainerInterface $c) {
            $environment = new Environment($c->get(LoaderInterface::class), $c->get(self::OPTIONS));
            $environment->addExtension($c->get(Extension::class));
            // enable the `dump()` function in templates when debugging
            if (\WP_DEBUG) {
                $environment->addExtension($c->get(DebugExtension::class));
            }
            return $environment;
        }];
    }
    private function twig_cache_dir() : string
    {
        if (\defined('TWIG_CACHE_DIR') && TWIG_CACHE_DIR) {
            return TWIG_CACHE_DIR;
        }
        if (\defined('WP_CONTENT_DIR')) {
            return \WP_CONTENT_DIR . '/cache/twig/';
        }
        return \sys_get_temp_dir() . '/cache/twig/';
    }
}
