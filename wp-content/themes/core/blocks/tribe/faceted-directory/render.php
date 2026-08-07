<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Faceted_Directory_Controller;
use Tribe\Plugin\Facets\Results_Endpoint;

/**
 * @var array  $attributes
 * @var string $content
 */

$c = Faceted_Directory_Controller::factory( [
	'attributes'    => $attributes,
	'block_classes' => 'b-faceted-directory',
] );
?>
<section <?php echo get_block_wrapper_attributes( [ 'class' => esc_attr( $c->get_block_classes() ), 'style' => $c->get_block_styles() ] ); ?>>
	<form
		class="b-faceted-directory__form"
		method="get"
		action=""
		data-js="faceted-directory-form"
		data-endpoint="<?php echo esc_url( rest_url( Results_Endpoint::REST_NAMESPACE . Results_Endpoint::ROUTE ) ); ?>"
	>
		<div class="b-faceted-directory__inner">
			<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inner blocks. ?>
		</div>
		<p class="screen-reader-text" role="status" aria-live="polite" data-js="directory-status"></p>
	</form>
</section>
