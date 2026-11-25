<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

use Tribe\Plugin\Settings\Facets_Settings;

class Facet_Registry {

	public const string QUERY_PARAM_PREFIX = 'facet_';
	public const string SEARCH_PARAM       = 'facet_search';

	/**
	 * Pagination param. Deliberately not `paged`: WordPress canonical-redirects
	 * an unused `paged` arg on singular URLs, which would break pagination on
	 * the pages directories usually live on.
	 */
	public const string PAGE_PARAM = 'facet_page';

	/**
	 * @var list<array<string, mixed>>|null
	 */
	private ?array $facets = null;

	/**
	 * All configured taxonomy facets, normalized.
	 *
	 * @return list<array{
	 *   slug: string,
	 *   label: string,
	 *   taxonomy: string,
	 *   post_types: list<string>,
	 *   top_type: string,
	 *   sidebar_type: string,
	 *   mobile_type: string,
	 *   sidebar_open: bool,
	 *   mobile_open: bool,
	 *   hierarchical: bool,
	 *   searchable: bool,
	 *   source: string
	 * }>
	 */
	public function get_all(): array {
		if ( null !== $this->facets ) {
			return $this->facets;
		}

		$rows = get_field( Facets_Settings::FACETS, 'option' );

		if ( ! is_array( $rows ) ) {
			$this->facets = [];

			return $this->facets;
		}

		$facets = [];

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$normalized = $this->normalize_row( $row );

			if ( null === $normalized ) {
				continue;
			}

			$facets[] = $normalized;
		}

		$this->facets = $facets;

		return $this->facets;
	}

	/**
	 * Taxonomy facets that apply to any of the given post types, plus system facets.
	 *
	 * @param list<string> $post_types
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_for_post_types( array $post_types ): array {
		return array_merge( $this->get_system_facets(), $this->get_taxonomy_facets( $post_types ) );
	}

	/**
	 * Taxonomy facets that apply to any of the given post types (all when none given).
	 *
	 * @param list<string> $post_types
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_taxonomy_facets( array $post_types = [] ): array {
		if ( [] === $post_types ) {
			return $this->get_all();
		}

		return array_values( array_filter(
			$this->get_all(),
			static fn ( array $facet ): bool => [] !== array_intersect( $facet['post_types'], $post_types )
		) );
	}

	/**
	 * Post types that at least one facet applies to.
	 *
	 * @return list<string>
	 */
	public function get_facet_post_types(): array {
		$post_types = [];

		foreach ( $this->get_all() as $facet ) {
			$post_types = array_merge( $post_types, $facet['post_types'] );
		}

		return array_values( array_unique( $post_types ) );
	}

	public function uses_taxonomy( string $taxonomy ): bool {
		foreach ( $this->get_all() as $facet ) {
			if ( $facet['taxonomy'] === $taxonomy ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Editor-facing catalog (system + all taxonomy facets).
	 *
	 * @return list<array<string, mixed>>
	 */
	public function get_editor_catalog(): array {
		return array_merge( $this->get_system_facets(), $this->get_all() );
	}

	public function get_query_param( string $slug ): string {
		return self::QUERY_PARAM_PREFIX . sanitize_title( $slug );
	}

	/**
	 * Post types a directory may query. Attachments are excluded: they are
	 * public, but they are not directory content.
	 *
	 * @return array<string, \WP_Post_Type>
	 */
	public function public_post_types(): array {
		$post_types = get_post_types( [ 'public' => true ], 'objects' );

		unset( $post_types['attachment'] );

		return $post_types;
	}

	/**
	 * Narrow a requested set of post types to the ones a directory may query,
	 * falling back to posts when nothing survives.
	 *
	 * Block attributes and REST params are both untrusted, so every entry point
	 * runs through here rather than trusting what it was handed.
	 *
	 * @return list<string>
	 */
	public function filter_public_post_types( mixed $post_types ): array {
		if ( ! is_array( $post_types ) ) {
			return [ 'post' ];
		}

		$sanitized = array_values( array_unique( array_intersect(
			array_map( 'sanitize_key', array_filter( $post_types, 'is_string' ) ),
			array_keys( $this->public_post_types() )
		) ) );

		return [] === $sanitized ? [ 'post' ] : $sanitized;
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function get_system_facets(): array {
		return [
			[
				'slug'         => Facet_Types::SEARCH,
				'label'        => __( 'Search', 'tribe' ),
				'taxonomy'     => '',
				'post_types'   => [],
				'top_type'     => Facet_Types::SEARCH,
				'sidebar_type' => Facet_Types::SEARCH,
				'mobile_type'  => Facet_Types::SEARCH,
				'source'       => 'system',
			],
			[
				'slug'         => Facet_Types::RESET,
				'label'        => __( 'Clear all', 'tribe' ),
				'taxonomy'     => '',
				'post_types'   => [],
				'top_type'     => Facet_Types::RESET,
				'sidebar_type' => Facet_Types::RESET,
				'mobile_type'  => Facet_Types::RESET,
				'source'       => 'system',
			],
		];
	}

	/**
	 * @param array<string, mixed> $row
	 *
	 * @return array{
	 *   slug: string,
	 *   label: string,
	 *   taxonomy: string,
	 *   post_types: list<string>,
	 *   top_type: string,
	 *   sidebar_type: string,
	 *   mobile_type: string,
	 *   sidebar_open: bool,
	 *   mobile_open: bool,
	 *   hierarchical: bool,
	 *   searchable: bool,
	 *   source: string
	 * }|null
	 */
	private function normalize_row( array $row ): ?array {
		$label    = trim( (string) ( $row[ Facets_Settings::FACET_LABEL ] ?? '' ) );
		$slug     = sanitize_title( (string) ( $row[ Facets_Settings::FACET_SLUG ] ?? '' ) );
		$taxonomy = (string) ( $row[ Facets_Settings::FACET_TAXONOMY ] ?? '' );
		$top      = (string) ( $row[ Facets_Settings::FACET_TYPE ] ?? Facet_Types::DROPDOWN );
		$types    = $row[ Facets_Settings::FACET_POST_TYPES ] ?? [];

		// Default true so existing rows (pre-toggle) keep their per-layout types.
		$customize = (bool) ( $row[ Facets_Settings::FACET_CUSTOMIZE_LAYOUT ] ?? true );
		$sidebar   = $customize ? (string) ( $row[ Facets_Settings::FACET_SIDEBAR_TYPE ] ?? '' ) : '';
		$mobile    = $customize ? (string) ( $row[ Facets_Settings::FACET_MOBILE_TYPE ] ?? '' ) : '';

		$layout_types = Facet_Types::normalize_layout_types( $top, $sidebar, $mobile );

		if ( '' === $label || '' === $slug || '' === $taxonomy ) {
			return null;
		}

		if ( ! is_array( $types ) ) {
			$types = '' !== $types ? [ (string) $types ] : [];
		}

		$post_types = array_values( array_filter( array_map( 'strval', $types ) ) );

		if ( [] === $post_types ) {
			return null;
		}

		$sidebar_open = Facet_Types::is_accordion_type( $layout_types['sidebar_type'] )
			&& (bool) ( $row[ Facets_Settings::FACET_SIDEBAR_ACCORDION_OPEN ] ?? false );
		$mobile_open  = Facet_Types::is_accordion_type( $layout_types['mobile_type'] )
			&& (bool) ( $row[ Facets_Settings::FACET_MOBILE_ACCORDION_OPEN ] ?? false );

		return [
			'slug'         => $slug,
			'label'        => $label,
			'taxonomy'     => $taxonomy,
			'post_types'   => $post_types,
			'top_type'     => $layout_types['top_type'],
			'sidebar_type' => $layout_types['sidebar_type'],
			'mobile_type'  => $layout_types['mobile_type'],
			'sidebar_open' => $sidebar_open,
			'mobile_open'  => $mobile_open,
			// Whether the taxonomy is actually hierarchical is resolved at use
			// time — taxonomies may not be registered when facets first load.
			'hierarchical' => (bool) ( $row[ Facets_Settings::FACET_HIERARCHICAL ] ?? false ),
			'searchable'   => (bool) ( $row[ Facets_Settings::FACET_SEARCHABLE ] ?? false ),
			'source'       => 'taxonomy',
		];
	}

}
