<?php declare(strict_types=1);

namespace Tribe\Plugin\Settings;

/**
 * Top-level Tribe settings parent (ACF options page).
 */
class Tribe_Settings extends Base_Settings {

	public const string PAGE_SLUG = 'tribe-settings';

	public function get_title(): string {
		return esc_html__( 'Tribe', 'tribe' );
	}

	public function get_capability(): string {
		return 'manage_options';
	}

	public function get_parent_slug(): string {
		return '';
	}

	public function get_setting( string $key, mixed $default = null ): mixed {
		$value = get_field( $key, 'option' );

		return ! empty( $value ) ? $value : $default;
	}

	/**
	 * @param int $priority
	 */
	// phpcs:ignore SlevomatCodingStandard.TypeHints
	public function hook( $priority = 10 ): void {
		if ( ! function_exists( 'acf_add_options_page' ) ) {
			return;
		}

		parent::hook( $priority );
	}

	public function register_settings(): void {
		acf_add_options_page( apply_filters( 'core_settings_acf_parent_page', [
			'page_title' => $this->get_title(),
			'menu_title' => $this->get_title(),
			'menu_slug'  => self::PAGE_SLUG,
			'capability' => $this->get_capability(),
			'redirect'   => true,
			'icon_url'   => $this->get_menu_icon(),
			'position'   => 58,
		] ) );
	}

	protected function set_slug(): void {
		$this->slug = self::PAGE_SLUG;
	}

	private function get_menu_icon(): string {
		$svg = '<svg width="44" height="33" viewBox="0 0 44 33" fill="white" xmlns="http://www.w3.org/2000/svg"><path d="M8.3378 0L0.383431 31.4317L0 33H44L35.6021 0.0100784L21.9822 12.135L8.3378 0ZM9.76739 4.6473L20.1178 13.8526L3.77059 28.3429L9.76739 4.6473ZM23.8822 13.8212L34.1782 4.65514L40.1918 28.2788L23.8822 13.8212ZM22.0175 15.5396L38.8734 30.4804H5.16173L22.0175 15.5396Z" fill="black"></path></svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

}
