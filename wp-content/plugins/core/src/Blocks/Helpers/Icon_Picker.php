<?php declare(strict_types=1);

namespace Tribe\Plugin\Blocks\Helpers;

class Icon_Picker {

	private string $icon_key;
	private string $icon_color;
	private string $bg_color;
	private int $padding;
	private int $size;
	private bool $is_rounded;
	private string $icon_label;

	public function __construct( array $block_attributes ) {
		$this->icon_key   = $block_attributes['selectedIcon'] ?? '';
		$this->icon_color = $block_attributes['selectedIconColor'] ?? 'currentcolor';
		$this->bg_color   = $block_attributes['selectedBgColor'] ?? 'transparent';
		$this->padding    = intval( $block_attributes['iconPadding'] ?? 0 );
		$this->size       = intval( $block_attributes['iconSize'] ?? 24 );
		$this->is_rounded = ! empty( $block_attributes['isRounded'] );
		$this->icon_label = $block_attributes['iconLabel'] ?? '';
	}

	public function get_icon_wrapper_styles(): string {
		return sprintf(
			'--icon-picker--background-color:%s;
			--icon-picker--icon-color:%s;
			--icon-picker--icon-size:%dpx;
			--icon-picker--icon-padding:%dpx;
			--icon-picker--border-radius:%s;',
			esc_attr( $this->bg_color ),
			esc_attr( $this->icon_color ),
			$this->size,
			$this->padding,
			$this->is_rounded ? '50%' : '0'
		);
	}

	public function get_svg(): string {
		$name = $this->normalize_icon_name( $this->icon_key );

		if ( $name === '' ) {
			return '';
		}

		$args = [
			'size' => null,
		];

		if ( $this->icon_label !== '' ) {
			$args['label'] = $this->icon_label;
		}

		return \wp_get_icon( $name, $args );
	}

	/**
	 * Map saved `icon-ai-sparkle` keys to registered `tribe/ai-sparkle` names.
	 */
	private function normalize_icon_name( string $icon_key ): string {
		if ( $icon_key === '' ) {
			return '';
		}

		if ( str_contains( $icon_key, '/' ) ) {
			return $icon_key;
		}

		$slug = str_starts_with( $icon_key, 'icon-' ) ? substr( $icon_key, strlen( 'icon-' ) ) : $icon_key;

		return 'tribe/' . $slug;
	}

}
