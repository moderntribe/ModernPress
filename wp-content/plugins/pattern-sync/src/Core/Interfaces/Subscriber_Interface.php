<?php declare(strict_types=1);

namespace PatternSync\Core\Interfaces;

interface Subscriber_Interface {

	/**
	 * Register action/filter listeners to hook into WordPress
	 */
	public function register(): void;

}
