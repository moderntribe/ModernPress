<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tribe\Alert_Scoped\Twig\Node\Expression;

use Tribe\Alert_Scoped\Twig\Compiler;
class VariadicExpression extends ArrayExpression
{
    public function compile(Compiler $compiler) : void
    {
        $compiler->raw('...');
        parent::compile($compiler);
    }
}
