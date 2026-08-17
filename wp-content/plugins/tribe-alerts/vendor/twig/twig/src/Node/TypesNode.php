<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tribe\Alert_Scoped\Twig\Node;

use Tribe\Alert_Scoped\Twig\Attribute\YieldReady;
use Tribe\Alert_Scoped\Twig\Compiler;
/**
 * Represents a types node.
 *
 * @author Jeroen Versteeg <jeroen@alisqi.com>
 */
#[\Twig\Attribute\YieldReady]
class TypesNode extends Node
{
    /**
     * @param array<string, array{type: string, optional: bool}> $types
     */
    public function __construct(array $types, int $lineno)
    {
        parent::__construct([], ['mapping' => $types], $lineno);
    }
    /**
     * @return void
     */
    public function compile(Compiler $compiler)
    {
        // Don't compile anything.
    }
}
