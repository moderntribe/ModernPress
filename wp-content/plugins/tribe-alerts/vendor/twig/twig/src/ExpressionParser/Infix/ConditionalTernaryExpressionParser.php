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

use Tribe\Alert_Scoped\Twig\ExpressionParser\AbstractExpressionParser;
use Tribe\Alert_Scoped\Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use Tribe\Alert_Scoped\Twig\ExpressionParser\InfixAssociativity;
use Tribe\Alert_Scoped\Twig\ExpressionParser\InfixExpressionParserInterface;
use Tribe\Alert_Scoped\Twig\Node\Expression\AbstractExpression;
use Tribe\Alert_Scoped\Twig\Node\Expression\ConstantExpression;
use Tribe\Alert_Scoped\Twig\Node\Expression\Ternary\ConditionalTernary;
use Tribe\Alert_Scoped\Twig\Parser;
use Tribe\Alert_Scoped\Twig\Token;
/**
 * @internal
 */
final class ConditionalTernaryExpressionParser extends AbstractExpressionParser implements InfixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    public function parse(Parser $parser, AbstractExpression $left, Token $token) : AbstractExpression
    {
        $then = $parser->parseExpression($this->getPrecedence());
        if ($parser->getStream()->nextIf(Token::PUNCTUATION_TYPE, ':')) {
            // Ternary operator (expr ? expr2 : expr3)
            $else = $parser->parseExpression($this->getPrecedence());
        } else {
            // Ternary without else (expr ? expr2)
            $else = new ConstantExpression('', $token->getLine());
        }
        return new ConditionalTernary($left, $then, $else, $token->getLine());
    }
    public function getName() : string
    {
        return '?';
    }
    public function getDescription() : string
    {
        return 'Conditional operator (a ? b : c)';
    }
    public function getPrecedence() : int
    {
        return 0;
    }
    public function getAssociativity() : InfixAssociativity
    {
        return InfixAssociativity::Left;
    }
}
