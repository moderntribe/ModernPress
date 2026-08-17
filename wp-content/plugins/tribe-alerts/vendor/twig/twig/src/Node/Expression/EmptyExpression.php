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
/**
 * Represents an empty slot in an array.
 *
 * This is currently only used in destructuring contexts.
 *
 * @internal
 */
final class EmptyExpression extends AbstractExpression
{
    public function __construct(int $lineno)
    {
        parent::__construct([], [], $lineno);
    }
    public function compile(Compiler $compiler) : void
    {
    }
}
