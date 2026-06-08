<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Comparison_Row_Block_Controller;
use Tribe\Plugin\Components\Blocks\Comparison_Table_Block_Controller;

/**
 * @var array     $attributes Block attributes.
 * @var string    $content    Rendered inner blocks (table rows).
 * @var \WP_Block $block      The block instance.
 */

$c = Comparison_Table_Block_Controller::factory( [
	'attributes'    => $attributes,
	'block'         => $block,
	'block_classes' => 'b-comparison-table',
] );

$wrapper_attrs   = get_block_wrapper_attributes(
	[
		'class' => $c->get_block_classes(),
		'style' => $c->get_block_styles(),
	]
);
$row_controllers = $c->has_columns() ? $c->get_row_controllers() : [];
?>
<figure <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="b-comparison-table__desktop">
		<div
			class="b-comparison-table__scroll"
			role="region"
			aria-label="<?php echo esc_attr__( 'Comparison table', 'tribe' ); ?>"
			tabindex="0"
		>
			<table class="b-comparison-table__table">
				<?php if ( $c->has_columns() ) : ?>
					<thead class="b-comparison-table__head">
						<tr class="b-comparison-table__row b-comparison-table__row--header">
							<th scope="col" class="b-comparison-table__feature-header"></th>
							<?php foreach ( $c->get_columns() as $index => $column ) : ?>
								<th
									scope="col"
									class="<?php echo esc_attr( $c->get_column_header_class( $index ) ); ?>"
								>
									<?php if ( '' !== $c->get_column_badge( $index ) ) : ?>
										<span class="b-comparison-table__badge t-body-small">
											<?php echo esc_html( $c->get_column_badge( $index ) ); ?>
										</span>
									<?php endif; ?>
									<span class="b-comparison-table__column-label t-display-x-small">
										<?php echo esc_html( $c->get_column_label( $index ) ); ?>
									</span>
									<?php if ( '' !== $c->get_column_subtitle( $index ) ) : ?>
										<span class="b-comparison-table__column-subtitle t-body-small">
											<?php echo esc_html( $c->get_column_subtitle( $index ) ); ?>
										</span>
									<?php endif; ?>
								</th>
							<?php endforeach; ?>
						</tr>
					</thead>
				<?php endif; ?>

				<tbody class="b-comparison-table__body">
					<?php
					if ( '' !== trim( $content ) ) {
						echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						foreach ( $row_controllers as $row_controller ) {
							echo $row_controller->render_row(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
					}
					?>
				</tbody>

				<?php if ( $c->show_footer_ctas() && $c->has_columns() ) : ?>
					<tfoot class="b-comparison-table__foot">
						<tr class="b-comparison-table__row b-comparison-table__row--footer">
							<td class="b-comparison-table__footer-spacer"></td>
							<?php foreach ( $c->get_columns() as $index => $column ) : ?>
								<td class="b-comparison-table__footer-cell<?php echo ! empty( $column['isHighlighted'] ) ? ' b-comparison-table__col--highlighted' : ''; ?>">
									<?php if ( $c->has_column_cta( $index ) ) : ?>
										<a
											href="<?php echo esc_url( $c->get_column_cta_url( $index ) ); ?>"
											class="<?php echo esc_attr( 'a-btn-' . $c->get_column_cta_style( $index ) ); ?>"
											<?php echo $c->get_column_cta_opens_in_new_tab( $index ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
										>
											<?php echo esc_html( $c->get_column_cta_label( $index ) ); ?>
										</a>
									<?php endif; ?>
								</td>
							<?php endforeach; ?>
						</tr>
					</tfoot>
				<?php endif; ?>
			</table>
		</div>
	</div>

	<?php if ( $c->has_columns() ) : ?>
		<div class="b-comparison-table__mobile">
			<div class="b-comparison-table__cards">
				<?php foreach ( $c->get_columns() as $column_index => $column ) : ?>
					<article class="b-comparison-table__card<?php echo ! empty( $column['isHighlighted'] ) ? ' b-comparison-table__card--highlighted' : ''; ?>">
						<header class="b-comparison-table__card-header">
							<?php if ( '' !== $c->get_column_badge( $column_index ) ) : ?>
								<span class="b-comparison-table__badge t-body-small">
									<?php echo esc_html( $c->get_column_badge( $column_index ) ); ?>
								</span>
							<?php endif; ?>
							<h3 class="b-comparison-table__card-title t-display-x-small">
								<?php echo esc_html( $c->get_column_label( $column_index ) ); ?>
							</h3>
							<?php if ( '' !== $c->get_column_subtitle( $column_index ) ) : ?>
								<p class="b-comparison-table__card-subtitle t-body-small">
									<?php echo esc_html( $c->get_column_subtitle( $column_index ) ); ?>
								</p>
							<?php endif; ?>
						</header>

						<div class="b-comparison-table__card-features">
							<?php foreach ( $row_controllers as $row_controller ) : ?>
								<?php if ( $row_controller->is_category_row() ) : ?>
									<div class="b-comparison-table__card-category t-body-small">
										<?php echo esc_html( $row_controller->get_label() ); ?>
									</div>
									<?php continue; ?>
								<?php endif; ?>

								<?php
								$cells      = $row_controller->get_cells();
								$cell       = $cells[ $column_index ] ?? [ 'type' => 'dash' ];
								$cell_label = Comparison_Row_Block_Controller::get_cell_accessible_label( $cell );
								?>
								<div class="b-comparison-table__card-feature">
									<span class="b-comparison-table__card-feature-label t-body-small">
										<?php echo esc_html( $row_controller->get_label() ); ?>
									</span>
									<span class="b-comparison-table__card-feature-value t-body-small">
										<?php if ( 'check' === ( $cell['type'] ?? 'dash' ) ) : ?>
											<?php echo Comparison_Row_Block_Controller::get_check_icon_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<?php else : ?>
											<?php echo esc_html( $cell_label ); ?>
										<?php endif; ?>
									</span>
								</div>
							<?php endforeach; ?>
						</div>

						<?php if ( $c->show_footer_ctas() && $c->has_column_cta( $column_index ) ) : ?>
							<footer class="b-comparison-table__card-footer">
								<a
									href="<?php echo esc_url( $c->get_column_cta_url( $column_index ) ); ?>"
									class="<?php echo esc_attr( 'a-btn-' . $c->get_column_cta_style( $column_index ) ); ?>"
									<?php echo $c->get_column_cta_opens_in_new_tab( $column_index ) ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>
								>
									<?php echo esc_html( $c->get_column_cta_label( $column_index ) ); ?>
								</a>
							</footer>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</div>
	<?php endif; ?>
</figure>
