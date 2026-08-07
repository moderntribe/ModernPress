<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Directory_Grid_Controller;

/**
 * @var array     $attributes
 * @var \WP_Block $block
 */

$c = Directory_Grid_Controller::factory( [
	'attributes'    => $attributes,
	'context'       => $block->context ?? [],
	'block_classes' => 'b-directory-grid',
] );
?>
<div
	<?php echo get_block_wrapper_attributes( [ 'class' => esc_attr( $c->get_block_classes() ), 'style' => $c->get_block_styles() ] ); ?>
	data-js="directory-grid"
	data-post-types="<?php echo esc_attr( (string) wp_json_encode( $c->get_post_types() ) ); ?>"
	data-posts-per-page="<?php echo esc_attr( (string) $c->get_posts_per_page() ); ?>"
	data-show-pagination="<?php echo $c->shows_pagination() ? 'true' : 'false'; ?>"
	aria-busy="false"
>
	<?php get_template_part( 'components/directory-grid/grid', null, [ 'controller' => $c ] ); ?>
</div>
<?php wp_reset_postdata(); ?>
