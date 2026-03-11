<?php declare(strict_types=1);

namespace PatternSync\Connections;

use DI;
use PatternSync\Core\Interfaces\Definer_Interface;

class Connections_Definer implements Definer_Interface {

	public function define(): array {
		return [
			Connection_Manager::class => DI\create()->constructor( DI\get( Connection_Repository::class ) ),
		];
	}

}
