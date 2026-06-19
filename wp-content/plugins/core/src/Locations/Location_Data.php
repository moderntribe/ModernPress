<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations;

use Tribe\Plugin\Object_Meta\Post_Types\Location_Meta;
use Tribe\Plugin\Post_Types\Location\Location;

class Location_Data {

	/**
	 * @return array{
	 *     id: int,
	 *     title: string,
	 *     url: string,
	 *     lat: float|null,
	 *     lng: float|null,
	 *     address: string,
	 *     phone: string,
	 *     email: string,
	 *     hours: string,
	 *     directionsUrl: string
	 * }
	 */
	public static function from_post( int $post_id ): array {
		$address = self::get_formatted_address( $post_id );

		$data = [
			'id'            => $post_id,
			'title'         => get_the_title( $post_id ),
			'url'           => (string) get_permalink( $post_id ),
			'lat'           => self::get_coordinate( $post_id, Location_Meta::LATITUDE ),
			'lng'           => self::get_coordinate( $post_id, Location_Meta::LONGITUDE ),
			'address'       => $address,
			'phone'         => (string) self::get_field( $post_id, Location_Meta::PHONE ),
			'email'         => (string) self::get_field( $post_id, Location_Meta::EMAIL ),
			'hours'         => (string) self::get_field( $post_id, Location_Meta::HOURS ),
			'directionsUrl' => self::get_directions_url( $address ),
		];

		/**
		 * Filter normalized location data used by the map block and REST API.
		 *
		 * @param array $data    Normalized location data.
		 * @param int   $post_id Location post ID.
		 */
		return apply_filters( 'tribe_location_map_location_from_post', $data, $post_id );
	}

	public static function get_formatted_address( int $post_id ): string {
		$line_1 = trim( (string) self::get_field( $post_id, Location_Meta::ADDRESS_LINE_1 ) );
		$line_2 = trim( (string) self::get_field( $post_id, Location_Meta::ADDRESS_LINE_2 ) );
		$city   = trim( (string) self::get_field( $post_id, Location_Meta::ADDRESS_CITY ) );
		$state  = trim( (string) self::get_field( $post_id, Location_Meta::ADDRESS_STATE ) );
		$zip    = trim( (string) self::get_field( $post_id, Location_Meta::ADDRESS_ZIP ) );

		$street     = trim( implode( ' ', array_filter( [ $line_1, $line_2 ] ) ) );
		$city_state = implode( ', ', array_filter( [ $city, $state ] ) );
		$address    = implode( ', ', array_filter( [ $street, $city_state ] ) );

		if ( $zip !== '' ) {
			$address = trim( $address . ' ' . $zip );
		}

		return $address;
	}

	public static function get_address_hash( int $post_id ): string {
		return wp_hash( self::get_formatted_address( $post_id ) );
	}

	public static function get_directions_url( string $address ): string {
		if ( $address === '' ) {
			return '';
		}

		return 'https://www.google.com/maps?daddr=' . rawurlencode( $address );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_locations_by_ids( array $post_ids ): array {
		$locations = [];

		foreach ( array_map( 'absint', $post_ids ) as $post_id ) {
			if ( $post_id <= 0 || Location::NAME !== get_post_type( $post_id ) ) {
				continue;
			}

			if ( 'publish' !== get_post_status( $post_id ) ) {
				continue;
			}

			$location = self::from_post( $post_id );

			if ( null === $location['lat'] || null === $location['lng'] ) {
				continue;
			}

			$locations[] = $location;
		}

		return $locations;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_locations_nearby( float $lat, float $lng, float $radius_miles = 30.0 ): array {
		$query = new \WP_Query( [
			'post_type'      => Location::NAME,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		] );

		$locations = [];

		foreach ( $query->posts as $post_id ) {
			$location_lat = self::get_coordinate( (int) $post_id, Location_Meta::LATITUDE );
			$location_lng = self::get_coordinate( (int) $post_id, Location_Meta::LONGITUDE );

			if ( null === $location_lat || null === $location_lng ) {
				continue;
			}

			$distance = self::get_distance_in_miles( $lat, $lng, $location_lat, $location_lng );

			if ( $distance > $radius_miles ) {
				continue;
			}

			$location             = self::from_post( (int) $post_id );
			$location['distance'] = round( $distance, 2 );
			$locations[]          = $location;
		}

		usort(
			$locations,
			static fn( array $left, array $right ): int => ( $left['distance'] ?? 0 ) <=> ( $right['distance'] ?? 0 )
		);

		return $locations;
	}

	public static function get_distance_in_miles( float $lat_a, float $lng_a, float $lat_b, float $lng_b ): float {
		$earth_radius = 3959;
		$lat_delta    = deg2rad( $lat_b - $lat_a );
		$lng_delta    = deg2rad( $lng_b - $lng_a );
		$lat_a_rad    = deg2rad( $lat_a );
		$lat_b_rad    = deg2rad( $lat_b );

		$a = sin( $lat_delta / 2 ) ** 2
			+ cos( $lat_a_rad ) * cos( $lat_b_rad ) * sin( $lng_delta / 2 ) ** 2;

		$c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );

		return $earth_radius * $c;
	}

	private static function get_field( int $post_id, string $key ): mixed {
		if ( function_exists( 'get_field' ) ) {
			$value = get_field( $key, $post_id );

			if ( null !== $value && $value !== false && $value !== '' ) {
				return $value;
			}
		}

		return get_post_meta( $post_id, $key, true );
	}

	private static function get_coordinate( int $post_id, string $key ): ?float {
		$value = self::get_field( $post_id, $key );

		if ( $value === '' || $value === null || $value === false ) {
			return null;
		}

		return (float) $value;
	}

}
