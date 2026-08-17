<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\DI\Definition\Source;

use Tribe\Alert_Scoped\DI\Definition\Exception\InvalidDefinition;
use Tribe\Alert_Scoped\DI\Definition\ObjectDefinition;
/**
 * Implementation used when autowiring is completely disabled.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class NoAutowiring implements Autowiring
{
    public function autowire(string $name, ?ObjectDefinition $definition = null) : ?ObjectDefinition
    {
        throw new InvalidDefinition(\sprintf('Cannot autowire entry "%s" because autowiring is disabled', $name));
    }
}
