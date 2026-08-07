<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;
use Tribe\Plugin\Facets\Facet_Registry;
use Tribe\Plugin\Facets\Facet_Renderer;
use Tribe\Plugin\Facets\Facet_Types;

class Filter_Bar_Controller extends Abstract_Block_Controller {

	private const ACCORDION_EXCLUDED_TYPES = [ Facet_Types::SEARCH, Facet_Types::RESET ];

	private const NO_LABEL_TYPES = [ Facet_Types::RESET ];

	private const string FILTER_BAR_POSITION_CONTEXT = 'tribe/faceted-directory/filterBarPosition';
	private const string POST_TYPES_CONTEXT          = 'tribe/faceted-directory/postTypes';

	/**
	 * @var list<array<string, mixed>>
	 */
	protected array $facets;
	protected string $filter_bar_position;

	/**
	 * @var list<string>
	 */
	protected array $post_types;

	/**
	 * @var array<string, mixed>
	 */
	protected array $request;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->filter_bar_position = $this->context[ self::FILTER_BAR_POSITION_CONTEXT ] ?? 'top';
		$post_types                = $this->context[ self::POST_TYPES_CONTEXT ] ?? [ 'post' ];
		$this->post_types          = is_array( $post_types )
			? array_values( array_filter( array_map( 'sanitize_key', $post_types ) ) )
			: [ 'post' ];
		$this->request             = $args['request'] ?? $this->get_request_params();
		$this->facets              = $this->resolve_facets( $this->attributes['facets'] ?? [] );
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	public function get_facets(): array {
		return $this->facets;
	}

	public function get_filter_bar_position(): string {
		return $this->filter_bar_position;
	}

	/**
	 * @param array<string, mixed> $facet
	 */
	public function should_hide_facet_label( array $facet, string $layout = 'top' ): bool {
		$type = $this->get_facet_type( $facet, $layout );

		return in_array( $type, self::NO_LABEL_TYPES, true );
	}

	/**
	 * @param array<string, mixed> $facet
	 */
	public function should_wrap_facet_in_accordion( array $facet, string $layout = 'top' ): bool {
		if ( ! in_array( $layout, [ 'sidebar', 'mobile' ], true ) ) {
			return false;
		}

		$type = $this->get_facet_type( $facet, $layout );

		return ! in_array( $type, self::ACCORDION_EXCLUDED_TYPES, true );
	}

	/**
	 * @param array<string, mixed> $current_facet
	 */
	public function get_grid_slot( array $current_facet ): ?string {
		$current_facet_type = $this->get_facet_type( $current_facet, 'top' );

		if ( Facet_Types::SEARCH === $current_facet_type ) {
			return 'search';
		}

		if ( Facet_Types::RESET === $current_facet_type ) {
			return 'reset';
		}

		$content_index = 0;
		foreach ( $this->get_facets() as $facet ) {
			$facet_type = $this->get_facet_type( $facet, 'top' );

			if ( in_array( $facet_type, self::ACCORDION_EXCLUDED_TYPES, true ) ) {
				continue;
			}

			if ( ( $current_facet['slug'] ?? '' ) === ( $facet['slug'] ?? '' ) ) {
				if ( $content_index >= 3 ) {
					return null;
				}

				return 'facet-' . ( $content_index + 1 );
			}
			$content_index++;
		}

		return null;
	}

	/**
	 * @param array<string, mixed> $facet
	 */
	public function get_facet_wrapper_attributes( array $facet, string $layout = 'top' ): string {
		$classes = [ 'b-filter-bar__facet' ];

		$is_accordion = $this->should_wrap_facet_in_accordion( $facet, $layout );

		if ( $is_accordion ) {
			$classes[] = 'b-filter-bar__facet--accordion';
		}

		$attrs = [ 'class' => implode( ' ', $classes ) ];

		if ( $is_accordion && ! empty( $facet[ $layout . '_open' ] ) ) {
			$attrs['open'] = 'open';
		}

		$grid_slot = 'top' === $layout ? $this->get_grid_slot( $facet ) : null;

		if ( null !== $grid_slot ) {
			$attrs['data-grid-slot'] = $grid_slot;
		}

		return implode( ' ', array_map(
			static fn ( string $key, string $value ): string => $key . '="' . esc_attr( $value ) . '"',
			array_keys( $attrs ),
			$attrs
		) );
	}

	/**
	 * @param array<string, mixed> $facet
	 */
	public function render_facet( array $facet, string $layout = 'top' ): string {
		return tribe_project()->container()->get( Facet_Renderer::class )->render( $facet, $this->request, $layout );
	}

	/**
	 * ID of the primary control rendered for labels.
	 *
	 * @param array<string, mixed> $facet
	 */
	public function get_control_id( array $facet, string $layout = 'top' ): string {
		return 'facet-' . sanitize_html_class( (string) $facet['slug'] ) . '-' . sanitize_html_class( $layout );
	}

	/**
	 * @param array<string, mixed> $facet
	 */
	private function get_facet_type( array $facet, string $layout ): string {
		return strtolower( (string) ( $facet[ $layout . '_type' ] ?? $facet['type'] ?? '' ) );
	}

	/**
	 * @param list<mixed> $selected
	 *
	 * @return list<array<string, mixed>>
	 */
	private function resolve_facets( array $selected ): array {
		$registry = tribe_project()->container()->get( Facet_Registry::class );
		$allowed  = $registry->get_for_post_types( $this->post_types );
		$by_slug  = [];

		foreach ( $allowed as $facet ) {
			$by_slug[ $facet['slug'] ] = $facet;
		}

		$resolved = [];

		foreach ( $selected as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$slug = sanitize_title( (string) ( $item['slug'] ?? '' ) );

			if ( '' === $slug || ! isset( $by_slug[ $slug ] ) ) {
				continue;
			}

			$facet                  = $by_slug[ $slug ];
			$facet['displayLabel']  = isset( $item['displayLabel'] ) ? (string) $item['displayLabel'] : null;
			$facet['display_label'] = ( $facet['displayLabel'] ?? '' ) !== ''
				? (string) $facet['displayLabel']
				: (string) $facet['label'];

			$resolved[] = $facet;
		}

		return $resolved;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_request_params(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return wp_unslash( $_GET );
	}

}
