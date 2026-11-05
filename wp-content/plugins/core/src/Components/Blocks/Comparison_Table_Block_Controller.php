<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;

class Comparison_Table_Block_Controller extends Abstract_Block_Controller {

	/**
	 * @var array<int, array<string, mixed>>
	 */
	protected array $columns;

	protected bool $show_footer_ctas;
	protected string $cta_placement;
	protected bool $mobile_card_view;
	protected bool $mobile_card_carousel;
	protected \WP_Block $block;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	protected array $rows = [];

	/**
	 * @var array<int, \Tribe\Plugin\Components\Blocks\Comparison_Row_Block_Controller>|null
	 */
	protected ?array $row_controllers = null;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->block                = $args['block'] ?? new \WP_Block( [ 'blockName' => 'tribe/comparison-table' ] );
		$this->columns              = $this->attributes['columns'] ?? [];
		$this->show_footer_ctas     = ! empty( $this->attributes['showFooterCtas'] );
		$placement                  = $this->attributes['ctaPlacement'] ?? 'footer';
		$this->cta_placement        = in_array( $placement, [ 'footer', 'header' ], true )
			? $placement
			: 'footer';
		$this->mobile_card_view     = ! empty( $this->attributes['mobileCardView'] );
		$this->mobile_card_carousel = ! empty( $this->attributes['mobileCardCarousel'] );
		$this->rows                 = $this->build_rows_from_inner_blocks();
	}

	public function get_block_classes(): string {
		$classes = parent::get_block_classes();

		if ( $this->mobile_card_view() ) {
			$classes .= ' b-comparison-table--mobile-cards';
		}

		if ( $this->mobile_card_carousel() ) {
			$classes .= ' b-comparison-table--mobile-carousel';
		}

		if ( $this->ctas_in_header() ) {
			$classes .= ' b-comparison-table--header-ctas';
		}

		return $classes;
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_columns(): array {
		return $this->columns;
	}

	public function has_columns(): bool {
		return ! empty( $this->columns );
	}

	public function ctas_in_header(): bool {
		return $this->show_footer_ctas && 'header' === $this->cta_placement;
	}

	public function ctas_in_footer(): bool {
		return $this->show_footer_ctas && 'footer' === $this->cta_placement;
	}

	public function mobile_card_view(): bool {
		return $this->mobile_card_view;
	}

	public function mobile_card_carousel(): bool {
		return $this->mobile_card_view() && $this->mobile_card_carousel;
	}

	/**
	 * Swiper settings for the mobile card carousel.
	 *
	 * @return string JSON-encoded Swiper configuration.
	 */
	public function get_mobile_carousel_swiper_settings(): string {
		$settings = [
			'slidesPerView' => 1,
			'spaceBetween'  => 20,
			'autoHeight'    => true,
		];

		return wp_json_encode( $settings ) ?: '{}';
	}

	public function has_column_cta( int $index ): bool {
		return '' !== ( $this->columns[ $index ]['ctaLabel'] ?? '' )
			&& '' !== ( $this->columns[ $index ]['ctaUrl'] ?? '' );
	}

	public function render_column_badge_markup( int $index ): string {
		$badge = $this->columns[ $index ]['badge'] ?? '';

		if ( '' === $badge ) {
			return '';
		}

		return sprintf(
			'<span class="b-comparison-table__badge t-tag">%s</span>',
			esc_html( $badge )
		);
	}

	public function render_table_column_header( int $index ): string {
		$subtitle_markup = $this->render_optional_column_markup(
			'span',
			'b-comparison-table__column-subtitle t-body-small',
			esc_html( $this->columns[ $index ]['subtitle'] ?? '' )
		);
		$cta_markup      = $this->render_optional_column_markup(
			'span',
			'b-comparison-table__column-cta',
			$this->ctas_in_header() ? $this->render_column_cta_link( $index ) : ''
		);
		$content         = sprintf(
			'%s<span class="b-comparison-table__column-label t-display-x-small">%s</span>%s%s',
			$this->render_column_badge_markup( $index ),
			esc_html( $this->columns[ $index ]['label'] ?? '' ),
			$subtitle_markup,
			$cta_markup
		);

		if ( $this->ctas_in_header() ) {
			$content = sprintf( '<div class="b-comparison-table__column-header-content">%s</div>', $content );
		}

		return sprintf( '<th scope="col" class="b-comparison-table__column-header">%s</th>', $content );
	}

	public function render_card_header( int $index ): string {
		$subtitle_markup = $this->render_optional_column_markup(
			'p',
			'b-comparison-table__card-subtitle t-body-small',
			esc_html( $this->columns[ $index ]['subtitle'] ?? '' )
		);
		$cta_markup      = $this->render_optional_column_markup(
			'div',
			'b-comparison-table__card-cta',
			$this->ctas_in_header() ? $this->render_column_cta_link( $index ) : ''
		);

		return sprintf(
			'<header class="b-comparison-table__card-header">%s<h3 class="b-comparison-table__card-title t-display-x-small">%s</h3>%s%s</header>',
			$this->render_column_badge_markup( $index ),
			esc_html( $this->columns[ $index ]['label'] ?? '' ),
			$subtitle_markup,
			$cta_markup
		);
	}

	public function render_column_cta_link( int $index ): string {
		if ( ! $this->has_column_cta( $index ) ) {
			return '';
		}

		$target_attrs = ! empty( $this->columns[ $index ]['ctaOpensInNewTab'] )
			? ' target="_blank" rel="noopener noreferrer"'
			: '';

		return sprintf(
			'<a href="%s" class="%s"%s>%s</a>',
			esc_url( $this->columns[ $index ]['ctaUrl'] ?? '' ),
			esc_attr( 'a-btn-' . ( $this->columns[ $index ]['ctaStyle'] ?? 'outlined' ) ),
			$target_attrs,
			esc_html( $this->columns[ $index ]['ctaLabel'] ?? '' )
		);
	}

	/**
	 * @param array<int, \Tribe\Plugin\Components\Blocks\Comparison_Row_Block_Controller> $row_controllers
	 */
	public function render_mobile_card_features( int $column_index, array $row_controllers ): string {
		$html = '';

		foreach ( $row_controllers as $row_controller ) {
			if ( $row_controller->is_category_row() ) {
				$html .= $row_controller->render_mobile_card_category();
				continue;
			}

			$html .= $row_controller->render_mobile_card_feature( $column_index );
		}

		return $html;
	}

	/**
	 * @return array<int, \Tribe\Plugin\Components\Blocks\Comparison_Row_Block_Controller>
	 */
	public function get_row_controllers(): array {
		if ( null === $this->row_controllers ) {
			$controllers = [];

			foreach ( $this->rows as $row ) {
				$controllers[] = Comparison_Row_Block_Controller::factory( [
					'attributes'    => $row,
					'columns'       => $this->columns,
					'block_classes' => 'wp-block-tribe-comparison-row',
				] );
			}

			$this->row_controllers = $controllers;
		}

		return $this->row_controllers;
	}

	/**
	 * Assigns per-category feature row indices and returns the prepared controllers.
	 *
	 * Feature rows alternate within each category section. The count resets whenever
	 * a category row is encountered.
	 *
	 * @param array<int, \Tribe\Plugin\Components\Blocks\Comparison_Row_Block_Controller> $row_controllers
	 *
	 * @return array<int, \Tribe\Plugin\Components\Blocks\Comparison_Row_Block_Controller>
	 */
	public function prepare_row_controllers_for_render( array $row_controllers ): array {
		$feature_row_index = 0;

		foreach ( $row_controllers as $row_controller ) {
			if ( $row_controller->is_category_row() ) {
				$feature_row_index = 0;
				continue;
			}

			$row_controller->set_feature_row_index( $feature_row_index );
			$feature_row_index++;
		}

		return $row_controllers;
	}

	protected function render_optional_column_markup( string $tag, string $class, string $content ): string {
		if ( '' === $content ) {
			return '';
		}

		return sprintf(
			'<%1$s class="%2$s">%3$s</%1$s>',
			esc_attr( $tag ),
			esc_attr( $class ),
			$content
		);
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	protected function build_rows_from_inner_blocks(): array {
		// Editor-only bridge for ServerSideRender; never persisted to post content.
		if ( ! empty( $this->attributes['previewRows'] ) && is_array( $this->attributes['previewRows'] ) ) {
			return $this->attributes['previewRows'];
		}

		$inner_blocks = $this->block->parsed_block['innerBlocks'] ?? [];
		$rows         = [];

		foreach ( $inner_blocks as $inner ) {
			if ( ( $inner['blockName'] ?? '' ) !== 'tribe/comparison-row' ) {
				continue;
			}

			$rows[] = $inner['attrs'] ?? [];
		}

		return $rows;
	}

}
