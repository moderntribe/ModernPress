<?php

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tribe\Alert_Scoped\Twig\Extension;

use Tribe\Alert_Scoped\Twig\ExpressionParser;
use Tribe\Alert_Scoped\Twig\ExpressionParser\ExpressionParserInterface;
use Tribe\Alert_Scoped\Twig\ExpressionParser\PrecedenceChange;
use Tribe\Alert_Scoped\Twig\Node\Expression\Binary\AbstractBinary;
use Tribe\Alert_Scoped\Twig\Node\Expression\Unary\AbstractUnary;
use Tribe\Alert_Scoped\Twig\NodeVisitor\NodeVisitorInterface;
use Tribe\Alert_Scoped\Twig\TokenParser\TokenParserInterface;
use Tribe\Alert_Scoped\Twig\TwigFilter;
use Tribe\Alert_Scoped\Twig\TwigFunction;
use Tribe\Alert_Scoped\Twig\TwigTest;
/**
 * Interface implemented by extension classes.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @method array<ExpressionParserInterface> getExpressionParsers()
 */
interface ExtensionInterface
{
    /**
     * Returns the token parser instances to add to the existing list.
     *
     * @return TokenParserInterface[]
     */
    public function getTokenParsers();
    /**
     * Returns the node visitor instances to add to the existing list.
     *
     * @return NodeVisitorInterface[]
     */
    public function getNodeVisitors();
    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters();
    /**
     * Returns a list of tests to add to the existing list.
     *
     * @return TwigTest[]
     */
    public function getTests();
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions();
    /**
     * Returns a list of operators to add to the existing list.
     *
     * @return array<array>
     *
     * @psalm-return array{
     *     array<string, array{precedence: int, precedence_change?: PrecedenceChange, class: class-string<AbstractUnary>}>,
     *     array<string, array{precedence: int, precedence_change?: PrecedenceChange, class?: class-string<AbstractBinary>, associativity: ExpressionParser::OPERATOR_*}>
     * }
     */
    public function getOperators();
}
