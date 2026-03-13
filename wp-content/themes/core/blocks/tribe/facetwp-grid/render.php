<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\FacetWP_Grid_Controller;

/**
 * @var array $attributes
 */

$c = FacetWP_Grid_Controller::factory( [
	'attributes'    => $attributes,
	'block_classes' => 'b-facetwp-grid',
] );

$query = $c->get_query();

if ( ! $query->have_posts() ) {
	return;
}
?>
<div <?php echo get_block_wrapper_attributes( [ 'class' => esc_attr( $c->get_block_classes() ), 'style' => $c->get_block_styles() ] ); ?>>
	<div class="b-facetwp-grid__grid">
		<?php while ( $query->have_posts() ) : ?>
			<?php $query->the_post(); ?>
			<?php get_template_part( 'components/cards/post', null, [
				'post_id' => get_the_ID(),
			] ); ?>
		<?php endwhile; ?>
	</div>
	<?php if ( $c->should_show_pagination() ) : ?>
		<?php // should_show_pagination() checks if the pagination facet exists, so no need to do that here
		$pagination_facet = $c->get_pagination_facet();
		?>
		<div class="b-facetwp-grid__pagination">
			<?php echo facetwp_display( 'facet', $pagination_facet['name'] ?? '' ); ?>
		</div>
	<?php endif; ?>
</div>
<?php wp_reset_postdata(); ?>
