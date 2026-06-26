<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations;

use Tribe\Plugin\Core\Abstract_Subscriber;

class Location_Geocode_Subscriber extends Abstract_Subscriber {

	public function register(): void {
		add_action(
			'acf/save_post',
			function ( int|string $post_id ): void {
				if ( ! is_numeric( $post_id ) ) {
					return;
				}

				$this->container->get( Location_Geocoder::class )->maybe_geocode_post( (int) $post_id );
			},
			20
		);
	}

}
