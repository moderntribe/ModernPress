<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\DI\Definition\Source;

use Tribe\Alert_Scoped\DI\Definition\Exception\InvalidDefinition;
use Tribe\Alert_Scoped\DI\Definition\ObjectDefinition;
/**
 * Source of definitions for entries of the container.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface Autowiring
{
    /**
     * Autowire the given definition.
     *
     * @throws InvalidDefinition An invalid definition was found.
     */
    public function autowire(string $name, ?ObjectDefinition $definition = null) : ?ObjectDefinition;
}
