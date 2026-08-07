<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Filter_Bar_Controller;

/**
 * @var array     $attributes
 * @var \WP_Block $block
 */

$c = Filter_Bar_Controller::factory( [
	'attributes'    => $attributes,
	'context'       => $block->context,
	'block_classes' => 'b-filter-bar',
] );

$is_sidebar    = $c->get_filter_bar_position() === 'sidebar';
$wrapper_attrs = [
	'class' => esc_attr( $c->get_block_classes() ),
	'style' => $c->get_block_styles(),
];

$template_args = [
	'controller' => $c,
];

if ( $is_sidebar ) {
	$wrapper_attrs['data-filter-bar-position'] = 'sidebar';

	$template_args['flyout_id']       = 'filter-flyout-' . wp_unique_id();
	$template_args['flyout_title_id'] = 'filter-flyout-title-' . wp_unique_id();
}
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_attrs ); ?>>
	<?php
	get_template_part( 'components/filter-bar/' . ( $is_sidebar ? 'sidebar' : 'top' ), null, $template_args );
	?>
</div>
