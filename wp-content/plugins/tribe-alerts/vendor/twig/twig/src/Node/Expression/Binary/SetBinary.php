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
use Tribe\Alert_Scoped\Twig\Node\Expression\AbstractExpression;
use Tribe\Alert_Scoped\Twig\Node\Expression\Variable\AssignContextVariable;
use Tribe\Alert_Scoped\Twig\Node\Expression\Variable\ContextVariable;
use Tribe\Alert_Scoped\Twig\Node\Node;
/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SetBinary extends AbstractBinary
{
    /**
     * @param ContextVariable    $left
     * @param AbstractExpression $right
     */
    public function __construct(Node $left, Node $right, int $lineno)
    {
        $name = $left->getAttribute('name');
        if (!\is_string($name)) {
            throw new \LogicException('The "name" attribute must be a string.');
        }
        $left = new AssignContextVariable($name, $left->getTemplateLine());
        parent::__construct($left, $right, $lineno);
    }
    public function operator(Compiler $compiler) : Compiler
    {
        return $compiler->raw('=');
    }
}
