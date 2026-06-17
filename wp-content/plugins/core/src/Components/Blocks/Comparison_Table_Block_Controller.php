<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;

class Comparison_Table_Block_Controller extends Abstract_Block_Controller {

	/**
	 * @var array<int, array<string, mixed>>
	 */
	protected array $columns;

	protected bool $show_footer_ctas;
	protected bool $mobile_card_view;
	protected bool $mobile_card_carousel;
	protected \WP_Block $block;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	protected array $rows = [];

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->block            = $args['block'] ?? new \WP_Block( [ 'blockName' => 'tribe/comparison-table' ] );
		$this->columns          = $this->attributes['columns'] ?? [];
		$this->show_footer_ctas      = ! empty( $this->attributes['showFooterCtas'] );
		$this->mobile_card_view      = ! empty( $this->attributes['mobileCardView'] );
		$this->mobile_card_carousel  = ! empty( $this->attributes['mobileCardCarousel'] );
		$this->rows                  = $this->build_rows_from_inner_blocks();
	}

	public function get_block_classes(): string {
		$classes = parent::get_block_classes();

		if ( $this->mobile_card_view() ) {
			$classes .= ' b-comparison-table--mobile-cards';
		}

		if ( $this->mobile_card_carousel() ) {
			$classes .= ' b-comparison-table--mobile-carousel';
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

	public function show_footer_ctas(): bool {
		return $this->show_footer_ctas;
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

	public function get_column_header_class( int $index ): string {
		return 'b-comparison-table__column-header';
	}

	public function get_column_badge( int $index ): string {
		return $this->columns[ $index ]['badge'] ?? '';
	}

	public function get_column_label( int $index ): string {
		return $this->columns[ $index ]['label'] ?? '';
	}

	public function get_column_subtitle( int $index ): string {
		return $this->columns[ $index ]['subtitle'] ?? '';
	}

	public function get_column_cta_label( int $index ): string {
		return $this->columns[ $index ]['ctaLabel'] ?? '';
	}

	public function get_column_cta_url( int $index ): string {
		return $this->columns[ $index ]['ctaUrl'] ?? '';
	}

	public function get_column_cta_style( int $index ): string {
		return $this->columns[ $index ]['ctaStyle'] ?? 'outlined';
	}

	public function get_column_cta_opens_in_new_tab( int $index ): bool {
		return ! empty( $this->columns[ $index ]['ctaOpensInNewTab'] );
	}

	public function has_column_cta( int $index ): bool {
		return '' !== $this->get_column_cta_label( $index ) && '' !== $this->get_column_cta_url( $index );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function get_rows(): array {
		return $this->rows;
	}

	/**
	 * @return array<int, \Tribe\Plugin\Components\Blocks\Comparison_Row_Block_Controller>
	 */
	public function get_row_controllers(): array {
		$columns     = $this->columns;
		$controllers = [];

		foreach ( $this->rows as $row ) {
			$controllers[] = Comparison_Row_Block_Controller::factory( [
				'attributes'    => $row,
				'columns'       => $columns,
				'block_classes' => 'wp-block-tribe-comparison-row',
			] );
		}

		return $controllers;
	}

	/**
	 * Assigns per-category feature row indices and returns the prepared controllers.
	 *
	 * Feature rows alternate within each category section. The count resets whenever
	 * a category row is encountered.
	 *
	 * @param array<int, Comparison_Row_Block_Controller> $row_controllers
	 * @return array<int, Comparison_Row_Block_Controller>
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

	/**
	 * @return array<int, array<string, mixed>>
	 */
	protected function build_rows_from_inner_blocks(): array {
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
