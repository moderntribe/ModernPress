<?php declare(strict_types=1);

namespace Tribe\Plugin\Object_Meta\Post_Types;

use Extended\ACF\Fields\Text;
use Extended\ACF\Fields\Textarea;
use Extended\ACF\Location as ACFLocation;
use Tribe\Plugin\Object_Meta\Meta_Object;
use Tribe\Plugin\Post_Types\Location\Location as Location_Post_Type;

class Location_Meta extends Meta_Object {

	public const string GROUP_SLUG     = 'location_meta';
	public const string ADDRESS_LINE_1 = 'address_line_1';
	public const string ADDRESS_LINE_2 = 'address_line_2';
	public const string ADDRESS_CITY   = 'address_city';
	public const string ADDRESS_STATE  = 'address_state';
	public const string ADDRESS_ZIP    = 'address_zip';
	public const string PHONE          = 'phone';
	public const string EMAIL          = 'email';
	public const string HOURS          = 'hours';
	public const string LATITUDE       = 'latitude';
	public const string LONGITUDE      = 'longitude';
	public const string ADDRESS_HASH   = 'address_geocode_hash';

	public function get_slug(): string {
		return self::GROUP_SLUG;
	}

	public function get_title(): string {
		return esc_html__( 'Location Details', 'tribe' );
	}

	public function get_fields(): array {
		return [
			Text::make( esc_html__( 'Address Line 1', 'tribe' ), self::ADDRESS_LINE_1 )
				->required(),
			Text::make( esc_html__( 'Address Line 2', 'tribe' ), self::ADDRESS_LINE_2 ),
			Text::make( esc_html__( 'City', 'tribe' ), self::ADDRESS_CITY )
				->required(),
			Text::make( esc_html__( 'State / Province', 'tribe' ), self::ADDRESS_STATE )
				->required(),
			Text::make( esc_html__( 'ZIP / Postal Code', 'tribe' ), self::ADDRESS_ZIP )
				->required(),
			Text::make( esc_html__( 'Phone', 'tribe' ), self::PHONE ),
			Text::make( esc_html__( 'Email', 'tribe' ), self::EMAIL ),
			Textarea::make( esc_html__( 'Hours', 'tribe' ), self::HOURS )
				->rows( 4 )
				->helperText( esc_html__( 'Display hours for this location (e.g. Mon–Fri 9am–5pm).', 'tribe' ) ),
			Text::make( esc_html__( 'Latitude', 'tribe' ), self::LATITUDE )
				->disabled()
				->helperText( esc_html__( 'Auto-populated when the address is saved.', 'tribe' ) ),
			Text::make( esc_html__( 'Longitude', 'tribe' ), self::LONGITUDE )
				->disabled()
				->helperText( esc_html__( 'Auto-populated when the address is saved.', 'tribe' ) ),
		];
	}

	public function get_locations(): array {
		return [
			ACFLocation::where( 'post_type', '=', Location_Post_Type::NAME ),
		];
	}

}
