<?php declare(strict_types=1);

namespace PatternSync\Patterns;

use PatternSync\Core\Abstract_Subscriber;

class Patterns_Subscriber extends Abstract_Subscriber {

	public function register(): void {
		add_action( 'init', [ $this, 'register_stored_patterns' ], 10, 0 );
	}

	public function register_stored_patterns(): void {
		$this->container->get( Pattern_Registry_Service::class )->register_stored_patterns();
	}

}
