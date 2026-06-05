<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;

class Comparison_Table_Block_Controller extends Abstract_Block_Controller {

	/**
	 * @var array<int, array<string, mixed>>
	 */
	protected array $columns;

	protected bool $show_footer_ctas;
	protected \WP_Block $block;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	protected array $rows = [];

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->block            = $args['block'] ?? new \WP_Block( [ 'blockName' => 'tribe/comparison-table' ] );
		$this->columns          = $this->attributes['columns'] ?? [];
		$this->show_footer_ctas = ! empty( $this->attributes['showFooterCtas'] );
		$this->rows             = $this->build_rows_from_inner_blocks();

		$this->block_classes .= ' b-comparison-table';
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

	public function get_column_header_class( int $index ): string {
		$classes = 'b-comparison-table__column-header';

		if ( ! empty( $this->columns[ $index ]['isHighlighted'] ) ) {
			$classes .= ' b-comparison-table__col--highlighted';
		}

		return $classes;
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
		$columns = $this->columns;

		return array_map(
			static fn( array $row ): Comparison_Row_Block_Controller => Comparison_Row_Block_Controller::factory( [
				'attributes'    => $row,
				'columns'       => $columns,
				'block_classes' => 'wp-block-tribe-comparison-row',
			] ),
			$this->rows
		);
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
