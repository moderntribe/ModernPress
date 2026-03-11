<?php declare(strict_types=1);

namespace PatternSync\Sync;

use DI;
use PatternSync\Connections\Connection_Manager;
use PatternSync\Core\Interfaces\Definer_Interface;
use PatternSync\Patterns\Pattern_Registry_Service;

class Sync_Definer implements Definer_Interface {

	public function define(): array {
		return [
			Remote_Client::class => DI\create()->constructor( DI\get( Connection_Manager::class ) ),
			Sync_Service::class  => DI\create()->constructor(
				DI\get( Connection_Manager::class ),
				DI\get( Pattern_Registry_Service::class ),
				DI\get( Remote_Client::class )
			),
		];
	}

}
