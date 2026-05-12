<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Icon_Card_Controller;

/**
 * @var array $attributes
 */

$c = Icon_Card_Controller::factory( [
	'attributes'    => $attributes,
	'block_classes' => 'b-icon-card',
] );
?>
<article <?php echo get_block_wrapper_attributes( [ 'class' => esc_attr( $c->get_block_classes() ), 'style' => $c->get_block_styles() ] ); ?>>
	<div class="b-icon-card__inner">
		<div class="b-icon-card__top">
			<?php if ( $c->has_icon() ) : ?>
				<div class="b-icon-card__media">
					<div class="b-icon-card__icon-wrapper" style="<?php echo esc_attr( $c->get_icon_wrapper_styles() ); ?>">
						<?php echo $c->get_icon_svg(); ?>
					</div>
				</div>
			<?php endif; ?>
			<div class="b-icon-card__content">
				<div class="b-icon-card__title-wrap">
					<h3 class="b-icon-card__title t-display-x-small t-animated-underline"><?php echo esc_html( $c->get_title() ); ?></h3>
				</div>
				<?php if ( $c->has_description() ) : ?>
					<div class="b-icon-card__description t-body-small">
						<?php echo wp_kses_post( nl2br( $c->get_description() ) ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php if ( $c->has_link_url() ) : ?>
			<div class="b-icon-card__buttons l-flex" aria-hidden="true">
				<div class="b-icon-card__button">
					<span class="a-btn-ghost"><?php echo esc_html( $c->get_link_text() ); ?></span>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( $c->has_link_url() ) : ?>
		<a href="<?php echo esc_url( $c->get_link_url() ); ?>"<?php echo $c->does_link_open_in_new_tab() ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> class="b-icon-card__link-overlay" aria-label="<?php echo esc_attr( $c->get_link_a11y_label() ); ?>"></a>
	<?php endif; ?>
</article>
