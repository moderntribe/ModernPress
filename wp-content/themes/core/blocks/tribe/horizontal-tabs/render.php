<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Horizontal_Tabs_Block_Controller;

/**
 * All of the parameters passed to the function where this file is being required are accessible in this scope:
 *
 * @var array     $attributes Block attributes (currentActiveTabInstanceId, etc.).
 * @var string    $content    Rendered inner blocks (tab panels).
 * @var \WP_Block $block      The instance of the WP_Block class that represents the block being rendered.
 */

$c = Horizontal_Tabs_Block_Controller::factory( [
	'attributes'    => $attributes,
	'block'         => $block,
	'block_classes' => 'wp-block-tribe-horizontal-tabs',
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
	<div class="wp-block-tribe-horizontal-tabs__tab-nav">
		<div class="wp-block-tribe-horizontal-tabs__tab-list" role="tablist">
			<?php foreach ( $c->get_tabs() as $index => $tab ) : ?>
				<?php $is_selected = $c->is_tab_selected( $index ); ?>
				<button
					type="button"
					id="<?php echo esc_attr( $tab['buttonId'] ); ?>"
					class="wp-block-tribe-horizontal-tabs__tab-item-button"
					aria-controls="<?php echo esc_attr( $tab['id'] ); ?>"
					role="tab"
					aria-selected="<?php echo $is_selected ? 'true' : 'false'; ?>"
					tabindex="<?php echo $is_selected ? '0' : '-1'; ?>"
				>
					<?php echo esc_html( $tab['label'] ); ?>
				</button>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="tab-content">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
