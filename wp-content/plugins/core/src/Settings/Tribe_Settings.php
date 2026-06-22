<?php declare(strict_types=1);

namespace Tribe\Plugin\Settings;

use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\RadioButton;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Text;

class Tribe_Settings extends Settings_Sub_Page {

	public const PAGE_SLUG           = 'tribe-settings';
	public const MAPS_TAB            = 'maps_tab';
	public const GEOCODER            = 'location_geocoder';
	public const GEOCODER_NOMINATIM  = 'nominatim';
	public const GEOCODER_GOOGLE     = 'google';
	public const GOOGLE_MAPS_API_KEY = 'google_maps_api_key';

	public function get_title(): string {
		return esc_html__( 'Tribe Settings', 'tribe' );
	}

	public function get_parent_slug(): string {
		return 'options-general.php';
	}

	public function get_geocoder_provider(): string {
		$provider = (string) $this->get_setting( self::GEOCODER, self::GEOCODER_NOMINATIM );

		if ( ! in_array( $provider, [ self::GEOCODER_NOMINATIM, self::GEOCODER_GOOGLE ], true ) ) {
			return self::GEOCODER_NOMINATIM;
		}

		return $provider;
	}

	public function get_google_maps_api_key(): string {
		return trim( (string) $this->get_setting( self::GOOGLE_MAPS_API_KEY, '' ) );
	}

	public function is_google_geocoder_active(): bool {
		return self::GEOCODER_GOOGLE === $this->get_geocoder_provider()
			&& $this->get_google_maps_api_key() !== '';
	}

	/**
	 * @return \Extended\ACF\Fields\Field[]
	 */
	protected function get_fields(): array {
		return [
			Tab::make( esc_html__( 'Maps', 'tribe' ), self::MAPS_TAB )
				->placement( 'left' ),
			RadioButton::make( esc_html__( 'Geocoder', 'tribe' ), self::GEOCODER )
				->choices( [
					self::GEOCODER_NOMINATIM => esc_html__( 'OpenStreetMap (Nominatim)', 'tribe' ),
					self::GEOCODER_GOOGLE    => esc_html__( 'Google Maps', 'tribe' ),
				] )
				->default( self::GEOCODER_NOMINATIM )
				->helperText(
					esc_html__(
						'Select the geocoding provider used for location searches and address lookups. Google Maps requires an API key with the Geocoding API enabled.',
						'tribe'
					)
				),
			Text::make( esc_html__( 'Google Maps API Key', 'tribe' ), self::GOOGLE_MAPS_API_KEY )
				->helperText(
					esc_html__(
						'Required when using the Google Maps geocoder. Enable the Geocoding API for this key in Google Cloud Console.',
						'tribe'
					)
				)
				->conditionalLogic( [
					ConditionalLogic::where( self::GEOCODER, '==', self::GEOCODER_GOOGLE ),
				] ),
		];
	}

}
