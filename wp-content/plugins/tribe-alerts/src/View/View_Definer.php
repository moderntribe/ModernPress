<?php

declare (strict_types=1);
namespace Tribe\Alert\View;

use Tribe\Alert_Scoped\DI;
use Tribe\Alert_Scoped\League\Plates\Engine;
use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
use Tribe\Alert\Core;
use Tribe\Alert_Scoped\Tribe\Libs\Container\Definer_Interface;
class View_Definer implements Definer_Interface
{
    public const VIEW_DIRECTORY = 'views';
    public function define() : array
    {
        return [
            // Configure the path to our views
            Engine::class => DI\autowire()->constructorParameter('directory', static fn(ContainerInterface $c) => \apply_filters('tribe/alerts/view_directory', \sprintf('%s/%s', $c->get(Core::RESOURCES_PATH), self::VIEW_DIRECTORY))),
        ];
    }
}
