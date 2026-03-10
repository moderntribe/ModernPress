<?php declare(strict_types=1);

namespace Tribe\Plugin\Integrations\ACF_Fields\Color_Picker;

use Extended\ACF\Fields\Field;
use Extended\ACF\Fields\Settings\ConditionalLogic;
use Extended\ACF\Fields\Settings\HelperText;
use Extended\ACF\Fields\Settings\Required;
use Extended\ACF\Fields\Settings\Wrapper;

class Color_Picker extends Field {

	use ConditionalLogic;
	use HelperText;
	use Required;
	use Wrapper;

	protected ?string $type = 'color_picker_tribe';

	/**
	 * @var array{
	 *     default_value: string,
	 *     colors: array<int|string, mixed>
	 * }
	 */
	protected array $defaults = [
		'default_value' => '',
		'colors'        => [],
	];

	public function __construct( string $label, ?string $name = null ) {
		parent::__construct( $label, $name );

		$this->colors();
	}

	/**
	 * Define a fluent setter for colors.
	 */
	public function colors( array $colors = [] ): static {
		// bail of colors set via field
		if ( ! empty( $colors ) ) {
			$this->settings['colors'] = $colors;

			return $this;
		}

		$settings = wp_get_global_settings();
		if ( empty( $settings ) || empty( $settings['color'] ) ) {
			$this->settings['color'] = $colors;

			return $this;
		}

		$palette = $settings['color']['palette'] ?? [];

		if ( empty( $palette ) ) {
			$this->settings['colors'] = $colors;

			return $this;
		}

		$result = [];

		foreach ( $palette['theme'] as $key => $item ) {
			if ( $key === 'default' ) {
				continue;
			}

			$slug     = $item['slug'];
			$result[] = [
				'name'  => $item['name'],
				'slug'  => $slug,
				'color' => $item['color'],
			];
		}

		$this->settings['colors'] = $result;

		return $this;
	}

}
