<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Blocks\Helpers\Icon_Picker;
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

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->row_type = $this->attributes['rowType'] ?? 'feature';
		$this->label    = $this->attributes['label'] ?? '';
		$this->cells    = $this->attributes['cells'] ?? [];
		$this->columns  = $args['columns'] ?? [];
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

	public function is_column_highlighted( int $index ): bool {
		return ! empty( $this->columns[ $index ]['isHighlighted'] );
	}

	/**
	 * @param array{type?: string, value?: string} $cell
	 */
	public static function get_cell_accessible_label( array $cell ): string {
		$type = $cell['type'] ?? 'dash';

		if ( 'check' === $type ) {
			return __( 'Included', 'tribe' );
		}

		if ( 'text' === $type ) {
			return $cell['value'] ?? '';
		}

		return __( 'Not included', 'tribe' );
	}

	public function render_cell( int $index, array $cell ): string {
		$type            = $cell['type'] ?? 'dash';
		$highlight_class = $this->is_column_highlighted( $index ) ? ' b-comparison-table__col--highlighted' : '';

		if ( 'check' === $type ) {
			$icon_picker = new Icon_Picker( [
				'selectedIcon'     => 'icon-circle-check',
				'selectedIconColor'=> 'currentColor',
				'selectedBgColor'  => 'transparent',
				'iconSize'         => 24,
				'iconPadding'      => 0,
				'iconLabel'        => __( 'Included', 'tribe' ),
			] );

			return sprintf(
				'<td class="b-comparison-table__cell b-comparison-table__cell--check%s"><span class="b-comparison-table__cell-icon" aria-label="%s">%s</span></td>',
				esc_attr( $highlight_class ),
				esc_attr__( 'Included', 'tribe' ),
				$icon_picker->get_svg()
			);
		}

		if ( 'text' === $type ) {
			$value = $cell['value'] ?? '';

			return sprintf(
				'<td class="b-comparison-table__cell b-comparison-table__cell--text%s"><span class="b-comparison-table__cell-text t-body-small">%s</span></td>',
				esc_attr( $highlight_class ),
				esc_html( $value )
			);
		}

		return sprintf(
			'<td class="b-comparison-table__cell b-comparison-table__cell--dash%s"><span class="b-comparison-table__cell-dash" aria-label="%s">&mdash;</span></td>',
			esc_attr( $highlight_class ),
			esc_attr__( 'Not included', 'tribe' )
		);
	}

	public function render_row(): string {
		$row_classes = trim( 'b-comparison-table__row wp-block-tribe-comparison-row ' . $this->get_block_classes() );

		if ( $this->is_category_row() ) {
			return sprintf(
				'<tr class="%1$s"><th scope="colgroup" colspan="%2$s" class="b-comparison-table__category t-body-small">%3$s</th></tr>',
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

}
