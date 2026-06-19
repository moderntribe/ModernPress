<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations;

use Tribe\Plugin\Locations\Geocoding\Geocoder_Interface;
use Tribe\Plugin\Object_Meta\Post_Types\Location_Meta;
use Tribe\Plugin\Post_Types\Location\Location;

class Location_Geocoder {

	public function __construct(
		private Geocoder_Interface $geocoder,
	) {
	}

	public function maybe_geocode_post( int $post_id ): void {
		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( Location::NAME !== get_post_type( $post_id ) ) {
			return;
		}

		$address = Location_Data::get_formatted_address( $post_id );

		if ( $address === '' ) {
			return;
		}

		$hash      = Location_Data::get_address_hash( $post_id );
		$hash_prev = (string) get_post_meta( $post_id, Location_Meta::ADDRESS_HASH, true );

		if ( $hash === $hash_prev ) {
			return;
		}

		$coordinates = $this->geocoder->geocode( $address );

		update_post_meta( $post_id, Location_Meta::ADDRESS_HASH, $hash );

		if ( null === $coordinates ) {
			return;
		}

		$this->update_coordinates( $post_id, $coordinates['lat'], $coordinates['lng'] );
	}

	private function update_coordinates( int $post_id, float $lat, float $lng ): void {
		if ( function_exists( 'update_field' ) ) {
			update_field( Location_Meta::LATITUDE, (string) $lat, $post_id );
			update_field( Location_Meta::LONGITUDE, (string) $lng, $post_id );

			return;
		}

		update_post_meta( $post_id, Location_Meta::LATITUDE, (string) $lat );
		update_post_meta( $post_id, Location_Meta::LONGITUDE, (string) $lng );
	}

}
