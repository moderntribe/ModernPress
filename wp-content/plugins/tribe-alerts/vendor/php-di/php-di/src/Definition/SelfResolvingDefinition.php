<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\DI\Definition;

use Tribe\Alert_Scoped\Psr\Container\ContainerInterface;
/**
 * Describes a definition that can resolve itself.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface SelfResolvingDefinition
{
    /**
     * Resolve the definition and return the resulting value.
     */
    public function resolve(ContainerInterface $container) : mixed;
    /**
     * Check if a definition can be resolved.
     */
    public function isResolvable(ContainerInterface $container) : bool;
}
