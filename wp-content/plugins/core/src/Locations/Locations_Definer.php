<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations;

use DI;
use Psr\Container\ContainerInterface;
use Tribe\Plugin\Core\Interfaces\Definer_Interface;
use Tribe\Plugin\Locations\Geocoding\Geocoder_Interface;
use Tribe\Plugin\Locations\Geocoding\Google_Maps_Geocoder;
use Tribe\Plugin\Locations\Geocoding\Nominatim_Geocoder;
use Tribe\Plugin\Settings\Tribe_Settings;

class Locations_Definer implements Definer_Interface {

	public function define(): array {
		return [
			Geocoder_Interface::class => DI\factory(
				static function ( ContainerInterface $container ): Geocoder_Interface {
					$settings = $container->get( Tribe_Settings::class );

					if ( $settings->is_google_geocoder_active() ) {
						return $container->get( Google_Maps_Geocoder::class );
					}

					return $container->get( Nominatim_Geocoder::class );
				}
			),
		];
	}

}
