<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations\Geocoding;

interface Geocoder_Interface {

	/**
	 * @return array{lat: float, lng: float}|null
	 */
	public function geocode( string $address ): ?array;

}
