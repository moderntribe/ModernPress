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
		<?php
		// Result count is formatted client-side, so both forms ship as templates.
		// ponytail: two forms only. Locales with more plural categories would
		// need the count sent to the server, or wp.i18n in the view script.
		?>
		<p
			class="screen-reader-only"
			role="status"
			aria-live="polite"
			data-js="directory-status"
			data-results-singular="<?php echo esc_attr__( '%s result found', 'tribe' ); ?>"
			data-results-plural="<?php echo esc_attr__( '%s results found', 'tribe' ); ?>"
		></p>
	</form>
</section>
