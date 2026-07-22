<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations;

use Tribe\Plugin\Core\Interfaces\Definer_Interface;
use Tribe\Plugin\Locations\Geocoding\Geocoder_Interface;
use Tribe\Plugin\Locations\Geocoding\Google_Maps_Geocoder;

class Locations_Definer implements Definer_Interface {

	public function define(): array {
		return [
			Geocoder_Interface::class => \DI\get( Google_Maps_Geocoder::class ),
		];
	}

}
