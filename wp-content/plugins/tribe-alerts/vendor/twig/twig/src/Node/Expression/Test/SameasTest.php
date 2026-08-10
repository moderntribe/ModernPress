<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tribe\Alert_Scoped\Twig\Node\Expression\Test;

use Tribe\Alert_Scoped\Twig\Compiler;
use Tribe\Alert_Scoped\Twig\Node\Expression\TestExpression;
/**
 * Checks if a variable is the same as another one (=== in PHP).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SameasTest extends TestExpression
{
    public function compile(Compiler $compiler) : void
    {
        $compiler->raw('(')->subcompile($this->getNode('node'))->raw(' === ')->subcompile($this->getNode('arguments')->getNode('0'))->raw(')');
    }
    public function getStringCoercedChildNames() : array
    {
        // `===` is strict, no coercion
        return [];
    }
}
