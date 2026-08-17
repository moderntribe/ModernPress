<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tribe\Alert_Scoped\Twig\ExpressionParser\Prefix;

use Tribe\Alert_Scoped\Twig\ExpressionParser\AbstractExpressionParser;
use Tribe\Alert_Scoped\Twig\ExpressionParser\ExpressionParserDescriptionInterface;
use Tribe\Alert_Scoped\Twig\ExpressionParser\PrecedenceChange;
use Tribe\Alert_Scoped\Twig\ExpressionParser\PrefixExpressionParserInterface;
use Tribe\Alert_Scoped\Twig\Node\Expression\AbstractExpression;
use Tribe\Alert_Scoped\Twig\Node\Expression\Unary\AbstractUnary;
use Tribe\Alert_Scoped\Twig\Parser;
use Tribe\Alert_Scoped\Twig\Token;
/**
 * @internal
 */
final class UnaryOperatorExpressionParser extends AbstractExpressionParser implements PrefixExpressionParserInterface, ExpressionParserDescriptionInterface
{
    public function __construct(
        /** @var class-string<AbstractUnary> */
        private string $nodeClass,
        private string $name,
        private int $precedence,
        private ?PrecedenceChange $precedenceChange = null,
        private ?string $description = null,
        private array $aliases = [],
        private ?int $operandPrecedence = null
    )
    {
    }
    /**
     * @return AbstractUnary
     */
    public function parse(Parser $parser, Token $token) : AbstractExpression
    {
        return new $this->nodeClass($parser->parseExpression($this->operandPrecedence ?? $this->precedence), $token->getLine());
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function getDescription() : string
    {
        return $this->description ?? '';
    }
    public function getPrecedence() : int
    {
        return $this->precedence;
    }
    public function getPrecedenceChange() : ?PrecedenceChange
    {
        return $this->precedenceChange;
    }
    public function getAliases() : array
    {
        return $this->aliases;
    }
}
