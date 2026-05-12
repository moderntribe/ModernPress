<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Search_Card_Controller;

/**
 * @var object $args
 */

$post_id = $args['post_id'];

// return if no post id
if ( ! $post_id ) {
	return;
}

// get template part args
$attributes = $args['attributes'] ?? [];

$c = Search_Card_Controller::factory( [
	'post_id'       => $post_id,
	'attributes'    => $attributes,
	'block_classes' => 'c-search-card',
] );
?>
<article class="<?php echo esc_attr( $c->get_block_classes() ); ?>"<?php echo ( '' !== $c->get_block_styles() ) ? sprintf( 'style="%s"', $c->get_block_styles() ) : ''; ?>>
	<div class="c-search-card__inner">
		<?php if ( $c->has_media() ) : ?>
			<div class="c-search-card__image aspect-ratio-cover aspect-ratio-4-3">
				<?php echo $c->get_media(); ?>
			</div>
		<?php endif; ?>
		<?php if ( $c->has_post_type() ) : ?>
			<p class="c-search-card__post-type t-category"><?php echo esc_html( $c->get_post_type_name() ); ?></p>
		<?php endif; ?>
		<div class="c-search-card__title-wrap">
			<h2 class="c-search-card__title t-display-x-small t-animated-underline"><?php echo esc_html( $c->get_post_title() ); ?></h2>
		</div>
		<?php if ( $c->has_post_author() || $c->has_post_date() ) : ?>
			<p class="c-search-card__metadata is-color-text-secondary">
				<?php if ( $c->has_post_author() ) : ?>
					<span class="c-search-card__metadata-author t-body-small"><?php esc_html_e( 'by', 'tribe' ); ?> <?php echo esc_html( $c->get_post_author() ); ?></span>
				<?php endif; ?>
				<?php if ( $c->has_post_author() && $c->has_post_date() ) : ?>
					<span class="c-search-card__metadata-separator t-body-small">•</span>
				<?php endif; ?>
				<?php if ( $c->has_post_date() ) : ?>
					<span class="c-search-card__metadata-date t-body-small"><?php echo esc_html( $c->get_post_date() ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>
		<?php if ( $c->has_post_date() ) : ?>
			<p class="c-search-card__excerpt t-body-small"><?php echo esc_html( $c->get_post_excerpt() ); ?></p>
		<?php endif; ?>
		<p class="c-search-card__visible-permalink t-body-small"><?php echo esc_html( $c->get_post_permalink() ); ?></p>
	</div>
	<a href="<?php echo esc_url( $c->get_post_permalink() ); ?>" class="c-search-card__link-overlay" aria-label="<?php echo sprintf( '%s %s', esc_html__( 'Read more about', 'tribe' ), $c->get_post_title() ); ?>"></a>
</article>
