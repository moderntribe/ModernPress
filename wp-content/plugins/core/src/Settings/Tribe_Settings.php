<?php declare(strict_types=1);

namespace Tribe\Plugin\Settings;

use Extended\ACF\ConditionalLogic;
use Extended\ACF\Fields\Tab;
use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\TrueFalse;

class Tribe_Settings extends Settings_Sub_Page {

	public const PAGE_SLUG                      = 'tribe-settings';
	public const MAPS_TAB                       = 'maps_tab';
	public const GOOGLE_MAPS_API_KEY            = 'google_maps_api_key';
	public const GOOGLE_MAPS_MAP_ID             = 'google_maps_map_id';
	public const DEFAULT_GOOGLE_MAPS_MAP_ID     = 'DEMO_MAP_ID';
	public const AUTOCOMPLETE_ENABLED           = 'google_maps_autocomplete_enabled';
	public const AUTOCOMPLETE_MIN_CHARS         = 'google_maps_autocomplete_min_chars';
	public const DEFAULT_AUTOCOMPLETE_MIN_CHARS = 3;
	public const AUTOCOMPLETE_DEBOUNCE          = 'google_maps_autocomplete_debounce';
	public const DEFAULT_AUTOCOMPLETE_DEBOUNCE  = 500;

	public function get_title(): string {
		return esc_html__( 'Tribe Settings', 'tribe' );
	}

	public function get_parent_slug(): string {
		return 'options-general.php';
	}

	public function get_google_maps_api_key(): string {
		return trim( (string) $this->get_setting( self::GOOGLE_MAPS_API_KEY, '' ) );
	}

	public function has_google_maps_api_key(): bool {
		return $this->get_google_maps_api_key() !== '';
	}

	public function get_google_maps_map_id(): string {
		$map_id = trim( (string) $this->get_setting( self::GOOGLE_MAPS_MAP_ID, self::DEFAULT_GOOGLE_MAPS_MAP_ID ) );

		return $map_id !== '' ? $map_id : self::DEFAULT_GOOGLE_MAPS_MAP_ID;
	}

	public function is_google_maps_autocomplete_enabled(): bool {
		return (bool) $this->get_setting( self::AUTOCOMPLETE_ENABLED, true );
	}

	public function get_google_maps_autocomplete_min_chars(): int {
		$value = $this->get_setting( self::AUTOCOMPLETE_MIN_CHARS, (string) self::DEFAULT_AUTOCOMPLETE_MIN_CHARS );

		if ( ! is_numeric( $value ) ) {
			return self::DEFAULT_AUTOCOMPLETE_MIN_CHARS;
		}

		return max( 1, min( 20, (int) $value ) );
	}

	public function get_google_maps_autocomplete_debounce(): int {
		$value = $this->get_setting( self::AUTOCOMPLETE_DEBOUNCE, (string) self::DEFAULT_AUTOCOMPLETE_DEBOUNCE );

		if ( ! is_numeric( $value ) ) {
			return self::DEFAULT_AUTOCOMPLETE_DEBOUNCE;
		}

		return max( 0, min( 5000, (int) $value ) );
	}

	/**
	 * @return \Extended\ACF\Fields\Field[]
	 */
	protected function get_fields(): array {
		return [
			Tab::make( esc_html__( 'Maps', 'tribe' ), self::MAPS_TAB )
				->placement( 'left' ),
			Text::make( esc_html__( 'Google Maps API Key', 'tribe' ), self::GOOGLE_MAPS_API_KEY )
				->helperText(
					esc_html__(
						'Required for the Location Map block and address geocoding. Enable the Maps JavaScript API, Places API, and Geocoding API for this key in Google Cloud Console.',
						'tribe'
					)
				),
			Text::make( esc_html__( 'Google Maps Map ID', 'tribe' ), self::GOOGLE_MAPS_MAP_ID )
				->default( self::DEFAULT_GOOGLE_MAPS_MAP_ID )
				->helperText(
					esc_html__(
						'Map ID for Advanced Markers. Create one in Google Cloud Console under Map Management, or use `DEMO_MAP_ID` for testing.',
						'tribe'
					)
				),
			TrueFalse::make(
				esc_html__( 'Enable address autocomplete', 'tribe' ),
				self::AUTOCOMPLETE_ENABLED
			)
				->default( true )
				->stylized( esc_html__( 'Yes', 'tribe' ), esc_html__( 'No', 'tribe' ) )
				->helperText(
					esc_html__(
						'When disabled, visitors search by pressing Enter and results are geocoded server-side.',
						'tribe'
					)
				),
			Text::make(
				esc_html__( 'Autocomplete minimum characters', 'tribe' ),
				self::AUTOCOMPLETE_MIN_CHARS
			)
				->default( (string) self::DEFAULT_AUTOCOMPLETE_MIN_CHARS )
				->helperText(
					esc_html__(
						'Number of characters a visitor must type before address autocomplete suggestions appear.',
						'tribe'
					)
				)
				->conditionalLogic( [
					ConditionalLogic::where( self::AUTOCOMPLETE_ENABLED, '==', 1 ),
				] ),
			Text::make(
				esc_html__( 'Autocomplete debounce (ms)', 'tribe' ),
				self::AUTOCOMPLETE_DEBOUNCE
			)
				->default( (string) self::DEFAULT_AUTOCOMPLETE_DEBOUNCE )
				->helperText(
					esc_html__(
						'Delay in milliseconds after typing stops before autocomplete suggestions are requested.',
						'tribe'
					)
				)
				->conditionalLogic( [
					ConditionalLogic::where( self::AUTOCOMPLETE_ENABLED, '==', 1 ),
				] ),
		];
	}

}
