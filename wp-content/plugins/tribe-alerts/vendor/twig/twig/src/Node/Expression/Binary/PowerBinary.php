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
use Tribe\Alert_Scoped\Twig\Node\Expression\ReturnNumberInterface;
class PowerBinary extends AbstractBinary implements ReturnNumberInterface
{
    public function operator(Compiler $compiler) : Compiler
    {
        return $compiler->raw('**');
    }
}
