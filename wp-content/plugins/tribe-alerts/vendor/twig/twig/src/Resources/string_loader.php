<?php

namespace Tribe\Alert_Scoped;

/*
 * This file is part of Twig.
 *
 * (c) Fabien Potencier
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use Tribe\Alert_Scoped\Twig\Environment;
use Tribe\Alert_Scoped\Twig\Extension\StringLoaderExtension;
use Tribe\Alert_Scoped\Twig\TemplateWrapper;
/**
 * @internal
 *
 * @deprecated since Twig 3.9
 */
function twig_template_from_string(Environment $env, $template, ?string $name = null) : TemplateWrapper
{
    \trigger_deprecation('twig/twig', '3.9', 'Using the internal "%s" function is deprecated.', __FUNCTION__);
    return StringLoaderExtension::templateFromString($env, $template, $name);
}
