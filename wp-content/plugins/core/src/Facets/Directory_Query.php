<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

class Directory_Query {

	/**
	 * Max selected terms accepted per facet from the request.
	 *
	 * Untrusted lists are sanitized then truncated so prepared IN (...) clauses
	 * and tax_query arrays cannot be inflated without bound.
	 */
	private const int MAX_TERMS_PER_FACET = 50;

	public function __construct(
		private Facet_Registry $registry,
		private Facet_Index $index,
	) {
	}

	/**
	 * Build WP_Query args for a directory grid.
	 *
	 * When the index answers the query it has already paginated, so `args`
	 * describes a single page and `total` carries the real match count that
	 * WP_Query cannot work out for itself. `total` is null on the tax_query
	 * path, where WP_Query counts as usual.
	 *
	 * @param list<string>         $post_types
	 * @param array<string, mixed> $request    Typically $_GET (already unslashed).
	 *
	 * @return array{args: array<string, mixed>, total: int|null}
	 */
	public function build( array $post_types, int $posts_per_page, array $request = [], int $paged = 1 ): array {
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

		$selections = $this->expand_hierarchy( $this->get_selections( $request ) );

		// The index paginates before WP_Query runs, so `s` would only search
		// that page and totals would ignore search. Use tax_query instead.
		$page = ( '' === $search && $this->index->is_built() )
			? $this->index->get_page( $selections, $post_types, $args['posts_per_page'], $args['paged'] )
			: null;

		if ( null !== $page ) {
			// The index already applied LIMIT/OFFSET, so WP_Query fetches this
			// page and nothing else. Empty must not fall through to unfiltered.
			$args['post__in'] = [] === $page['ids'] ? [ 0 ] : $page['ids'];
			$args['orderby']  = 'post__in';
			$args['paged']    = 1;

			// Counting is the index's job now; skip SQL_CALC_FOUND_ROWS.
			$args['no_found_rows'] = true;

			return [
				'args'  => $args,
				'total' => $page['total'],
			];
		}

		$tax_query = self::build_tax_clauses( $this->registry->get_all(), $selections );

		if ( [] !== $tax_query ) {
			$args['tax_query'] = $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		return [
			'args'  => $args,
			'total' => null,
		];
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

		// Drop nested arrays first: `?facet_topic[a][b]=c` would otherwise reach
		// the string cast and emit an array-to-string warning on a public URL.
		$values = array_filter( $values, 'is_scalar' );

		return array_values( array_slice(
			array_filter( array_map( static function ( mixed $value ): string {
				return sanitize_title( (string) $value );
			}, $values ) ),
			0,
			self::MAX_TERMS_PER_FACET
		) );
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
	 * @return array<int|string, mixed>
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
				'taxonomy'         => (string) $facet['taxonomy'],
				'field'            => 'slug',
				'terms'            => array_values( $terms ),
				'operator'         => 'IN',
				// Descendants are already expanded into $terms when a facet opts
				// into hierarchy. Off by default keeps this path matching the
				// index path, which only ever matches the exact rows it stores.
				'include_children' => false,
			];
		}

		if ( count( $clauses ) > 1 ) {
			$clauses['relation'] = 'AND';
		}

		return $clauses;
	}

	/**
	 * Expand hierarchical facet selections to include descendant term slugs.
	 *
	 * Query-side only: the checked state in the UI and the URL both stay
	 * limited to the terms the visitor actually chose.
	 *
	 * Deliberately uncapped, unlike the request-side MAX_TERMS_PER_FACET —
	 * descendants come from the taxonomy, not from user input, so there is
	 * nothing here a request can inflate.
	 *
	 * @param array<string, list<string>> $selections
	 *
	 * @return array<string, list<string>>
	 */
	private function expand_hierarchy( array $selections ): array {
		foreach ( $this->registry->get_all() as $facet ) {
			$slug     = $facet['slug'];
			$taxonomy = $facet['taxonomy'];
			$terms    = $selections[ $slug ] ?? [];

			if (
				[] === $terms
				|| empty( $facet['hierarchical'] )
				|| ! is_taxonomy_hierarchical( $taxonomy )
			) {
				continue;
			}

			$expanded = $terms;

			foreach ( $terms as $term_slug ) {
				$term = get_term_by( 'slug', $term_slug, $taxonomy );

				if ( ! $term instanceof \WP_Term ) {
					continue;
				}

				// child_of is recursive, and hide_empty must stay off so a
				// container term between a selection and its posts is included.
				$descendants = get_terms( [
					'taxonomy'   => $taxonomy,
					'child_of'   => $term->term_id,
					'fields'     => 'slugs',
					'hide_empty' => false,
				] );

				if ( ! is_array( $descendants ) ) {
					continue;
				}

				$expanded = array_merge( $expanded, array_map( 'strval', $descendants ) );
			}

			$selections[ $slug ] = array_values( array_unique( $expanded ) );
		}

		return $selections;
	}

}
