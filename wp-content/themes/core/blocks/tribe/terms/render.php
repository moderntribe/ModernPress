<?php declare(strict_types=1);

use Tribe\Plugin\Components\Blocks\Terms_Block_Controller;

/**
 * All of the parameters passed to the function where this file is being required are accessible in this scope:
 *
 * @var array     $attributes     The array of attributes for this block.
 * @var string    $content        Rendered block output. ie. <InnerBlocks.Content />.
 * @var \WP_Block $block          The instance of the WP_Block class that represents the block being rendered.
 */

$c                  = Terms_Block_Controller::factory( [
	'attributes' => $attributes,
] );
$terms_block_terms  = $c->get_the_terms();
$wrapper_attributes = get_block_wrapper_attributes([
	'class' => $c->get_block_classes(),
	'style' => $c->get_block_styles(),
]);

// No terms and we're in the block editor
if ( 0 === count( $terms_block_terms ) && ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
	echo '<span class="wp-block-tribe-terms__empty-msg t-category">';
	printf( esc_html__( "No %s", 'tribe' ), $c->get_taxonomy_name() );
	echo '</span>';

	return;
}

echo '<div ' . wp_kses_data( $wrapper_attributes ) . '>';
echo '<ul class="wp-block-tribe-terms__list">';

foreach ( $terms_block_terms as $term ) {
	echo '<li class="wp-block-tribe-terms__term">';

	if ( $c->display_as_links() ) {
		printf(
			'<a href="%s" class="wp-block-tribe-terms__link t-category">%s</a>',
			esc_url( get_term_link( $term ) ?? '' ),
			esc_html( $term->name )
		);
	} else {
		printf(
			'<span class="wp-block-tribe-terms__link t-category">%s</span>',
			esc_html( $term->name )
		);
	}

	echo '</li>';
}

echo '</ul>';
echo '</div>';
