<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks\Announcements\Rules;

use Tribe\Plugin\Object_Meta\Post_Types\Announcement_Meta;

class Placement_Rule implements Rule_Interface {

	public function passes( \WP_Post $announcement, array $context ): bool {
		$placement           = get_field( Announcement_Meta::PLACEMENT, $announcement->ID );
		$requested_placement = $context['placement'] ?? '';

		return $placement === $requested_placement;
	}

	public function get_name(): string {
		return 'placement';
	}

}
