<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Comparison_Row_Block_Controller;

/**
 * @var array     $attributes Block attributes.
 * @var \WP_Block $block      The block instance.
 */

$columns = $block->context['tribe/comparison-table/columns'] ?? [];

$c = Comparison_Row_Block_Controller::factory( [
	'attributes'    => $attributes,
	'columns'       => $columns,
	'block_classes' => 'wp-block-tribe-comparison-row',
] );

$row_attrs = get_block_wrapper_attributes(
	[
		'class' => trim( 'b-comparison-table__row ' . $c->get_row_type_classes() . ' ' . $c->get_block_classes() ),
	]
);

if ( $c->is_category_row() ) :
	?>
	<tr <?php echo $row_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<th
			scope="colgroup"
			colspan="<?php echo esc_attr( (string) $c->get_colspan() ); ?>"
			class="b-comparison-table__category t-body"
		>
			<?php echo esc_html( $c->get_label() ); ?>
		</th>
	</tr>
	<?php
else :
	?>
	<tr <?php echo $row_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<th scope="row" class="b-comparison-table__feature-label t-body-small">
			<?php echo esc_html( $c->get_label() ); ?>
		</th>
		<?php foreach ( $c->get_cells() as $index => $cell ) : ?>
			<?php echo $c->render_cell( $index, $cell ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<?php endforeach; ?>
	</tr>
	<?php
endif;
