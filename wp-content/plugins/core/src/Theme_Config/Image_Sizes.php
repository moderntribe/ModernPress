<?php declare(strict_types=1);

namespace Tribe\Plugin\Theme_Config;

class Image_Sizes {

	public const SQUARE_XSMALL      = 'square-xsmall';
	public const SQUARE_MEDIUM      = 'square-medium';
	public const SQUARE_LARGE       = 'square-large';
	public const SQUARE_XLARGE      = 'square-xlarge';
	public const SIXTEEN_NINE       = 'sixteen-nine';
	public const SIXTEEN_NINE_SMALL = 'sixteen-nine-small';
	public const SIXTEEN_NINE_LARGE = 'sixteen-nine-large';
	public const NEWS_FEATURED      = 'news-featured';

	/**
	 * @var array<string, array<string, int|bool>>
	 */
	protected array $sizes = [
		self::SQUARE_XSMALL      => [
			'width'  => 150,
			'height' => 150,
			'crop'   => true,
		],
		self::SQUARE_MEDIUM      => [
			'width'  => 376,
			'height' => 376,
			'crop'   => true,
		],
		self::SQUARE_LARGE       => [
			'width'  => 650,
			'height' => 650,
			'crop'   => true,
		],
		self::SQUARE_XLARGE      => [
			'width'  => 1000,
			'height' => 1000,
			'crop'   => true,
		],
		self::SIXTEEN_NINE_SMALL => [
			'width'  => 680,
			'height' => 383,
			'crop'   => true,
		],
		self::SIXTEEN_NINE       => [
			'width'  => 1280,
			'height' => 720,
			'crop'   => true,
		],
		self::SIXTEEN_NINE_LARGE => [
			'width'  => 1920,
			'height' => 1080,
			'crop'   => true,
		],
		self::NEWS_FEATURED      => [
			'width'  => 1619,
			'height' => 1080,
			'crop'   => true,
		],
	];

	/**
	 * @action after_setup_theme
	 */
	public function register_sizes(): void {
		foreach ( $this->sizes as $key => $attributes ) {
			add_image_size( $key, $attributes['width'], $attributes['height'], $attributes['crop'] );
		}
	}

	/**
	 * @filter image_size_names_choose
	 *
	 * @param array<string, string> $sizes
	 *
	 * @return array<string, string>
	 */
	public function add_size_names( array $sizes ): array {
		foreach ( $this->get_labels() as $key => $label ) {
			$sizes[ $key ] = sprintf(
				'%1$s (%2$d × %3$d)',
				$label,
				$this->sizes[ $key ]['width'],
				$this->sizes[ $key ]['height']
			);
		}

		var_dump($sizes);

		return $sizes;
	}

	/**
	 * @return array<string, string>
	 */
	protected function get_labels(): array {
		return [
			self::SQUARE_XSMALL      => __( 'Square, extra small', 'tribe' ),
			self::SQUARE_MEDIUM      => __( 'Square, medium', 'tribe' ),
			self::SQUARE_LARGE       => __( 'Square, large', 'tribe' ),
			self::SQUARE_XLARGE      => __( 'Square, extra large', 'tribe' ),
			self::SIXTEEN_NINE_SMALL => __( 'Wide 16:9, small', 'tribe' ),
			self::SIXTEEN_NINE       => __( 'Wide 16:9', 'tribe' ),
			self::SIXTEEN_NINE_LARGE => __( 'Wide 16:9, large', 'tribe' ),
			self::NEWS_FEATURED      => __( 'News featured', 'tribe' ),
		];
	}

}
