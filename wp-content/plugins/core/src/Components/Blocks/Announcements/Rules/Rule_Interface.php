<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks\Announcements\Rules;

interface Rule_Interface {

	/**
	 * Check if an announcement passes this rule
	 *
	 * @param \WP_Post $announcement
	 * @param array $context
	 */
	public function passes( \WP_Post $announcement, array $context ): bool;

	/**
	 * Get the rule name for debugging
	 */
	public function get_name(): string;

}
