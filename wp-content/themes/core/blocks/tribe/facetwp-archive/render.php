<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\FacetWP_Archive_Controller;

/**
 * @var array $attributes
 * @var string $content
 */

$c = FacetWP_Archive_Controller::factory( [
	'attributes'    => $attributes,
	'block_classes' => 'b-facetwp-archive',
] );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => esc_attr( $c->get_block_classes() ), 'style' => $c->get_block_styles() ] ); ?>>
	<div class="b-facetwp-archive__inner">
		<?php echo $content; ?>
	</div>
</section>
