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
use Tribe\Alert_Scoped\Twig\Node\Expression\ReturnNumberInterface;
class SpaceshipBinary extends AbstractBinary implements ReturnNumberInterface, CoercesChildrenToStringInterface
{
    public function operator(Compiler $compiler) : Compiler
    {
        return $compiler->raw('<=>');
    }
    public function getStringCoercedChildNames() : array
    {
        return ['left', 'right'];
    }
}
