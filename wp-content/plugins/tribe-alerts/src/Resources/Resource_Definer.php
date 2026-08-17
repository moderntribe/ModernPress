<?php

declare (strict_types=1);
namespace Tribe\Alert\Resources;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
use Tribe\Alert\Core;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
class Resource_Definer implements Definer_Interface
{
    public function define() : array
    {
        return [
            // Define the location of Laravel Mix's mix-manifest.json
            \Tribe\Alert\Resources\Manifest_Loader::class => DI\autowire()->constructorParameter('manifest_path', static function (ContainerInterface $c) {
                return \sprintf('%s/%s', $c->get(Core::DIST_DIR_PATH), '/mix-manifest.json');
            })->constructorParameter('dist_uri', DI\get(Core::DIST_DIR_URI)),
        ];
    }
}
