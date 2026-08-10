<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 * (c) Armin Ronacher
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tribe\Alert_Scoped\Twig\Node\Expression\Unary;

use Tribe\Alert_Scoped\Twig\Compiler;
use Tribe\Alert_Scoped\Twig\Node\Expression\AbstractExpression;
use Tribe\Alert_Scoped\Twig\Node\Node;
abstract class AbstractUnary extends AbstractExpression implements UnaryInterface
{
    /**
     * @param AbstractExpression $node
     */
    public function __construct(Node $node, int $lineno)
    {
        if (!$node instanceof AbstractExpression) {
            \trigger_deprecation('twig/twig', '3.15', 'Not passing a "%s" instance argument to "%s" is deprecated ("%s" given).', AbstractExpression::class, static::class, $node::class);
        }
        parent::__construct(['node' => $node], ['with_parentheses' => \false], $lineno);
    }
    public function compile(Compiler $compiler) : void
    {
        if ($this->hasExplicitParentheses()) {
            $compiler->raw('(');
        } else {
            $compiler->raw(' ');
        }
        $this->operator($compiler);
        $compiler->subcompile($this->getNode('node'));
        if ($this->hasExplicitParentheses()) {
            $compiler->raw(')');
        }
    }
    public abstract function operator(Compiler $compiler) : Compiler;
}
