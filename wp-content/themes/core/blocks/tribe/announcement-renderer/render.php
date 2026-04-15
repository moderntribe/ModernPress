<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Announcements\Announcement_Controller;
use Tribe\Plugin\Components\Blocks\Announcements\Announcement_Renderer;

$placement = $attributes['placement'] ?? null;

if ( null === $placement ) {
	return;
}

$controller = Announcement_Controller::factory();
$renderer   = new Announcement_Renderer( $controller );

echo $renderer->parse_and_render( $placement );
