<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

use DI;
use Tribe\Plugin\Core\Interfaces\Definer_Interface;

class Facets_Definer implements Definer_Interface {

	public function define(): array {
		return [
			\wpdb::class                 => DI\factory( static fn (): \wpdb => $GLOBALS['wpdb'] ),
			Facet_Registry::class        => DI\create( Facet_Registry::class ),
			Facet_Index_Table::class     => DI\autowire( Facet_Index_Table::class ),
			Facet_Index_Writer::class    => DI\autowire( Facet_Index_Writer::class ),
			Facet_Index_Rebuilder::class => DI\autowire( Facet_Index_Rebuilder::class ),
			Facet_Index_Sync::class      => DI\autowire( Facet_Index_Sync::class ),
			Facet_Index::class           => DI\autowire( Facet_Index::class ),
			Facet_Index_Admin::class     => DI\autowire( Facet_Index_Admin::class ),
			Facet_Terms::class           => DI\autowire( Facet_Terms::class ),
			Directory_Query::class       => DI\autowire( Directory_Query::class ),
			Facet_Renderer::class        => DI\autowire( Facet_Renderer::class ),
			Results_Endpoint::class      => DI\autowire( Results_Endpoint::class ),
		];
	}

}
