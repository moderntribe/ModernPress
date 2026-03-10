<?php declare(strict_types=1);

namespace Tribe\Plugin\Integrations\ACF_Fields\Color_Picker;

class Color_Picker_Render {

	public function render_color_picker( array $field ): void {
		$value  = esc_attr( $field['value'] ?? '' );
		$name   = esc_attr( $field['name'] );
		$colors = (array) ( $field['colors'] ?? [] );

		$hex = '';

		foreach ( $colors as $color ) {
			if ( $color['slug'] !== $value ) {
				continue;
			}
			$hex = $color['color'];
		}

		$props = [
			'value'                 => $hex ?: $value,
			'colorsToUse'           => $colors,
			'colorAttribute'        => $name,
			'showTransparentOption' => 'false',
		];

		$props_json = wp_json_encode( $props );
		$wrapper_id = 'acf-color-picker-' . uniqid();

		echo '<div id="' . esc_attr( $wrapper_id ) . '"
               class="acf-color-picker-wrapper"
               data-props=\'' . esc_attr( $props_json ) . '\'></div>';

		// Hidden input ensures ACF sees the value
		echo '<input type="hidden" name="' . $name . '" value="' . $value . '" />';

		// Inline mount script (minimal)
		echo '<script>
      window.MTColorPickerBridge = window.MTColorPickerBridge || [];
      window.MTColorPickerBridge.push({
        el: document.getElementById("' . esc_js( $wrapper_id ) . '")
      });
    </script>';
	}

}
