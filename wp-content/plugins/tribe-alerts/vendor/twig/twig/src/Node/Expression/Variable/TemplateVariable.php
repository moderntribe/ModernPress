<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tribe\Alert_Scoped\Twig\Node\Expression\Variable;

use Tribe\Alert_Scoped\Twig\Compiler;
use Tribe\Alert_Scoped\Twig\Node\Expression\TempNameExpression;
class TemplateVariable extends TempNameExpression
{
    public function getName(Compiler $compiler) : string
    {
        if (null === $this->getAttribute('name')) {
            $this->setAttribute('name', $compiler->getVarName());
        }
        return $this->getAttribute('name');
    }
    public function compile(Compiler $compiler) : void
    {
        $name = $this->getName($compiler);
        if ('_self' === $name) {
            $compiler->raw('$this');
        } else {
            $compiler->raw('$macros[')->string($name)->raw(']');
        }
    }
}
