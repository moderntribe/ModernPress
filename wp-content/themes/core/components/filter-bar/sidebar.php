<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Filter_Bar_Controller;

/**
 * @var array $args
 */

$c = $args['controller'] ?? null;

if ( ! $c instanceof Filter_Bar_Controller ) {
	return;
}

$flyout_id       = $args['flyout_id'] ?? '';
$flyout_title_id = $args['flyout_title_id'] ?? '';

if ( '' === $flyout_id || '' === $flyout_title_id ) {
	return;
}
?>
<div class="b-filter-bar__mobile-trigger" data-js="filter-trigger">
	<button
		type="button"
		class="b-filter-bar__trigger-btn"
		data-js="filter-open"
		aria-expanded="false"
		aria-controls="<?php echo esc_attr( $flyout_id ); ?>"
		aria-haspopup="dialog"
	>
		<span class="b-filter-bar__trigger-icon" aria-hidden="true"></span>
		<span class="b-filter-bar__trigger-text"><?php esc_html_e( 'Search & Refine', 'tribe' ); ?></span>
	</button>
	<span class="b-filter-bar__clear-wrap" data-js="filter-clear-wrap" hidden>
		<button type="reset" class="t-body" data-js="filter-clear-all"><?php esc_html_e( 'Clear all', 'tribe' ); ?></button>
	</span>
</div>
<?php
/*
 * ponytail: the sidebar and mobile fieldsets below each render the full facet
 * set, so a large taxonomy ships its terms twice. That is the price of the
 * "Mobile Flyout Type" setting — the two layouts can render different controls
 * for the same facet, and the swap is CSS-only so it works without JS. Folding
 * them into one node would mean dropping per-layout types or re-rendering in
 * JS. Revisit only if per-layout types are ever dropped.
 */
?>
<fieldset class="b-filter-bar__responsive-facets b-filter-bar__responsive-facets--sidebar" data-facet-layout="sidebar">
	<legend class="screen-reader-only"><?php esc_html_e( 'Filters', 'tribe' ); ?></legend>
	<div class="b-filter-bar__grid">
		<?php get_template_part( 'components/filter-bar/facets', null, [
			'controller' => $c,
			'layout'     => 'sidebar',
		] ); ?>
	</div>
</fieldset>
<div
	id="<?php echo esc_attr( $flyout_id ); ?>"
	class="b-filter-bar__flyout"
	role="dialog"
	aria-modal="true"
	aria-labelledby="<?php echo esc_attr( $flyout_title_id ); ?>"
	aria-hidden="true"
	data-js="filter-flyout"
>
	<div class="b-filter-bar__flyout-inner">
		<header class="b-filter-bar__flyout-header">
			<h2 id="<?php echo esc_attr( $flyout_title_id ); ?>" class="b-filter-bar__flyout-title t-display-x-small"><?php esc_html_e( 'Search & Refine', 'tribe' ); ?></h2>
			<button
				type="button"
				class="b-filter-bar__flyout-close"
				data-js="filter-close"
				aria-label="<?php esc_attr_e( 'Close', 'tribe' ); ?>"
			>
				<span class="b-filter-bar__flyout-close-icon" aria-hidden="true"></span>
				<span class="b-filter-bar__flyout-close-text"><?php esc_html_e( 'Close', 'tribe' ); ?></span>
			</button>
		</header>
		<div class="b-filter-bar__flyout-body">
			<fieldset class="b-filter-bar__responsive-facets b-filter-bar__responsive-facets--mobile" data-facet-layout="mobile" disabled>
				<legend class="screen-reader-only"><?php esc_html_e( 'Filters', 'tribe' ); ?></legend>
				<div class="b-filter-bar__grid">
				<?php get_template_part( 'components/filter-bar/facets', null, [
					'controller' => $c,
					'layout'     => 'mobile',
				] ); ?>
				</div>
			</fieldset>
		</div>
		<footer class="b-filter-bar__flyout-footer">
			<button type="submit" class="a-btn" data-js="filter-show-results"><?php esc_html_e( 'Show results', 'tribe' ); ?></button>
		</footer>
	</div>
</div>
