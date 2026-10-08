<?php declare(strict_types=1);

namespace Tribe\Plugin\Settings;

use Extended\ACF\Fields\Image;

class Login_Settings extends Settings_Sub_Page {

	public const PAGE_SLUG = 'login-settings';

	public const LOGIN_LOGO = 'login_logo';

	public function get_title(): string {
		return esc_html__( 'Login', 'tribe' );
	}

	public function get_parent_slug(): string {
		return Tribe_Settings::PAGE_SLUG;
	}

	public function get_fields(): array {
		return [
			Image::make( esc_html__( 'Login Logo', 'tribe' ), self::LOGIN_LOGO )
				->helperText( esc_html__( 'An SVG image file is the recommended file type for this image. If a PNG or JPG image is used the recommended dimensions are 900px by 200px.', 'tribe' ) )
				->format( 'id' )
				->previewSize( 'thumbnail' ),
		];
	}

}
