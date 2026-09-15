<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Filter_Bar_Controller;

/**
 * @var array $args
 */

$c = $args['controller'] ?? null;

if ( ! $c instanceof Filter_Bar_Controller ) {
	return;
}

$layout = in_array( $args['layout'] ?? '', [ 'top', 'sidebar', 'mobile' ], true )
	? $args['layout']
	: 'top';
?>
<?php foreach ( $c->get_facets() as $facet ) : ?>
	<?php if ( $c->should_wrap_facet_in_accordion( $facet, $layout ) ) : ?>
		<details <?php echo $c->get_facet_wrapper_attributes( $facet, $layout ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in controller. ?>>
			<summary class="b-filter-bar__facet-summary"><?php echo esc_html( $facet['display_label'] ); ?></summary>
			<div class="b-filter-bar__facet-content">
				<?php echo $c->render_facet( $facet, $layout ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes. ?>
			</div>
		</details>
	<?php else : ?>
		<div <?php echo $c->get_facet_wrapper_attributes( $facet, $layout ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in controller. ?>>
			<?php if ( ! $c->should_hide_facet_label( $facet, $layout ) ) : ?>
				<label for="<?php echo esc_attr( $c->get_control_id( $facet, $layout ) ); ?>" class="b-filter-bar__facet-label"><?php echo esc_html( $facet['display_label'] ); ?></label>
			<?php endif; ?>
			<?php echo $c->render_facet( $facet, $layout ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- renderer escapes. ?>
		</div>
	<?php endif; ?>
<?php endforeach; ?>
