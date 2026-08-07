<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

class Directory_Query {

	public function __construct(
		private Facet_Registry $registry,
		private Facet_Index $index,
	) {
	}

	/**
	 * Build WP_Query args for a directory grid.
	 *
	 * @param list<string>         $post_types
	 * @param array<string, mixed> $request    Typically $_GET (already unslashed).
	 *
	 * @return array<string, mixed>
	 */
	public function build_args( array $post_types, int $posts_per_page, array $request = [], int $paged = 1 ): array {
		$post_types = array_values( array_filter( array_map( 'sanitize_key', $post_types ) ) );

		if ( [] === $post_types ) {
			$post_types = [ 'post' ];
		}

		$args = [
			'post_type'      => count( $post_types ) === 1 ? $post_types[0] : $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, $posts_per_page ),
			'paged'          => max( 1, $paged ),
		];

		$search = isset( $request[ Facet_Registry::SEARCH_PARAM ] )
			? sanitize_text_field( (string) $request[ Facet_Registry::SEARCH_PARAM ] )
			: '';

		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		$selections = $this->get_selections( $request );

		$indexed_ids = $this->index->is_built()
			? $this->index->get_post_ids( $selections, $post_types )
			: null;

		if ( null !== $indexed_ids ) {
			// Empty match set must not fall through to an unfiltered query.
			$args['post__in'] = [] === $indexed_ids ? [ 0 ] : $indexed_ids;

			return $args;
		}

		$tax_query = self::build_tax_clauses( $this->registry->get_all(), $selections );

		if ( [] !== $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		return $args;
	}

	/**
	 * Selected term slugs for every configured facet.
	 *
	 * @param array<string, mixed> $request
	 *
	 * @return array<string, list<string>>
	 */
	public function get_selections( array $request = [] ): array {
		$selections = [];

		foreach ( $this->registry->get_all() as $facet ) {
			$selections[ $facet['slug'] ] = $this->get_selected_terms( $facet['slug'], $request );
		}

		return $selections;
	}

	/**
	 * Active facet params, for pagination links and history URLs.
	 *
	 * @param array<string, mixed> $request
	 *
	 * @return array<string, mixed>
	 */
	public function get_active_query_args( array $request = [] ): array {
		$args = [];

		foreach ( $this->get_selections( $request ) as $slug => $terms ) {
			if ( [] === $terms ) {
				continue;
			}

			$args[ $this->registry->get_query_param( $slug ) ] = $terms;
		}

		$search = $this->get_search_query( $request );

		if ( '' !== $search ) {
			$args[ Facet_Registry::SEARCH_PARAM ] = $search;
		}

		return $args;
	}

	/**
	 * Selected term slugs for a facet from the request.
	 *
	 * @param array<string, mixed> $request
	 *
	 * @return list<string>
	 */
	public function get_selected_terms( string $facet_slug, array $request = [] ): array {
		$param = $this->registry->get_query_param( $facet_slug );
		$raw   = $request[ $param ] ?? null;

		if ( null === $raw || '' === $raw || [] === $raw ) {
			return [];
		}

		if ( is_array( $raw ) ) {
			$values = $raw;
		} else {
			$values = explode( ',', (string) $raw );
		}

		return array_values( array_filter( array_map( static function ( mixed $value ): string {
			return sanitize_title( (string) $value );
		}, $values ) ) );
	}

	/**
	 * Get the search query from the request.
	 *
	 * @param array<string, mixed> $request
	 */
	public function get_search_query( array $request = [] ): string {
		return isset( $request[ Facet_Registry::SEARCH_PARAM ] )
			? sanitize_text_field( (string) $request[ Facet_Registry::SEARCH_PARAM ] )
			: '';
	}

	/**
	 * Build tax_query clauses from facet definitions + selected term slugs.
	 * Pure helper so indexing can swap in later without changing callers.
	 *
	 * @param list<array{slug: string, taxonomy: string}> $facets
	 * @param array<string, list<string>>                 $selected_by_slug
	 *
	 * @return array<string, mixed>
	 */
	public static function build_tax_clauses( array $facets, array $selected_by_slug ): array {
		$clauses = [];

		foreach ( $facets as $facet ) {
			$slug  = (string) ( $facet['slug'] ?? '' );
			$terms = $selected_by_slug[ $slug ] ?? [];

			if ( '' === $slug || [] === $terms ) {
				continue;
			}

			$clauses[] = [
				'taxonomy' => (string) $facet['taxonomy'],
				'field'    => 'slug',
				'terms'    => array_values( $terms ),
				'operator' => 'IN',
			];
		}

		if ( count( $clauses ) > 1 ) {
			$clauses['relation'] = 'AND';
		}

		return $clauses;
	}

}
