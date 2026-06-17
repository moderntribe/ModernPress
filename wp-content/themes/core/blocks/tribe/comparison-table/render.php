<?php declare(strict_types=1);

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
$row_controllers = $c->has_columns()
	? $c->prepare_row_controllers_for_render( $c->get_row_controllers() )
	: [];
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
					foreach ( $row_controllers as $row_controller ) {
						echo $row_controller->render_row(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</tbody>

				<?php if ( $c->show_footer_ctas() && $c->has_columns() ) : ?>
					<tfoot class="b-comparison-table__foot">
						<tr class="b-comparison-table__row b-comparison-table__row--footer">
							<td class="b-comparison-table__footer-spacer"></td>
							<?php foreach ( $c->get_columns() as $index => $column ) : ?>
								<td class="b-comparison-table__footer-cell">
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

	<?php if ( $c->has_columns() && $c->mobile_card_view() ) : ?>
		<div class="b-comparison-table__mobile">
			<?php
			$cards_wrapper_class = 'b-comparison-table__cards';

			if ( $c->mobile_card_carousel() ) {
				$cards_wrapper_class .= ' swiper';
			}
			?>
			<div
				class="<?php echo esc_attr( $cards_wrapper_class ); ?>"
				<?php if ( $c->mobile_card_carousel() ) : ?>
					data-swiper-settings="<?php echo esc_attr( $c->get_mobile_carousel_swiper_settings() ); ?>"
				<?php endif; ?>
			>
				<?php if ( $c->mobile_card_carousel() ) : ?>
					<div class="swiper-wrapper">
				<?php endif; ?>

				<?php foreach ( $c->get_columns() as $column_index => $column ) : ?>
					<?php
					$card_class = 'b-comparison-table__card';

					if ( $c->mobile_card_carousel() ) {
						$card_class .= ' swiper-slide';
					}
					?>
					<article class="<?php echo esc_attr( $card_class ); ?>">
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
							<?php
							$card_feature_row_index = 0;

							foreach ( $row_controllers as $row_controller ) :
								if ( $row_controller->is_category_row() ) :
									$card_feature_row_index = 0;
									?>
									<div class="b-comparison-table__card-category t-body">
										<?php echo esc_html( $row_controller->get_label() ); ?>
									</div>
									<?php
									continue;
								endif;

								$cells      = $row_controller->get_cells();
								$cell       = $cells[ $column_index ] ?? [ 'type' => 'dash' ];
								$cell_label = $row_controller->get_cell_accessible_label( $cell );
								$is_alt     = 1 === $card_feature_row_index % 2;
								$card_feature_row_index++;
								?>
								<div class="b-comparison-table__card-feature<?php echo $is_alt ? ' b-comparison-table__card-feature--alt' : ''; ?>">
									<span class="b-comparison-table__card-feature-label t-body-small">
										<?php echo esc_html( $row_controller->get_label() ); ?>
									</span>
									<span class="b-comparison-table__card-feature-value t-body-small">
										<?php if ( 'check' === ( $cell['type'] ?? 'dash' ) ) : ?>
											<?php echo $row_controller->get_check_icon_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
										<?php elseif ( 'dash' === ( $cell['type'] ?? 'dash' ) ) : ?>
											<?php echo $row_controller->get_dash_icon_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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

				<?php if ( $c->mobile_card_carousel() ) : ?>
					</div>
					<div
						class="swiper-pagination"
						data-clickable="true"
					></div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</figure>
