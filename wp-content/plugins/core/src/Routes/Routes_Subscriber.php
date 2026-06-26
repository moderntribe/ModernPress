<?php declare(strict_types=1);

namespace Tribe\Plugin\Routes;

use Tribe\Plugin\Core\Abstract_Subscriber;
use Tribe\Plugin\Routes\API\Geocode_Endpoint;
use Tribe\Plugin\Routes\API\Locations_Endpoint;

class Routes_Subscriber extends Abstract_Subscriber {

	public function register(): void {
		add_action( 'rest_api_init', function (): void {
			$this->container->get( Locations_Endpoint::class )->register_rest_route();
			$this->container->get( Geocode_Endpoint::class )->register_rest_route();
		} );
	}

}
