<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks\Announcements\Rules;

use Tribe\Plugin\Object_Meta\Post_Types\Announcement_Meta;

class Front_Page_Rule implements Rule_Interface {

	public function passes( \WP_Post $announcement, array $context ): bool {
		$is_front_page = $context['is_front_page'] ?? is_front_page();

		if ( ! $is_front_page ) {
			return true;
		}

		$display_type        = get_field( Announcement_Meta::FIELD_RULES_DISPLAY_TYPE, $announcement->ID );
		$apply_to_front_page = get_field( Announcement_Meta::FIELD_RULES_APPLY_TO_FRONT_PAGE, $announcement->ID );

		// If not applying rules to front page, always show.
		if ( ! $apply_to_front_page ) {
			return true;
		}

		// If set to show everywhere, always show.
		if ( $display_type === Announcement_Meta::OPTION_EVERY_PAGE ) {
			return true;
		}

		// For include rule: show on front page
		// For exclude rule: don't show on front page
		return $display_type === Announcement_Meta::OPTION_INCLUDE;
	}

	public function get_name(): string {
		return 'front_page';
	}

}
