<?php declare(strict_types=1);

namespace PatternSync\Rest;

use DI;
use PatternSync\Core\Interfaces\Definer_Interface;
use PatternSync\Rest\Controllers\Patterns_Controller;

class Rest_Definer implements Definer_Interface {

	public function define(): array {
		return [
			Patterns_Controller::class => DI\create()->constructor( DI\get( \PatternSync\Patterns\Pattern_Registry_Service::class ) ),
		];
	}

}
