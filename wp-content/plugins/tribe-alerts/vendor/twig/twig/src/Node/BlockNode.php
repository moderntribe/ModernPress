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
namespace Tribe\Alert_Scoped\Twig\Node;

use Tribe\Alert_Scoped\Twig\Attribute\YieldReady;
use Tribe\Alert_Scoped\Twig\Compiler;
/**
 * Represents a block node.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
#[\Twig\Attribute\YieldReady]
class BlockNode extends Node
{
    public function __construct(string $name, Node $body, int $lineno)
    {
        parent::__construct(['body' => $body], ['name' => $name], $lineno);
    }
    public function compile(Compiler $compiler) : void
    {
        $compiler->addDebugInfo($this)->write("/**\n")->write(" * @return iterable<null|scalar|\\Stringable>\n")->write(" */\n")->write(\sprintf("public function block_%s(array \$context, array \$blocks = []): iterable\n", $this->getAttribute('name')), "{\n")->indent()->write("\$macros = \$this->macros;\n");
        $compiler->subcompile($this->getNode('body'))->write("yield from [];\n")->outdent()->write("}\n\n");
    }
}
