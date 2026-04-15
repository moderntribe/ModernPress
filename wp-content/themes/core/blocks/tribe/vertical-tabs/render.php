<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Vertical_Tabs_Block_Controller;

/**
 * All of the parameters passed to the function where this file is being required are accessible in this scope:
 *
 * @var array     $attributes Block attributes.
 * @var string    $content    Rendered inner blocks (tab panels).
 * @var \WP_Block $block      The instance of the WP_Block class that represents the block being rendered.
 */

$c = Vertical_Tabs_Block_Controller::factory( [
	'attributes'    => $attributes,
	'block'         => $block,
	'block_classes' => 'b-vertical-tabs',
] );

$wrapper_attrs = get_block_wrapper_attributes(
	[
		'class'   => $c->get_block_classes(),
		'style'   => $c->get_block_styles(),
		'data-js' => 'tabs-block',
	]
);
?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="b-vertical-tabs__tab-container" role="tablist">
		<?php foreach ( $c->get_tabs() as $index => $tab ) : ?>
			<?php $is_selected = $c->is_tab_selected( $index ); ?>
			<div
				id="<?php echo esc_attr( $tab['buttonId'] ); ?>"
				class="b-vertical-tabs__tab"
				aria-controls="<?php echo esc_attr( $tab['id'] ); ?>"
				role="tab"
				aria-selected="<?php echo $is_selected ? 'true' : 'false'; ?>"
				tabindex="<?php echo $is_selected ? '0' : '-1'; ?>"
			>
				<span class="b-vertical-tabs__tab-title t-display-xx-small s-remove-margin--top t-animated-underline"><?php echo esc_html( $tab['title'] ); ?></span>
				<div class="b-vertical-tabs__tab-hidden">
					<?php if ( $tab['content'] !== '' ) : ?>
						<span class="b-vertical-tabs__tab-description t-body"><?php echo wp_kses_post( $tab['content'] ); ?></span>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="b-vertical-tabs__tab-content">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
