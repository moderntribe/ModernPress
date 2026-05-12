<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Masthead_Search_Controller;

/**
 * @var array $attributes
 */

$c = Masthead_Search_Controller::factory( [
	'attributes'    => $attributes,
	'block_classes' => 'masthead-search',
] );
?>
<div <?php echo get_block_wrapper_attributes( [ 'class' => esc_attr( $c->get_block_classes() ), 'style' => $c->get_block_styles(), 'data-js' => 'masthead-search-wrapper' ] ); ?>>
	<button type="button" class="masthead-search__icon" data-js="toggle-search-overlay" title="<?php echo esc_attr( $c->get_toggle_button_a11y_label() ); ?>">
		<?php echo $c->get_search_icon(); ?>
	</button>
	<div class="masthead-search__overlay" data-js="masthead-search-overlay" aria-hidden="true">
		<form class="masthead-search__overlay-form" action="<?php echo esc_url( $c->get_form_action() ); ?>" method="GET">
			<label for="masthead-search__input" class="screen-reader-text"><?php echo esc_attr( $c->get_label_text() ); ?></label>
			<input id="masthead-search__input" type="text" name="s" class="t-body masthead-search__overlay-form-input" placeholder="<?php echo esc_html( $c->get_input_placeholder() ); ?>">
			<button type="submit" class="masthead-search__overlay-form-submit" title="<?php echo esc_attr( $c->get_submit_button_text() ); ?>">
				<?php echo $c->get_search_icon(); ?>
			</button>
		</form>
	</div>
</div>
