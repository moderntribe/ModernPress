<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

/**
 * Taxonomy terms for facets: the options a facet displays, and the descendants a
 * hierarchical selection expands to. Results are memoized for the request, since
 * a filter bar renders every facet twice (desktop and mobile).
 */
class Facet_Terms {

	/**
	 * @var array<string, list<array{term: \WP_Term, depth: int}>>
	 */
	private array $options = [];

	/**
	 * @var array<string, list<\WP_Term>>
	 */
	private array $all_terms = [];

	public function __construct(
		private Facet_Index $index,
	) {
	}

	/**
	 * Options for a facet, each tagged with its display depth.
	 *
	 * Depth is always 0 unless the facet opts into hierarchy and the taxonomy
	 * is actually hierarchical.
	 *
	 * @param array<string, mixed> $facet
	 *
	 * @return list<array{term: \WP_Term, depth: int}>
	 */
	public function get_options( array $facet ): array {
		$taxonomy   = (string) ( $facet['taxonomy'] ?? '' );
		$post_types = $this->get_directory_post_types( $facet );
		$key        = $taxonomy . '|' . ( $facet['slug'] ?? '' ) . '|' . implode( ',', $post_types );

		if ( ! isset( $this->options[ $key ] ) ) {
			$this->options[ $key ] = $this->build_options( $facet, $taxonomy, $post_types );
		}

		return $this->options[ $key ];
	}

	/**
	 * The given term slugs plus the slugs of all their descendants.
	 *
	 * Hide-empty stays off so a container term between a selection and its
	 * posts is included.
	 *
	 * @param list<string> $slugs
	 *
	 * @return list<string>
	 */
	public function expand_with_descendants( string $taxonomy, array $slugs ): array {
		$terms    = $this->get_all_terms( $taxonomy );
		$children = [];
		$by_slug  = [];

		foreach ( $terms as $term ) {
			$children[ (int) $term->parent ][] = $term;
			$by_slug[ $term->slug ]            = $term;
		}

		$expanded = $slugs;
		$pending  = array_values( array_filter( array_map(
			static fn ( string $slug ): ?\WP_Term => $by_slug[ $slug ] ?? null,
			$slugs
		) ) );

		// Each term is visited once, which also breaks any parent cycle.
		$visited = [];

		while ( [] !== $pending ) {
			$term = array_shift( $pending );

			foreach ( $children[ (int) $term->term_id ] ?? [] as $child ) {
				if ( isset( $visited[ $child->term_id ] ) ) {
					continue;
				}

				$visited[ $child->term_id ] = true;
				$expanded[]                 = $child->slug;
				$pending[]                  = $child;
			}
		}

		return array_values( array_unique( $expanded ) );
	}

	/**
	 * @param array<string, mixed> $facet
	 * @param list<string>         $post_types
	 *
	 * @return list<array{term: \WP_Term, depth: int}>
	 */
	private function build_options( array $facet, string $taxonomy, array $post_types ): array {
		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return [];
		}

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		] );

		if ( is_wp_error( $terms ) ) {
			return [];
		}

		$hierarchical = ! empty( $facet['hierarchical'] ) && is_taxonomy_hierarchical( $taxonomy );
		$terms        = $this->limit_to_available( array_values( $terms ), (string) ( $facet['slug'] ?? '' ), $post_types, $hierarchical );

		if ( $hierarchical ) {
			return $this->nest( $terms );
		}

		return array_map(
			static fn ( \WP_Term $term ): array => [
				'term'  => $term,
				'depth' => 0,
			],
			$terms
		);
	}

	/**
	 * Drop terms that have no results in this directory.
	 *
	 * hide_empty already removed terms with no posts at all, but it counts
	 * every post type sharing the taxonomy. The index knows which terms carry
	 * posts of the types this directory actually shows. Without the index
	 * built there is nothing better to go on, so every term stays.
	 *
	 * @param list<\WP_Term> $terms
	 * @param list<string>   $post_types
	 *
	 * @return list<\WP_Term>
	 */
	private function limit_to_available( array $terms, string $facet_slug, array $post_types, bool $hierarchical ): array {
		$available = $this->index->get_available_term_slugs( $facet_slug, $post_types );

		if ( null === $available ) {
			return $terms;
		}

		$keep = array_fill_keys( $available, true );

		if ( $hierarchical ) {
			$keep = $this->add_ancestors( $keep, $terms );
		}

		return array_values( array_filter(
			$terms,
			static fn ( \WP_Term $term ): bool => isset( $keep[ $term->slug ] )
		) );
	}

	/**
	 * Mark the ancestors of every kept term, so a container parent that holds
	 * no posts of its own cannot orphan the children underneath it.
	 *
	 * @param array<string, bool> $keep
	 * @param list<\WP_Term>      $terms
	 *
	 * @return array<string, bool>
	 */
	private function add_ancestors( array $keep, array $terms ): array {
		$by_id = [];

		foreach ( $terms as $term ) {
			$by_id[ (int) $term->term_id ] = $term;
		}

		foreach ( $terms as $term ) {
			if ( ! isset( $keep[ $term->slug ] ) ) {
				continue;
			}

			$parent = (int) $term->parent;

			// Stopping on an already-kept ancestor also breaks any parent cycle.
			while ( isset( $by_id[ $parent ] ) && ! isset( $keep[ $by_id[ $parent ]->slug ] ) ) {
				$keep[ $by_id[ $parent ]->slug ] = true;
				$parent                          = (int) $by_id[ $parent ]->parent;
			}
		}

		return $keep;
	}

	/**
	 * Flatten terms into parent-first order, each tagged with its depth.
	 *
	 * A term whose parent was filtered out by hide_empty is treated as a root
	 * so it can't disappear from the list.
	 *
	 * @param list<\WP_Term> $terms
	 *
	 * @return list<array{term: \WP_Term, depth: int}>
	 */
	private function nest( array $terms ): array {
		$ids      = array_map( static fn ( \WP_Term $term ): int => $term->term_id, $terms );
		$children = [];

		foreach ( $terms as $term ) {
			$parent                = in_array( (int) $term->parent, $ids, true ) ? (int) $term->parent : 0;
			$children[ $parent ][] = $term;
		}

		$flat = [];

		$walk = static function ( int $parent, int $depth ) use ( &$walk, &$flat, $children ): void {
			foreach ( $children[ $parent ] ?? [] as $term ) {
				$flat[] = [
					'term'  => $term,
					'depth' => $depth,
				];

				$walk( (int) $term->term_id, $depth + 1 );
			}
		};

		$walk( 0, 0 );

		return $flat;
	}

	/**
	 * @return list<\WP_Term>
	 */
	private function get_all_terms( string $taxonomy ): array {
		if ( ! isset( $this->all_terms[ $taxonomy ] ) ) {
			$terms = taxonomy_exists( $taxonomy )
				? get_terms( [
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
				] )
				: [];

			$this->all_terms[ $taxonomy ] = is_wp_error( $terms ) ? [] : array_values( $terms );
		}

		return $this->all_terms[ $taxonomy ];
	}

	/**
	 * @param array<string, mixed> $facet
	 *
	 * @return list<string>
	 */
	private function get_directory_post_types( array $facet ): array {
		$post_types = $facet['directory_post_types'] ?? [];

		return is_array( $post_types ) ? array_values( array_map( 'strval', $post_types ) ) : [];
	}

}
