<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations\Geocoding;

interface Geocoder_Interface {

	/**
	 * @return array{lat: float, lng: float, search?: array<string, mixed>}|null
	 */
	public function geocode( string $address ): ?array;

}
