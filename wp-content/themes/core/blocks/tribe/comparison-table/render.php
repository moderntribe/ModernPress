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
								<?php echo $c->render_table_column_header( $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
									<?php echo $c->render_column_cta_link( $index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
						<?php echo $c->render_card_header( $column_index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

						<div class="b-comparison-table__card-features">
							<?php echo $c->render_mobile_card_features( $column_index, $row_controllers ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</div>

						<?php if ( $c->show_footer_ctas() && $c->has_column_cta( $column_index ) ) : ?>
							<footer class="b-comparison-table__card-footer">
								<?php echo $c->render_column_cta_link( $column_index ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
