<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tribe\Alert_Scoped\Twig\ExpressionParser\Infix;

use Tribe\Alert_Scoped\Twig\Attribute\FirstClassTwigCallableReady;
use Tribe\Alert_Scoped\Twig\ExpressionParser\AbstractExpressionParser;
use Tribe\Alert_Scoped\Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use Tribe\Alert_Scoped\Twig\ExpressionParser\InfixAssociativity;
use Tribe\Alert_Scoped\Twig\ExpressionParser\InfixExpressionParserInterface;
use Tribe\Alert_Scoped\Twig\Node\Expression\AbstractExpression;
use Tribe\Alert_Scoped\Twig\Node\Expression\ArrayExpression;
use Tribe\Alert_Scoped\Twig\Node\Expression\MacroReferenceExpression;
use Tribe\Alert_Scoped\Twig\Node\Expression\NameExpression;
use Tribe\Alert_Scoped\Twig\Node\Nodes;
use Tribe\Alert_Scoped\Twig\Parser;
use Tribe\Alert_Scoped\Twig\Token;
use Tribe\Alert_Scoped\Twig\TwigTest;
/**
 * @internal
 */
class IsExpressionParser extends AbstractExpressionParser implements InfixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    use ArgumentsTrait;
    private $readyNodes = [];
    public function parse(Parser $parser, AbstractExpression $expr, Token $token) : AbstractExpression
    {
        $stream = $parser->getStream();
        $test = $parser->getTest($token->getLine());
        $arguments = null;
        if ($stream->test(Token::OPERATOR_TYPE, '(')) {
            $arguments = $this->parseNamedArguments($parser);
        } elseif ($test->hasOneMandatoryArgument()) {
            $arguments = new Nodes([0 => $parser->parseExpression($this->getPrecedence())]);
        }
        if ('defined' === $test->getName() && $expr instanceof NameExpression && null !== ($alias = $parser->getImportedSymbol('function', $expr->getAttribute('name')))) {
            $expr = new MacroReferenceExpression($alias['node']->getNode('var'), $alias['name'], new ArrayExpression([], $expr->getTemplateLine()), $expr->getTemplateLine());
        }
        $ready = $test instanceof TwigTest;
        if (!isset($this->readyNodes[$class = $test->getNodeClass()])) {
            $this->readyNodes[$class] = (bool) (new \ReflectionClass($class))->getConstructor()->getAttributes(FirstClassTwigCallableReady::class);
        }
        if (!($ready = $this->readyNodes[$class])) {
            \trigger_deprecation('twig/twig', '3.12', 'Twig node "%s" is not marked as ready for passing a "TwigTest" in the constructor instead of its name; please update your code and then add #[FirstClassTwigCallableReady] attribute to the constructor.', $class);
        }
        return new $class($expr, $ready ? $test : $test->getName(), $arguments, $stream->getCurrent()->getLine());
    }
    public function getPrecedence() : int
    {
        return 100;
    }
    public function getName() : string
    {
        return 'is';
    }
    public function getDescription() : string
    {
        return 'Twig tests';
    }
    public function getAssociativity() : InfixAssociativity
    {
        return InfixAssociativity::Left;
    }
}
