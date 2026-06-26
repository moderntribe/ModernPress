<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations;

use Tribe\Plugin\Object_Meta\Post_Types\Location_Meta;
use Tribe\Plugin\Post_Types\Location\Location;

class Location_Data {

	public function __construct(
		private Us_States $us_states,
	) {
	}

	public function normalize_zip( string $zip ): string {
		$digits = preg_replace( '/\D/', '', $zip );

		if ( ! is_string( $digits ) || strlen( $digits ) < 5 ) {
			return trim( $zip );
		}

		return substr( $digits, 0, 5 );
	}

	public function normalize_city( string $city ): string {
		return strtolower( trim( $city ) );
	}

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
	public function from_post( int $post_id ): array {
		$address = $this->get_formatted_address( $post_id );

		$data = [
			'id'            => $post_id,
			'title'         => get_the_title( $post_id ),
			'url'           => (string) get_permalink( $post_id ),
			'lat'           => $this->get_coordinate( $post_id, Location_Meta::LATITUDE ),
			'lng'           => $this->get_coordinate( $post_id, Location_Meta::LONGITUDE ),
			'address'       => $address,
			'phone'         => (string) $this->get_field( $post_id, Location_Meta::PHONE ),
			'email'         => (string) $this->get_field( $post_id, Location_Meta::EMAIL ),
			'hours'         => (string) $this->get_field( $post_id, Location_Meta::HOURS ),
			'directionsUrl' => $this->get_directions_url( $address ),
		];

		/**
		 * Filter normalized location data used by the map block and REST API.
		 *
		 * @param array $data    Normalized location data.
		 * @param int   $post_id Location post ID.
		 */
		return apply_filters( 'tribe_location_map_location_from_post', $data, $post_id );
	}

	public function get_formatted_address( int $post_id ): string {
		$line_1 = trim( (string) $this->get_field( $post_id, Location_Meta::ADDRESS_LINE_1 ) );
		$line_2 = trim( (string) $this->get_field( $post_id, Location_Meta::ADDRESS_LINE_2 ) );
		$city   = trim( (string) $this->get_field( $post_id, Location_Meta::ADDRESS_CITY ) );
		$state  = trim( (string) $this->get_field( $post_id, Location_Meta::ADDRESS_STATE ) );
		$zip    = trim( (string) $this->get_field( $post_id, Location_Meta::ADDRESS_ZIP ) );

		$street     = trim( implode( ' ', array_filter( [ $line_1, $line_2 ] ) ) );
		$city_state = implode( ', ', array_filter( [ $city, $state ] ) );
		$address    = implode( ', ', array_filter( [ $street, $city_state ] ) );

		if ( $zip !== '' ) {
			$address = trim( $address . ' ' . $zip );
		}

		return $address;
	}

	public function get_address_hash( int $post_id ): string {
		return wp_hash( $this->get_formatted_address( $post_id ) );
	}

	public function get_directions_url( string $address ): string {
		if ( $address === '' ) {
			return '';
		}

		return 'https://www.google.com/maps?daddr=' . rawurlencode( $address );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_locations_by_ids( array $post_ids ): array {
		$locations = [];

		foreach ( array_map( 'absint', $post_ids ) as $post_id ) {
			if ( $post_id <= 0 || Location::NAME !== get_post_type( $post_id ) ) {
				continue;
			}

			if ( 'publish' !== get_post_status( $post_id ) ) {
				continue;
			}

			$location = $this->from_post( $post_id );

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
	public function get_locations_nearby( float $lat, float $lng, float $radius_miles = 30.0 ): array {
		$bounds = $this->get_bounding_box( $lat, $lng, $radius_miles );

		$query = new \WP_Query( [
			'post_type'      => Location::NAME,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [
				'relation' => 'AND',
				[
					'key'     => Location_Meta::LATITUDE,
					'value'   => [ $bounds['min_lat'], $bounds['max_lat'] ],
					'type'    => 'DECIMAL',
					'compare' => 'BETWEEN',
				],
				[
					'key'     => Location_Meta::LONGITUDE,
					'value'   => [ $bounds['min_lng'], $bounds['max_lng'] ],
					'type'    => 'DECIMAL',
					'compare' => 'BETWEEN',
				],
			],
		] );

		$locations = [];

		foreach ( $query->posts as $post_id ) {
			$location_lat = $this->get_coordinate( (int) $post_id, Location_Meta::LATITUDE );
			$location_lng = $this->get_coordinate( (int) $post_id, Location_Meta::LONGITUDE );

			if ( null === $location_lat || null === $location_lng ) {
				continue;
			}

			$distance = $this->get_distance_in_miles( $lat, $lng, $location_lat, $location_lng );

			if ( $distance > $radius_miles ) {
				continue;
			}

			$location             = $this->from_post( (int) $post_id );
			$location['distance'] = round( $distance, 2 );
			$locations[]          = $location;
		}

		usort(
			$locations,
			static fn( array $left, array $right ): int => ( $left['distance'] ?? 0 ) <=> ( $right['distance'] ?? 0 )
		);

		return $locations;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_locations_by_state( string $state ): array {
		$match_values = $this->us_states->get_match_values( $state );

		if ( empty( $match_values ) ) {
			return [];
		}

		$query = new \WP_Query( [
			'post_type'      => Location::NAME,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [
				[
					'key'     => Location_Meta::ADDRESS_STATE,
					'value'   => $match_values,
					'compare' => 'IN',
				],
			],
		] );

		return $this->get_locations_by_ids( $query->posts );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_locations_by_city( string $city, string $state ): array {
		$match_values = $this->us_states->get_match_values( $state );
		$city         = trim( $city );

		if ( $city === '' || empty( $match_values ) ) {
			return [];
		}

		$normalized_city = $this->normalize_city( $city );

		$query = new \WP_Query( [
			'post_type'      => Location::NAME,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [
				[
					'key'     => Location_Meta::ADDRESS_STATE,
					'value'   => $match_values,
					'compare' => 'IN',
				],
			],
		] );

		$locations = [];

		foreach ( $query->posts as $post_id ) {
			$stored_city = $this->normalize_city(
				(string) $this->get_field( (int) $post_id, Location_Meta::ADDRESS_CITY )
			);

			if ( $stored_city !== $normalized_city ) {
				continue;
			}

			$location = $this->from_post( (int) $post_id );

			if ( null === $location['lat'] || null === $location['lng'] ) {
				continue;
			}

			$locations[] = $location;
		}

		usort(
			$locations,
			static fn( array $left, array $right ): int => strcasecmp(
				(string) ( $left['title'] ?? '' ),
				(string) ( $right['title'] ?? '' )
			)
		);

		return $locations;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_locations_by_zip( string $zip ): array {
		$zip = $this->normalize_zip( $zip );

		if ( strlen( $zip ) < 5 ) {
			return [];
		}

		$query = new \WP_Query( [
			'post_type'      => Location::NAME,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => [
				[
					'key'     => Location_Meta::ADDRESS_ZIP,
					'value'   => $zip,
					'compare' => 'LIKE',
				],
			],
		] );

		$locations = [];

		foreach ( $query->posts as $post_id ) {
			$stored_zip = $this->normalize_zip(
				(string) $this->get_field( (int) $post_id, Location_Meta::ADDRESS_ZIP )
			);

			if ( $stored_zip !== $zip ) {
				continue;
			}

			$location = $this->from_post( (int) $post_id );

			if ( null === $location['lat'] || null === $location['lng'] ) {
				continue;
			}

			$locations[] = $location;
		}

		usort(
			$locations,
			static fn( array $left, array $right ): int => strcasecmp(
				(string) ( $left['title'] ?? '' ),
				(string) ( $right['title'] ?? '' )
			)
		);

		return $locations;
	}

	public function get_distance_in_miles( float $lat_a, float $lng_a, float $lat_b, float $lng_b ): float {
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

	private function get_field( int $post_id, string $key ): mixed {
		if ( function_exists( 'get_field' ) ) {
			$value = get_field( $key, $post_id );

			if ( null !== $value && $value !== false && $value !== '' ) {
				return $value;
			}
		}

		return get_post_meta( $post_id, $key, true );
	}

	/**
	 * @return array{min_lat: float, max_lat: float, min_lng: float, max_lng: float}
	 */
	private function get_bounding_box( float $lat, float $lng, float $radius_miles ): array {
		$lat_delta = $radius_miles / 69.0;
		$lng_delta = $radius_miles / max( 69.0 * cos( deg2rad( $lat ) ), 0.0001 );

		return [
			'min_lat' => $lat - $lat_delta,
			'max_lat' => $lat + $lat_delta,
			'min_lng' => $lng - $lng_delta,
			'max_lng' => $lng + $lng_delta,
		];
	}

	private function get_coordinate( int $post_id, string $key ): ?float {
		$value = $this->get_field( $post_id, $key );

		if ( $value === '' || $value === null || $value === false ) {
			return null;
		}

		return (float) $value;
	}

}
