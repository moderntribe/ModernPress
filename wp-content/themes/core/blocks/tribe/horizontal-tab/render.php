<?php declare(strict_types=1);

/**
 * @var array     $attributes
 * @var string    $content
 * @var \WP_Block $block
 */

$block_id = $attributes['blockId'] ?? '';

$wrapper_attributes = get_block_wrapper_attributes(
	[
		'id'              => esc_attr( $block_id ),
		'role'            => 'tabpanel',
		'tabindex'        => '0',
		'aria-labelledby' => esc_attr( 'button-' . $block_id ),
	]
);
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> hidden>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
