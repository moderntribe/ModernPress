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
use Tribe\Alert_Scoped\Twig\Node\Expression\ArrayExpression;
use Tribe\Alert_Scoped\Twig\Node\Expression\ConstantExpression;
use Tribe\Alert_Scoped\Twig\Node\Expression\GetAttrExpression;
use Tribe\Alert_Scoped\Twig\Node\Nodes;
use Tribe\Alert_Scoped\Twig\Parser;
use Tribe\Alert_Scoped\Twig\Template;
use Tribe\Alert_Scoped\Twig\Token;
/**
 * @internal
 */
final class SquareBracketExpressionParser extends AbstractExpressionParser implements InfixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    public function parse(Parser $parser, AbstractExpression $expr, Token $token) : AbstractExpression
    {
        $stream = $parser->getStream();
        $lineno = $token->getLine();
        $arguments = new ArrayExpression([], $lineno);
        // slice?
        $slice = \false;
        if ($stream->test(Token::PUNCTUATION_TYPE, ':')) {
            $slice = \true;
            $attribute = new ConstantExpression(0, $token->getLine());
        } else {
            $attribute = $parser->parseExpression();
        }
        if ($stream->nextIf(Token::PUNCTUATION_TYPE, ':')) {
            $slice = \true;
        }
        if ($slice) {
            if ($stream->test(Token::PUNCTUATION_TYPE, ']')) {
                $length = new ConstantExpression(null, $token->getLine());
            } else {
                $length = $parser->parseExpression();
            }
            $filter = $parser->getFilter('slice', $token->getLine());
            $arguments = new Nodes([$attribute, $length]);
            $filter = new $filter->getNodeClass()($expr, $filter, $arguments, $token->getLine());
            $stream->expect(Token::PUNCTUATION_TYPE, ']');
            return $filter;
        }
        $stream->expect(Token::PUNCTUATION_TYPE, ']');
        return new GetAttrExpression($expr, $attribute, $arguments, Template::ARRAY_CALL, $lineno);
    }
    public function getName() : string
    {
        return '[';
    }
    public function getDescription() : string
    {
        return 'Array access';
    }
    public function getPrecedence() : int
    {
        return 512;
    }
    public function getAssociativity() : InfixAssociativity
    {
        return InfixAssociativity::Left;
    }
}
