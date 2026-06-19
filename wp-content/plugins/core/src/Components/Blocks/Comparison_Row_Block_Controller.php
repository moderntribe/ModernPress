<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;

class Comparison_Row_Block_Controller extends Abstract_Block_Controller {

	protected string $row_type;
	protected string $label;

	/**
	 * @var array<int, array{type: string, value?: string}>
	 */
	protected array $cells;

	/**
	 * @var array<int, array<string, mixed>>
	 */
	protected array $columns;

	protected ?int $feature_row_index = null;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->row_type = $this->attributes['rowType'] ?? 'feature';
		$this->label    = $this->attributes['label'] ?? '';
		$this->cells    = $this->attributes['cells'] ?? [];
		$this->columns  = $args['columns'] ?? [];

		if ( ! isset( $args['feature_row_index'] ) ) {
			return;
		}

		$this->feature_row_index = (int) $args['feature_row_index'];
	}

	public function set_feature_row_index( int $index ): void {
		$this->feature_row_index = $index;
	}

	public function get_row_type_classes(): string {
		if ( $this->is_category_row() ) {
			return 'b-comparison-table__row--category';
		}

		$classes = 'b-comparison-table__row--feature';

		if ( null !== $this->feature_row_index && 1 === $this->feature_row_index % 2 ) {
			$classes .= ' b-comparison-table__row--feature-alt';
		}

		return $classes;
	}

	public function is_category_row(): bool {
		return 'category' === $this->row_type;
	}

	public function get_label(): string {
		return $this->label;
	}

	public function get_colspan(): int {
		return count( $this->columns ) + 1;
	}

	/**
	 * @return array<int, array{type: string, value?: string}>
	 */
	public function get_cells(): array {
		$column_count = count( $this->columns );
		$cells        = array_slice( $this->cells, 0, $column_count );

		while ( count( $cells ) < $column_count ) {
			$cells[] = [ 'type' => 'dash' ];
		}

		return $cells;
	}

	public function is_feature_row_alt(): bool {
		return null !== $this->feature_row_index && 1 === $this->feature_row_index % 2;
	}

	/**
	 * @param array{type?: string, value?: string} $cell
	 */
	public function render_cell_value_markup( array $cell ): string {
		$type = $cell['type'] ?? 'dash';

		if ( 'check' === $type ) {
			return $this->get_check_icon_markup();
		}

		if ( 'text' === $type ) {
			return sprintf(
				'<span class="b-comparison-table__cell-text t-body-small">%s</span>',
				esc_html( $cell['value'] ?? '' )
			);
		}

		return $this->get_dash_icon_markup();
	}

	public function render_cell( int $index, array $cell ): string {
		$type = $cell['type'] ?? 'dash';
		$class = match ( $type ) {
			'check' => 'b-comparison-table__cell b-comparison-table__cell--check',
			'text'  => 'b-comparison-table__cell b-comparison-table__cell--text',
			default => 'b-comparison-table__cell b-comparison-table__cell--dash',
		};

		return sprintf(
			'<td class="%s">%s</td>',
			esc_attr( $class ),
			$this->render_cell_value_markup( $cell )
		);
	}

	public function render_mobile_card_category(): string {
		return sprintf(
			'<div class="b-comparison-table__card-category t-body">%s</div>',
			esc_html( $this->get_label() )
		);
	}

	public function render_mobile_card_feature( int $column_index ): string {
		$cells = $this->get_cells();
		$cell  = $cells[ $column_index ] ?? [ 'type' => 'dash' ];
		$alt   = $this->is_feature_row_alt() ? ' b-comparison-table__card-feature--alt' : '';

		return sprintf(
			'<div class="b-comparison-table__card-feature%s"><span class="b-comparison-table__card-feature-label t-body-small">%s</span><span class="b-comparison-table__card-feature-value t-body-small">%s</span></div>',
			$alt,
			esc_html( $this->get_label() ),
			$this->render_cell_value_markup( $cell )
		);
	}

	public function render_row(): string {
		$row_classes = trim(
			'b-comparison-table__row ' . $this->get_row_type_classes() . ' wp-block-tribe-comparison-row ' . $this->get_block_classes()
		);

		if ( $this->is_category_row() ) {
			return sprintf(
				'<tr class="%1$s"><th scope="colgroup" colspan="%2$s" class="b-comparison-table__category t-body">%3$s</th></tr>',
				esc_attr( $row_classes ),
				esc_attr( (string) $this->get_colspan() ),
				esc_html( $this->get_label() )
			);
		}

		$cells_html = '';

		foreach ( $this->get_cells() as $index => $cell ) {
			$cells_html .= $this->render_cell( $index, $cell );
		}

		return sprintf(
			'<tr class="%1$s"><th scope="row" class="b-comparison-table__feature-label t-body-small">%2$s</th>%3$s</tr>',
			esc_attr( $row_classes ),
			esc_html( $this->get_label() ),
			$cells_html
		);
	}

	public function get_check_icon_markup(): string {
		return sprintf(
			'<span class="b-comparison-table__cell-icon b-comparison-table__cell-icon--check" aria-label="%s"></span>',
			esc_attr__( 'Included', 'tribe' )
		);
	}

	public function get_dash_icon_markup(): string {
		return sprintf(
			'<span class="b-comparison-table__cell-icon b-comparison-table__cell-icon--dash" aria-label="%s"></span>',
			esc_attr__( 'Not included', 'tribe' )
		);
	}

}
