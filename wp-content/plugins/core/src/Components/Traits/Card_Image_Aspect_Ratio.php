<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Traits;

trait Card_Image_Aspect_Ratio {

	public function get_image_aspect_ratio(): string {
		$slug = $this->attributes['imageAspectRatio'] ?? '';

		if ( ! is_string( $slug ) || ! preg_match( '/\A[1-9][0-9]*-[1-9][0-9]*\z/', $slug ) ) {
			return '';
		}

		$presets = wp_get_global_settings( [ 'dimensions', 'aspectRatios', 'theme' ] ) ?? [];

		if ( ! in_array( $slug, array_column( $presets, 'slug' ), true ) ) {
			return '';
		}

		$token = '1-1' === $slug ? '1' : $slug;

		return "var(--aspect-ratio-{$token}, var(--wp--preset--aspect-ratio--{$slug}))";
	}

}
