<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Image_Overlay_Card_Controller;

/**
 * @var array $attributes
 */

$c = Image_Overlay_Card_Controller::factory( [
	'attributes'    => $attributes,
	'block_classes' => 'b-image-overlay-card',
] );
?>
<article <?php echo get_block_wrapper_attributes( [ 'class' => esc_attr( $c->get_block_classes() ), 'style' => esc_attr( $c->get_block_styles() ) ] ); ?>>
	<?php if ( $c->has_media() ) : ?>
		<div class="b-image-overlay-card__media">
			<?php echo wp_kses_post( $c->get_media() ); ?>
		</div>
	<?php endif; ?>
	<div class="b-image-overlay-card__content">
		<div class="b-image-overlay-card__title-wrap">
			<h3 class="b-image-overlay-card__title t-display-x-small t-animated-underline"><?php echo esc_html( $c->get_title() ); ?></h3>
		</div>
		<?php if ( $c->has_link_url() ) : ?>
			<div class="b-image-overlay-card__buttons l-flex" aria-hidden="true">
				<div class="b-image-overlay-card__button">
					<span class="a-btn-ghost"><?php echo esc_html( $c->get_link_text() ); ?></span>
				</div>
			</div>
		<?php endif; ?>
	</div>
	<?php if ( $c->has_link_url() ) : ?>
		<a href="<?php echo esc_url( $c->get_link_url() ); ?>"<?php echo $c->does_link_open_in_new_tab() ? ' target="_blank" rel="noopener noreferrer"' : ''; ?> class="b-image-overlay-card__link-overlay" aria-label="<?php echo esc_attr( $c->get_link_a11y_label() ); ?>"></a>
	<?php endif; ?>
</article>
