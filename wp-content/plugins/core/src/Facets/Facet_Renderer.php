<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

class Facet_Renderer {

	public function __construct(
		private Facet_Registry $registry,
		private Directory_Query $directory_query,
		private Facet_Index $index,
	) {
	}

	/**
	 * @param array<string, mixed> $facet     Resolved facet definition (registry + display overrides).
	 * @param array<string, mixed> $request
	 * @param string               $layout    top, sidebar, or mobile.
	 */
	public function render( array $facet, array $request = [], string $layout = 'top' ): string {
		$type = (string) ( $facet[ $layout . '_type' ] ?? $facet['type'] ?? Facet_Types::CHECKBOXES );

		return match ( $type ) {
			Facet_Types::SEARCH   => $this->render_search( $facet, $request, $layout ),
			Facet_Types::RESET    => $this->render_reset( $facet ),
			Facet_Types::DROPDOWN => $this->render_dropdown( $facet, $request, $layout ),
			default               => $this->render_checkboxes( $facet, $request, $layout ),
		};
	}

	/**
	 * @param array<string, mixed> $facet
	 * @param array<string, mixed> $request
	 */
	private function render_search( array $facet, array $request, string $layout ): string {
		$slug  = (string) ( $facet['slug'] ?? Facet_Types::SEARCH );
		$id    = $this->get_control_id( $facet, $layout );
		$value = $this->directory_query->get_search_query( $request );

		ob_start();
		?>
		<div class="tribe-facet tribe-facet--search" data-facet="<?php echo esc_attr( $slug ); ?>">
			<input
				type="search"
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( Facet_Registry::SEARCH_PARAM ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo esc_attr__( 'Enter keyword', 'tribe' ); ?>"
				class="tribe-facet__search-input"
			/>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * @param array<string, mixed> $facet
	 */
	private function render_reset( array $facet ): string {
		$slug  = (string) ( $facet['slug'] ?? Facet_Types::RESET );
		$label = (string) ( $facet['display_label'] ?? $facet['label'] ?? __( 'Clear all', 'tribe' ) );

		ob_start();
		?>
		<div class="tribe-facet tribe-facet--reset" data-facet="<?php echo esc_attr( $slug ); ?>">
			<button type="reset" class="a-btn-link tribe-facet__reset-button">
				<?php echo esc_html( $label ); ?>
			</button>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * @param array<string, mixed> $facet
	 * @param array<string, mixed> $request
	 */
	private function render_checkboxes( array $facet, array $request, string $layout ): string {
		$slug     = (string) $facet['slug'];
		$param    = $this->registry->get_query_param( $slug );
		$selected = $this->directory_query->get_selected_terms( $slug, $request );
		$terms    = $this->get_terms( $facet );
		$id_base  = $this->get_control_id( $facet, $layout );

		ob_start();
		?>
		<div class="tribe-facet tribe-facet--checkboxes" data-facet="<?php echo esc_attr( $slug ); ?>">
			<ul class="tribe-facet__list" role="list">
				<?php foreach ( $terms as [ 'term' => $term, 'depth' => $depth ] ) : ?>
					<?php
					$input_id = $id_base . '-' . $term->slug;
					$checked  = in_array( $term->slug, $selected, true );
					?>
					<li class="tribe-facet__item" style="--facet-depth: <?php echo esc_attr( (string) $depth ); ?>;">
						<label class="tribe-facet__label" for="<?php echo esc_attr( $input_id ); ?>">
							<input
								type="checkbox"
								class="screen-reader-only"
								id="<?php echo esc_attr( $input_id ); ?>"
								name="<?php echo esc_attr( $param ); ?>[]"
								value="<?php echo esc_attr( $term->slug ); ?>"
								<?php checked( $checked ); ?>
							/>
							<span class="tribe-facet__label-text t-body t-transparent-underline"><?php echo esc_html( $term->name ); ?></span>
						</label>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php echo $this->render_clear_button( $facet ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in method. ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Multi-select dropdown: a custom trigger and listbox over a hidden native
	 * <select multiple> that carries the form state.
	 *
	 * @param array<string, mixed> $facet
	 * @param array<string, mixed> $request
	 */
	private function render_dropdown( array $facet, array $request, string $layout ): string {
		$slug       = (string) $facet['slug'];
		$param      = $this->registry->get_query_param( $slug );
		$selected   = $this->directory_query->get_selected_terms( $slug, $request );
		$terms      = $this->get_terms( $facet );
		$id         = $this->get_control_id( $facet, $layout );
		$label      = (string) ( $facet['display_label'] ?? $facet['label'] ?? '' );
		$searchable = ! empty( $facet['searchable'] );

		ob_start();
		?>
		<div class="tribe-facet tribe-facet--dropdown" data-facet="<?php echo esc_attr( $slug ); ?>">
			<div class="tribe-facet__dropdown">
				<?php // Owns the facet's control id so the label toggles this, not the hidden select. ?>
				<button
					type="button"
					id="<?php echo esc_attr( $id ); ?>"
					class="tribe-facet__dropdown-trigger"
					aria-haspopup="listbox"
					aria-expanded="false"
					aria-controls="<?php echo esc_attr( $id . '-list' ); ?>"
					data-placeholder="<?php echo esc_attr__( 'Any', 'tribe' ); ?>"
					data-selected-template="<?php echo esc_attr( $this->get_selected_template() ); ?>"
				>
					<span class="tribe-facet__dropdown-trigger-label">
						<?php echo esc_html( $this->get_selected_label( $terms, $selected ) ); ?>
					</span>
				</button>
				<?php // The trigger and listbox are the keyboard UI; this only carries form state. ?>
				<select
					id="<?php echo esc_attr( $id . '-input' ); ?>"
					name="<?php echo esc_attr( $param ); ?>[]"
					class="tribe-facet__select screen-reader-only"
					data-dropdown="true"
					tabindex="-1"
					multiple
				>
					<?php foreach ( $terms as [ 'term' => $term ] ) : ?>
						<option
							value="<?php echo esc_attr( $term->slug ); ?>"
							<?php selected( in_array( $term->slug, $selected, true ) ); ?>
						>
							<?php echo esc_html( $term->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
				<div class="tribe-facet__dropdown-popup" hidden>
					<?php if ( $searchable ) : ?>
						<div class="tribe-facet__dropdown-search">
							<?php // Filters the options below in place; never re-queries terms. ?>
							<input
								type="search"
								class="tribe-facet__dropdown-search-input"
								data-js="facet-option-search"
								placeholder="<?php echo esc_attr__( 'Search', 'tribe' ); ?>"
								aria-label="<?php echo esc_attr( sprintf( /* translators: %s: facet label. */ __( 'Search %s options', 'tribe' ), $label ) ); ?>"
								aria-controls="<?php echo esc_attr( $id . '-list' ); ?>"
								autocomplete="off"
							/>
						</div>
					<?php endif; ?>
					<ul
						id="<?php echo esc_attr( $id . '-list' ); ?>"
						class="tribe-facet__dropdown-list"
						role="listbox"
						aria-multiselectable="true"
					>
						<?php foreach ( $terms as [ 'term' => $term, 'depth' => $depth ] ) : ?>
							<?php $is_selected = in_array( $term->slug, $selected, true ); ?>
							<?php // role="none": a listbox may only own options, not list items. ?>
							<li class="tribe-facet__item" role="none" style="--facet-depth: <?php echo esc_attr( (string) $depth ); ?>;">
								<button
									type="button"
									class="tribe-facet__dropdown-option"
									role="option"
									aria-selected="<?php echo $is_selected ? 'true' : 'false'; ?>"
									data-value="<?php echo esc_attr( $term->slug ); ?>"
								>
									<span class="tribe-facet__label-text t-body t-transparent-underline"><?php echo esc_html( $term->name ); ?></span>
								</button>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php
					// Outside the listbox: only options may live inside it.
					// Stays in the DOM and empty rather than hidden, because a
					// live region that appears from nowhere announces unreliably.
					?>
					<p
						class="tribe-facet__dropdown-empty"
						role="status"
						data-empty-text="<?php echo esc_attr__( 'No matches', 'tribe' ); ?>"
					></p>
					<div class="tribe-facet__dropdown-footer">
						<?php echo $this->render_clear_button( $facet, [] === $selected ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in method. ?>
						<?php echo $this->render_apply_button( $facet ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in method. ?>
					</div>
				</div>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Trigger text: the term name for a single choice, a count beyond that.
	 *
	 * @param list<array{term: \WP_Term, depth: int}> $terms
	 * @param list<string>                            $selected
	 */
	private function get_selected_label( array $terms, array $selected ): string {
		$labels = array_values( array_map(
			static fn ( array $item ): string => $item['term']->name,
			array_filter(
				$terms,
				static fn ( array $item ): bool => in_array( $item['term']->slug, $selected, true )
			)
		) );

		return match ( true ) {
			[] === $labels         => __( 'Any', 'tribe' ),
			1 === count( $labels ) => $labels[0],
			default                => sprintf( $this->get_selected_template(), number_format_i18n( count( $labels ) ) ),
		};
	}

	/**
	 * Multi-selection trigger text. Shared with dropdown.js via a data
	 * attribute so both sides format the label identically.
	 *
	 * ponytail: single plural form. Only ever shown for counts of 2+, so this
	 * is correct for languages with one plural; locales with several plural
	 * forms would need _n() plus the count passed to the client.
	 */
	private function get_selected_template(): string {
		/* translators: %s: number of chosen options. */
		return __( '%s options chosen...', 'tribe' );
	}

	/**
	 * Dismiss a dropdown's popup.
	 *
	 * Selections filter on change like every other facet type, so this only
	 * closes the popup. It's inert without JS, hence type="button".
	 *
	 * @param array<string, mixed> $facet
	 */
	private function render_apply_button( array $facet ): string {
		$label = (string) ( $facet['display_label'] ?? $facet['label'] ?? '' );

		ob_start();
		?>
		<button
			type="button"
			class="a-btn tribe-facet__apply"
			data-js="facet-apply"
			aria-label="<?php echo esc_attr( sprintf( /* translators: %s: facet label. */ __( 'Filter by %s', 'tribe' ), $label ) ); ?>"
		>
			<?php echo esc_html__( 'Filter', 'tribe' ); ?>
		</button>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Clear this facet's selections.
	 *
	 * Hidden until selections exist. Without JS the form-level reset remains
	 * the working fallback.
	 *
	 * @param array<string, mixed> $facet
	 */
	private function render_clear_button( array $facet, bool $hidden = true ): string {
		$label = (string) ( $facet['display_label'] ?? $facet['label'] ?? '' );

		ob_start();
		?>
		<button
			type="button"
			class="a-btn-link tribe-facet__clear"
			data-js="facet-clear"
			aria-label="<?php echo esc_attr( sprintf( /* translators: %s: facet label. */ __( 'Clear %s selections', 'tribe' ), $label ) ); ?>"
			<?php echo $hidden ? ' hidden' : ''; ?>
		>
			<?php echo esc_html__( 'Clear', 'tribe' ); ?>
		</button>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Terms for a facet, each tagged with its display depth.
	 *
	 * Depth is always 0 unless the facet opts into hierarchy and the taxonomy
	 * is actually hierarchical.
	 *
	 * @param array<string, mixed> $facet
	 *
	 * @return list<array{term: \WP_Term, depth: int}>
	 */
	private function get_terms( array $facet ): array {
		$taxonomy = (string) ( $facet['taxonomy'] ?? '' );

		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return [];
		}

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		] );

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return [];
		}

		$hierarchical = ! empty( $facet['hierarchical'] ) && is_taxonomy_hierarchical( $taxonomy );
		$terms        = $this->limit_to_available( array_values( $terms ), $facet, $hierarchical );

		if ( ! $hierarchical ) {
			return array_map(
				static fn ( \WP_Term $term ): array => [
					'term'  => $term,
					'depth' => 0,
				],
				$terms
			);
		}

		return self::nest( $terms );
	}

	/**
	 * Drop terms that have no results in this directory.
	 *
	 * hide_empty already removed terms with no posts at all, but it counts
	 * every post type sharing the taxonomy. The index knows which terms carry
	 * posts of the types this directory actually shows. Without the index
	 * built there is nothing better to go on, so every term stays.
	 *
	 * @param list<\WP_Term>       $terms
	 * @param array<string, mixed> $facet
	 *
	 * @return list<\WP_Term>
	 */
	private function limit_to_available( array $terms, array $facet, bool $hierarchical ): array {
		$post_types = $facet['directory_post_types'] ?? [];

		if ( ! is_array( $post_types ) || [] === $post_types ) {
			return $terms;
		}

		$available = $this->index->get_available_term_slugs(
			(string) ( $facet['slug'] ?? '' ),
			array_values( array_map( 'strval', $post_types ) )
		);

		if ( null === $available ) {
			return $terms;
		}

		$keep = array_fill_keys( $available, true );

		if ( $hierarchical ) {
			$keep = self::add_ancestors( $keep, $terms );
		}

		return array_values( array_filter(
			$terms,
			static fn ( \WP_Term $term ): bool => isset( $keep[ $term->slug ] )
		) );
	}

	/**
	 * Control id for a facet in a layout.
	 *
	 * The prefix comes from the filter bar so two directories on one page do
	 * not both emit `facet-topic-top`, which would point every label at the
	 * first one. Filter_Bar_Controller::get_control_id() must stay in step:
	 * it produces the `for` half of the same pair.
	 *
	 * @param array<string, mixed> $facet
	 */
	private function get_control_id( array $facet, string $layout ): string {
		$prefix = (string) ( $facet['id_prefix'] ?? 'facet' );

		return sanitize_html_class( $prefix )
			. '-' . sanitize_html_class( (string) ( $facet['slug'] ?? '' ) )
			. '-' . sanitize_html_class( $layout );
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
	private static function add_ancestors( array $keep, array $terms ): array {
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
	private static function nest( array $terms ): array {
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

}
