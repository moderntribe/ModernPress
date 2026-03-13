<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\FacetWP_Filter_Bar_Controller;

/**
 * @var array $attributes
 * @var \WP_Block $block
 */

$c = FacetWP_Filter_Bar_Controller::factory( [
	'attributes'    => $attributes,
	'context'       => $block->context,
	'block_classes' => 'b-facetwp-filter-bar',
] );

$is_sidebar    = $c->get_filter_bar_position() === 'sidebar';
$wrapper_attrs = [
	'class'                    => esc_attr( $c->get_block_classes() ),
	'style'                    => $c->get_block_styles(),
];

if ( $is_sidebar ) {
	$flyout_id       = 'facetwp-filter-flyout-' . wp_unique_id();
	$flyout_title_id = 'facetwp-filter-flyout-title-' . wp_unique_id();

	$wrapper_attrs['data-filter-bar-position'] = 'sidebar';
}
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_attrs ); ?>>
	<?php if ( $is_sidebar ) : ?>
		<div class="b-facetwp-filter-bar__mobile-trigger" data-js="facetwp-filter-trigger">
			<button
				type="button"
				class="b-facetwp-filter-bar__trigger-btn"
				data-js="facetwp-filter-open"
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( $flyout_id ); ?>"
				aria-haspopup="dialog"
			>
				<span class="b-facetwp-filter-bar__trigger-icon" aria-hidden="true"></span>
				<span class="b-facetwp-filter-bar__trigger-text"><?php esc_html_e( 'Search & Refine', 'tribe' ); ?></span>
			</button>
			<span class="b-facetwp-filter-bar__clear-wrap" data-js="facetwp-filter-clear-wrap" hidden>
				<button type="button" class="a-btn-link" data-js="facetwp-filter-clear-all"><?php esc_html_e( 'Clear all', 'tribe' ); ?></button>
			</span>
		</div>
		<div
			id="<?php echo esc_attr( $flyout_id ); ?>"
			class="b-facetwp-filter-bar__flyout"
			role="dialog"
			aria-modal="true"
			aria-labelledby="<?php echo esc_attr( $flyout_title_id ); ?>"
			aria-hidden="true"
			data-js="facetwp-filter-flyout"
		>
			<div class="b-facetwp-filter-bar__flyout-inner">
				<header class="b-facetwp-filter-bar__flyout-header">
					<h2 id="<?php echo esc_attr( $flyout_title_id ); ?>" class="b-facetwp-filter-bar__flyout-title t-display-x-small"><?php esc_html_e( 'Search & Refine', 'tribe' ); ?></h2>
					<button
						type="button"
						class="b-facetwp-filter-bar__flyout-close"
						data-js="facetwp-filter-close"
						aria-label="<?php esc_attr_e( 'Close', 'tribe' ); ?>"
					>
						<span class="b-facetwp-filter-bar__flyout-close-icon" aria-hidden="true"></span>
						<span class="b-facetwp-filter-bar__flyout-close-text"><?php esc_html_e( 'Close', 'tribe' ); ?></span>
					</button>
				</header>
				<div class="b-facetwp-filter-bar__flyout-body">
					<div class="b-facetwp-filter-bar__grid">
						<?php foreach ( $c->get_facets() as $facet ) : ?>
							<?php if ( $c->should_wrap_facet_in_accordion( $facet ) ) : ?>
								<details <?php echo $c->get_facet_wrapper_attributes( $facet ); ?>>
									<summary class="b-facetwp-filter-bar__facet-summary"><?php echo esc_html( $facet['display_label'] ); ?></summary>
									<div class="b-facetwp-filter-bar__facet-content">
										<?php echo facetwp_display( 'facet', $facet['slug'] ); ?>
									</div>
								</details>
							<?php else : ?>
								<div <?php echo $c->get_facet_wrapper_attributes( $facet ); ?>>
									<?php if ( ! $c->should_hide_facet_label( $facet ) ) : ?>
										<label for="facet-<?php echo esc_attr( $facet['slug'] ); ?>" class="b-facetwp-filter-bar__facet-label"><?php echo esc_html( $facet['display_label'] ); ?></label>
									<?php endif; ?>
									<?php echo facetwp_display( 'facet', $facet['slug'] ); ?>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
				<footer class="b-facetwp-filter-bar__flyout-footer">
					<button type="button" class="a-btn" data-js="facetwp-filter-show-results"><?php esc_html_e( 'Show results', 'tribe' ); ?></button>
				</footer>
			</div>
		</div>
	<?php else : ?>
		<div class="b-facetwp-filter-bar__grid">
			<?php foreach ( $c->get_facets() as $facet ) : ?>
				<?php if ( $c->should_wrap_facet_in_accordion( $facet ) ) : ?>
					<details <?php echo $c->get_facet_wrapper_attributes( $facet ); ?>>
						<summary class="b-facetwp-filter-bar__facet-summary"><?php echo esc_html( $facet['display_label'] ); ?></summary>
						<div class="b-facetwp-filter-bar__facet-content">
							<?php echo facetwp_display( 'facet', $facet['slug'] ); ?>
						</div>
					</details>
				<?php else : ?>
					<div <?php echo $c->get_facet_wrapper_attributes( $facet ); ?>>
						<?php if ( ! $c->should_hide_facet_label( $facet ) ) : ?>
							<label for="facet-<?php echo esc_attr( $facet['slug'] ); ?>" class="b-facetwp-filter-bar__facet-label"><?php echo esc_html( $facet['display_label'] ); ?></label>
						<?php endif; ?>
						<?php echo facetwp_display( 'facet', $facet['slug'] ); ?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>
