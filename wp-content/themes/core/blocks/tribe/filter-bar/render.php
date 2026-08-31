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

$filter_bar_position = $c->get_filter_bar_position();
$wrapper_attrs = [
	'class'                    => esc_attr( $c->get_block_classes() ),
	'style'                    => $c->get_block_styles(),
	'data-filter-bar-position' => esc_attr( $filter_bar_position ),
];

$template_args = [
	'controller'       => $c,
	'desktop_layout'   => $filter_bar_position,
	'flyout_id'        => 'filter-flyout-' . wp_unique_id(),
	'flyout_title_id'  => 'filter-flyout-title-' . wp_unique_id(),
];
?>
<div <?php echo get_block_wrapper_attributes( $wrapper_attrs ); ?>>
	<?php
	get_template_part( 'components/filter-bar/sidebar', null, $template_args );
	?>
</div>
