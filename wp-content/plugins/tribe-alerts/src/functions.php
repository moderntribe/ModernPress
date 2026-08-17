<?php

declare (strict_types=1);
namespace Tribe\Alert;

use Tribe\Alert\Components\Alert\Alert_Controller;
/**
 * Render the alert output.
 *
 * @action wp_footer
 *
 * @throws \Psr\Container\ContainerExceptionInterface
 * @throws \Psr\Container\NotFoundExceptionInterface
 */
function render_alert() : void
{
    if (\is_404()) {
        return;
    }
    tribe_alert()->get_container()->get(Alert_Controller::class)->render();
}
/**
 * Format content that is not already formatted out of the box by WordPress.
 *
 * @param string $content The content to format.
 *
 * @return string The formatted content.
 */
function format_content(string $content) : string
{
    return \convert_chars(\wptexturize($content));
}
