<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

class Facet_Types {

	public const string CHECKBOXES = 'checkboxes';
	public const string DROPDOWN   = 'dropdown';
	public const string SEARCH     = 'search';
	public const string RESET      = 'reset';

	/**
	 * Taxonomy facet UI types (admin + renderer).
	 *
	 * @return array<string, string>
	 */
	public static function choices(): array {
		return [
			self::CHECKBOXES => esc_html__( 'Checkboxes', 'tribe' ),
			self::DROPDOWN   => esc_html__( 'Dropdown', 'tribe' ),
		];
	}

	/**
	 * @return list<string>
	 */
	public static function taxonomy_types(): array {
		return [
			self::CHECKBOXES,
			self::DROPDOWN,
		];
	}

	public static function is_taxonomy_type( string $type ): bool {
		return in_array( $type, self::taxonomy_types(), true );
	}

	/**
	 * Types that use a sidebar/mobile accordion (and thus the "starts expanded" setting).
	 */
	public static function is_accordion_type( string $type ): bool {
		return self::CHECKBOXES === $type;
	}

	/**
	 * Validate the three responsive facet types.
	 *
	 * An empty sidebar/mobile value supports facets saved before those fields
	 * existed: sidebar inherits top, and mobile inherits sidebar.
	 *
	 * @return array{top_type: string, sidebar_type: string, mobile_type: string}
	 */
	public static function normalize_layout_types( string $top, string $sidebar = '', string $mobile = '' ): array {
		$top = self::is_taxonomy_type( $top ) ? $top : self::CHECKBOXES;

		if ( ! self::is_taxonomy_type( $sidebar ) ) {
			$sidebar = $top;
		}

		if ( ! self::is_taxonomy_type( $mobile ) ) {
			$mobile = $sidebar;
		}

		return [
			'top_type'     => $top,
			'sidebar_type' => $sidebar,
			'mobile_type'  => $mobile,
		];
	}

}
