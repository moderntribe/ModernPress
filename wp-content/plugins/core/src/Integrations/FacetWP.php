<?php declare(strict_types=1);

namespace Tribe\Plugin\Integrations;

class FacetWP {

	public function get_facets(): array {
		if ( ! function_exists( 'FWP' ) ) {
			return [];
		}

		return FWP()->helper->get_facets();
	}

	/**
	 * Add an id attribute to the facet's first form control so label[for] works for accessibility.
	 *
	 * @param string $output Facet HTML.
	 * @param array  $params Facet params; $params['facet']['name'] is the facet slug.
	 *
	 * @return string Modified HTML.
	 */
	public function add_facet_control_id( string $output, array $params ): string {
		$name = $params['facet']['name'] ?? '';
		if ( $name === '' ) {
			return $output;
		}

		$id = 'facet-' . $name;

		// Add id to the first <select> or <input> so label[for] targets the focusable control.
		$output = preg_replace(
			'/<(select|input)(\s)/',
			'<$1 id="' . esc_attr( $id ) . '"$2',
			$output,
			1
		);

		return $output;
	}

	public function remove_facetwp_counts( string $output, array $params ): string {
		$remove_from_types = [ 'checkboxes', 'radio', 'hierarchy', 'range_list', 'time_since' ];

		if ( ! in_array( $params['facet']['type'], $remove_from_types, true ) ) {
			return $output;
		}

		return preg_replace( '/<span class="facetwp-counter">[^<]*<\/span>/', '', $output );
	}

	public function register_custom_facets( array $facets ): array {
		// Pagination facet
		$facets[] = [
			'name'                => 'pagination',
			'label'               => 'Pagination',
			'type'                => 'pager',
			'pager_type'          => 'numbers',
			'inner_size'          => '1',
			'dots_label'          => '…',
			'prev_label'          => '<span>Previous page</span>',
			'next_label'          => '<span>Next page</span>',
			'count_text_plural'   => '[lower] - [upper] of [total] results',
			'count_text_singular' => '1 result',
			'count_text_none'     => 'No results',
			'scroll_target'       => '.facetwp-template',
			'scroll_offset'       => '-150',
			'load_more_text'      => 'Load more',
			'loading_text'        => 'Loading...',
			'default_label'       => 'Per page',
			'per_page_options'    => '10, 25, 50, 100',
		];

		// Search facet
		$facets[] = [
			'enable_relevance' => 'yes',
			'name'             => 'search',
			'label'            => 'Search',
			'type'             => 'search',
			'search_engine'    => '',
			'placeholder'      => 'Enter keyword',
			'auto_refresh'     => 'yes',
		];

		// Reset facet
		$facets[] = [
			'name'         => 'reset_filters',
			'label'        => 'Reset Filters',
			'type'         => 'reset',
			'reset_facets' => [],
			'reset_ui'     => 'button',
			'reset_text'   => 'Clear all',
			'reset_mode'   => 'off',
			'auto_hide'    => 'no',
		];

		return $facets;
	}

	public function rewrite_pagination_link_tags( string $html, array $params ): string {
		if ( '' === $params['page'] ) {
			$html = str_replace( [ '<a', '/a>' ], [ '<span', '/span>' ], $html );
		}

		return str_replace( [ '<a', '/a>' ], [ '<button type="button"', '/button>' ], $html );
	}

}
