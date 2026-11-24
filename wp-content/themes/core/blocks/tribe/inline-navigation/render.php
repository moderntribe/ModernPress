<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Inline_Navigation_Controller;

/**
 * @var array $attributes
 * @var \WP_Block $block
 */

$c = Inline_Navigation_Controller::factory( [
	'attributes'    => $attributes,
	'context'       => $block->context,
	'block_classes' => 'b-inline-navigation',
] );

$pages           = 'manual' === $c->get_mode() ? $c->render_manual_pages() : $c->render_pages();
$share_services  = $c->render_share_services();
$cta             = $c->render_cta();
$flyout_id       = wp_unique_id( 'inline-navigation-flyout-' );
$flyout_title_id = wp_unique_id( 'inline-navigation-flyout-title-' );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => esc_attr( $c->get_block_classes() ), 'style' => $c->get_block_styles() ] ); ?>>
	<div class="b-inline-navigation__inner">
		<button type="button" class="b-inline-navigation__menu-toggle" data-js="inline-navigation-toggle" aria-expanded="false" aria-haspopup="dialog" aria-controls="<?php echo esc_attr( $flyout_id ); ?>">
			<span class="b-inline-navigation__menu-toggle-icon" aria-hidden="true"></span>
			<span class="b-inline-navigation__menu-toggle-label"><?php echo esc_html( $c->get_mobile_menu_toggle_label() ); ?></span>
		</button>
		<div class="b-inline-navigation__desktop">
			<div class="b-inline-navigation__flyout-body">
				<?php if ( $pages ) : ?>
					<nav class="b-inline-navigation__nav" aria-label="<?php esc_attr_e( 'Page navigation', 'tribe' ); ?>">
						<ul class="b-inline-navigation__list"><?php echo $pages; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
					</nav>
				<?php endif; ?>
				<div class="b-inline-navigation__right">
					<?php echo $share_services; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $cta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</div>
		<dialog id="<?php echo esc_attr( $flyout_id ); ?>" class="b-inline-navigation__flyout" data-js="inline-navigation-flyout" aria-labelledby="<?php echo esc_attr( $flyout_title_id ); ?>">
			<header class="b-inline-navigation__flyout-header">
				<h2 id="<?php echo esc_attr( $flyout_title_id ); ?>" class="b-inline-navigation__flyout-title t-display-x-small"><?php esc_html_e( 'Menu', 'tribe' ); ?></h2>
				<button type="button" class="b-inline-navigation__flyout-close" data-js="inline-navigation-close" autofocus>
					<span class="b-inline-navigation__flyout-close-icon" aria-hidden="true"></span>
					<span class="b-inline-navigation__flyout-close-label"><?php esc_html_e( 'Close', 'tribe' ); ?></span>
				</button>
			</header>
			<div class="b-inline-navigation__flyout-body">
				<?php if ( $pages ) : ?>
					<nav class="b-inline-navigation__nav" aria-label="<?php esc_attr_e( 'Page navigation', 'tribe' ); ?>">
						<ul class="b-inline-navigation__list"><?php echo $pages; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></ul>
					</nav>
				<?php endif; ?>
				<div class="b-inline-navigation__right">
					<?php echo $share_services; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $cta; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
		</dialog>
	</div>
</section>
