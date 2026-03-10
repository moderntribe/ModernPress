<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Traits;

use WP_Term;

trait Primary_Term {

	public function get_primary_term( int $post_id, string $taxonomy ): ?WP_Term {
		$terms = get_the_terms( get_post( $post_id ), $taxonomy );

		// No terms set
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return null;
		}

		// Yoast Enabled
		if ( $this->has_yoast() ) {
			$primary_term_id = yoast_get_primary_term_id( $taxonomy, $post_id );
		}

		// RankMath SEO Enabled
		if ( $this->has_rank_math() ) {
			$primary_term_id = get_post_meta( $post_id, "rank_math_primary_{$taxonomy}", true );
		}

		// No Yoast fallback
		if ( empty( $primary_term_id ) ) {
			$primary_term_id = reset( $terms )->term_id;
		}

		/* If we're viewing a category archive, try to use different terms than the current archive. */
		if ( is_category() ) {
			$current_cat_id = get_queried_object_id();
			$count          = count( $terms );

			if ( $count > 1 && $current_cat_id === $primary_term_id ) {
				foreach ( $terms as $term ) {
					if ( $term->term_id !== $current_cat_id ) {
						$primary_term_id = $term->term_id;
						break;
					}
				}
			}
		}

		return get_term( $primary_term_id );
	}

	protected function has_yoast(): bool {
		return function_exists( 'yoast_get_primary_term_id' );
	}

	protected function has_rank_math(): bool {
		return class_exists( 'RankMath\Common' );
	}

}
