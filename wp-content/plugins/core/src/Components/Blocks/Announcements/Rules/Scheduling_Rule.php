<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks\Announcements\Rules;

use Tribe\Plugin\Object_Meta\Post_Types\Announcement_Meta;

class Scheduling_Rule implements Rule_Interface {

	public function passes( \WP_Post $announcement, array $context ): bool {
		$enabled_raw = get_field( Announcement_Meta::SCHEDULED, $announcement->ID );
		$enabled     = $enabled_raw === true || $enabled_raw === 1 || $enabled_raw === '1';

		if ( ! $enabled ) {
			return true;
		}

		$start_time   = get_field( Announcement_Meta::SCHEDULING_START_TIME, $announcement->ID );
		$end_time     = get_field( Announcement_Meta::SCHEDULING_END_TIME, $announcement->ID );
		$current_time = $context['current_time'] ?? current_time( 'U' );

		// If no scheduling is set, the announcement passes.
		if ( empty( $start_time ) && empty( $end_time ) ) {
			return true;
		}

		// Check if current time is after start time (if set).
		if ( ! empty( $start_time ) && $current_time < (int) $start_time ) {
			return false;
		}

		// Check if current time is before end time (if set).
		return empty( $end_time ) || $current_time <= (int) $end_time;
	}

	public function get_name(): string {
		return 'scheduling';
	}

}
