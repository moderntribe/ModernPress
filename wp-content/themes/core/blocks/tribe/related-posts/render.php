<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Related_Posts_Controller;

/**
 * @var array $attributes
 */

$c = Related_Posts_Controller::factory( [
	'attributes'    => $attributes,
	'block_classes' => 'b-related-posts',
] );

if ( $c->should_bail_early() || ! $c->get_query()->have_posts() ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( [ 'class' => esc_attr( $c->get_block_classes() ), 'style' => $c->get_block_styles() ] ); ?>>
	<?php while ( $c->get_query()->have_posts() ) : ?>
		<?php $c->get_query()->the_post(); ?>
		<?php get_template_part( 'components/cards/post', null, [
			'post_id' => get_the_ID(),
		] ); ?>
	<?php endwhile; ?>
</div>
<?php wp_reset_postdata(); ?>
