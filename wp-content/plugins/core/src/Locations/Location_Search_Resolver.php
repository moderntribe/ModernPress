<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations;

/**
 * Determines whether a geocoded place should use radius or area-based location queries.
 */
class Location_Search_Resolver {

	/**
	 * Google result types that should use radius search.
	 */
	private const array RADIUS_TYPES = [
		'street_address',
		'premise',
		'subpremise',
		'route',
		'street_number',
		'establishment',
		'point_of_interest',
		'intersection',
		'plus_code',
		'floor',
		'room',
	];

	public function __construct(
		private Us_States $us_states,
		private Location_Data $location_data,
	) {
	}

	/**
	 * @param array<string, mixed> $result Google Geocoding API result object.
	 *
	 * @return array{
	 *     mode: 'radius'|'area',
	 *     scope?: 'state'|'city'|'zip',
	 *     state?: string,
	 *     city?: string,
	 *     zip?: string
	 * }
	 */
	public function from_geocode_result( array $result ): array {
		$types      = array_values( array_filter( (array) ( $result['types'] ?? [] ), 'is_string' ) );
		$components = $this->parse_address_components( (array) ( $result['address_components'] ?? [] ) );

		if ( $this->should_use_radius_search( $types ) ) {
			return [
				'mode' => 'radius',
			];
		}

		if ( $this->has_type( $types, 'postal_code' ) && $components['zip'] !== '' ) {
			return [
				'mode'  => 'area',
				'scope' => 'zip',
				'zip'   => $components['zip'],
				'state' => $components['state'],
			];
		}

		if (
			( $this->has_type( $types, 'locality' ) || $this->has_type( $types, 'sublocality' ) )
			&& $components['city'] !== ''
		) {
			return [
				'mode'  => 'area',
				'scope' => 'city',
				'city'  => $components['city'],
				'state' => $components['state'],
			];
		}

		if ( $this->has_type( $types, 'administrative_area_level_1' ) && $components['state'] !== '' ) {
			return [
				'mode'  => 'area',
				'scope' => 'state',
				'state' => $components['state'],
			];
		}

		return [
			'mode' => 'radius',
		];
	}

	/**
	 * @param array<int, array<string, mixed>> $components
	 *
	 * @return array{state: string, city: string, zip: string}
	 */
	public function parse_address_components( array $components ): array {
		$parsed = [
			'state' => '',
			'city'  => '',
			'zip'   => '',
		];

		foreach ( $components as $component ) {
			if ( ! is_array( $component ) ) {
				continue;
			}

			$types = array_values( array_filter( (array) ( $component['types'] ?? [] ), 'is_string' ) );

			if ( $this->has_type( $types, 'administrative_area_level_1' ) && $parsed['state'] === '' ) {
				$parsed['state'] = $this->us_states->normalize_to_code(
					(string) ( $component['short_name'] ?? $component['long_name'] ?? '' )
				);
			}

			if ( $this->has_type( $types, 'locality' ) && $parsed['city'] === '' ) {
				$parsed['city'] = trim( (string) ( $component['long_name'] ?? '' ) );
			}

			if (
				$parsed['city'] === ''
				&& $this->has_type( $types, 'sublocality' )
			) {
				$parsed['city'] = trim( (string) ( $component['long_name'] ?? '' ) );
			}

			if ( ! $this->has_type( $types, 'postal_code' ) || $parsed['zip'] !== '' ) {
				continue;
			}

			$parsed['zip'] = $this->location_data->normalize_zip( (string) ( $component['long_name'] ?? '' ) );
		}

		return $parsed;
	}

	/**
	 * @param string[] $types
	 */
	private function should_use_radius_search( array $types ): bool {
		return (bool) array_intersect( $types, self::RADIUS_TYPES );
	}

	/**
	 * @param string[] $types
	 */
	private function has_type( array $types, string $needle ): bool {
		return in_array( $needle, $types, true );
	}

}
