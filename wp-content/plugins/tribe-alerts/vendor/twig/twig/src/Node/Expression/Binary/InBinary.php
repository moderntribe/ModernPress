<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tribe\Alert_Scoped\Twig\Node\Expression\Binary;

use Tribe\Alert_Scoped\Twig\Compiler;
use Tribe\Alert_Scoped\Twig\Node\CoercesChildrenToStringInterface;
use Tribe\Alert_Scoped\Twig\Node\Expression\ReturnBoolInterface;
class InBinary extends AbstractBinary implements ReturnBoolInterface, CoercesChildrenToStringInterface
{
    public function compile(Compiler $compiler) : void
    {
        $compiler->raw('CoreExtension::inFilter(')->subcompile($this->getNode('left'))->raw(', ')->subcompile($this->getNode('right'))->raw(')');
    }
    public function operator(Compiler $compiler) : Compiler
    {
        return $compiler->raw('in');
    }
    public function getStringCoercedChildNames() : array
    {
        return ['left', 'right'];
    }
}
