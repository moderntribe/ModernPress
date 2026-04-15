<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Announcements\Announcement_Block_Controller;

/**
 * @var array $attributes The block attributes.
 */

$c = Announcement_Block_Controller::factory( [
	'attributes'    => $attributes,
	'block_classes' => 'b-announcement',
] );
?>
<aside <?php echo wp_kses_data( get_block_wrapper_attributes( [
	'class'                => $c->get_block_classes(),
	'role'                 => 'region',
	'aria-label'           => esc_attr__( 'Site announcement', 'tribe' ),
	'data-announcement-id' => esc_attr( (string) $c->get_announcement_id() ),
	'style'                => $c->get_block_styles(),
] ) ); ?>>
	<div class="b-announcement__inner">
		<?php if ( $c->has_heading() ) : ?>
			<h2 class="b-announcement__heading t-body"><?php echo esc_html( $c->get_heading() ); ?></h2>
		<?php endif; ?>

		<?php if ( $c->has_body() ) : ?>
			<p class="b-announcement__body t-body"><?php echo esc_html( $c->get_body() ); ?></p>
		<?php endif; ?>

		<?php if ( $c->has_cta() ) : ?>
			<div class="b-announcement__cta-wrapper l-flex">
				<span class="b-announcement__cta">
					<a href="<?php echo esc_url( $c->get_cta_link() ); ?>" class="<?php echo esc_attr( "a-btn-{$c->get_cta_style()}" ); ?>"><?php echo esc_html( $c->get_cta_label() ); ?></a>
				</span>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $c->is_dismissible() ) : ?>
		<div class="b-announcement__dismiss-wrapper">
			<button type="button" class="b-announcement__dismiss" aria-label="Dismiss announcement">
				<span class="b-announcement__dismiss-text"><?php echo esc_html__( 'Dismiss', 'tribe' ); ?></span>
			</button>
		</div>
	<?php endif; ?>
</aside>

