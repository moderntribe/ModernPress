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
use Tribe\Alert_Scoped\Twig\Node\EmptyNode;
use Tribe\Alert_Scoped\Twig\Node\Expression\AbstractExpression;
use Tribe\Alert_Scoped\Twig\Node\Expression\BlockReferenceExpression;
use Tribe\Alert_Scoped\Twig\Node\Expression\OperatorEscapeInterface;
use Tribe\Alert_Scoped\Twig\Node\Expression\Test\DefinedTest;
use Tribe\Alert_Scoped\Twig\Node\Expression\Test\NullTest;
use Tribe\Alert_Scoped\Twig\Node\Expression\Unary\NotUnary;
use Tribe\Alert_Scoped\Twig\Node\Node;
use Tribe\Alert_Scoped\Twig\TwigTest;
final class NullCoalesceBinary extends AbstractBinary implements OperatorEscapeInterface
{
    /**
     * @param AbstractExpression $left
     * @param AbstractExpression $right
     */
    public function __construct(Node $left, Node $right, int $lineno)
    {
        parent::__construct($left, $right, $lineno);
        $test = new DefinedTest(clone $left, new TwigTest('defined', null, ['always_allowed_in_sandbox' => \true]), new EmptyNode(), $left->getTemplateLine());
        // for "block()", we don't need the null test as the return value is always a string
        if (!$left instanceof BlockReferenceExpression) {
            $test = new AndBinary($test, new NotUnary(new NullTest($left, new TwigTest('null', null, ['always_allowed_in_sandbox' => \true]), new EmptyNode(), $left->getTemplateLine()), $left->getTemplateLine()), $left->getTemplateLine());
        }
        $left->setAttribute('always_defined', \true);
        $this->setNode('test', $test);
    }
    public function compile(Compiler $compiler) : void
    {
        $compiler->raw('((')->subcompile($this->getNode('test'))->raw(') ? (')->subcompile($this->getNode('left'))->raw(') : (')->subcompile($this->getNode('right'))->raw('))');
    }
    public function operator(Compiler $compiler) : Compiler
    {
        return $compiler->raw('??');
    }
    public function getOperandNamesToEscape() : array
    {
        return ['left', 'right'];
    }
}
