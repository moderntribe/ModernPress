<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

use DI;
use Tribe\Plugin\Core\Interfaces\Definer_Interface;

class Facets_Definer implements Definer_Interface {

	public function define(): array {
		return [
			Facet_Registry::class   => DI\create( Facet_Registry::class ),
			Facet_Index::class      => DI\autowire( Facet_Index::class ),
			Directory_Query::class  => DI\autowire( Directory_Query::class ),
			Facet_Renderer::class   => DI\autowire( Facet_Renderer::class ),
			Results_Endpoint::class => DI\autowire( Results_Endpoint::class ),
		];
	}

}
