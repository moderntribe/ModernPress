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

use Tribe\Alert_Scoped\Twig\Environment;
use Tribe\Alert_Scoped\Twig\TemplateWrapper;
use Tribe\Alert_Scoped\Twig\TwigFunction;
final class StringLoaderExtension extends AbstractExtension
{
    public function getFunctions() : array
    {
        return [new TwigFunction('template_from_string', [self::class, 'templateFromString'], ['needs_environment' => \true])];
    }
    /**
     * Loads a template from a string.
     *
     *     {{ include(template_from_string("Hello {{ name }}")) }}
     *
     * Never expose `template_from_string` to untrusted template
     * authors (like in a sandboxed environment). See the docs for more details.
     *
     * @param string|null $name An optional name of the template to be used in error messages
     *
     * @internal
     */
    public static function templateFromString(Environment $env, string|\Stringable $template, ?string $name = null) : TemplateWrapper
    {
        return $env->createTemplate((string) $template, $name);
    }
}
