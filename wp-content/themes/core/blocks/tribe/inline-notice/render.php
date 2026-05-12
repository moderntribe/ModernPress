<?php declare(strict_types=1);

use Tribe\Plugin\Blocks\Helpers\Block_Animation_Attributes;
use Tribe\Plugin\Blocks\Helpers\Icon_Picker;

/**
 * @var array $attributes
 * @var string $content
 */

$animation_attributes    = new Block_Animation_Attributes( $attributes );
$animation_styles        = $animation_attributes->get_styles();
$animation_classes       = $animation_attributes->get_classes();
$icon_picker             = new Icon_Picker( $attributes );
$icon_wrapper_styles     = $icon_picker->get_icon_wrapper_styles();
$icon_svg                = $icon_picker->get_svg();
$classes                 = 'b-inline-notice';
$styles                  = $icon_wrapper_styles;
$heading                 = $attributes['heading']; // @phpstan-ignore-line
$header_text_color_theme = $attributes['headerTextColorTheme']; // @phpstan-ignore-line
$theme                   = $attributes['themeColor']; // @phpstan-ignore-line

// add theme class
$classes .= ' b-inline-notice--theme-' . esc_attr( $header_text_color_theme );

// add color style
$styles .= sprintf( ' --theme-color: %s;', esc_attr( $theme ) );

// add animation attribute classes
if ( $animation_classes !== '' ) {
	$classes .= ' ' . $animation_classes;
}

if ( $animation_styles !== '' ) {
	$styles .= ' ' . $animation_styles;
}
?>
<aside <?php echo get_block_wrapper_attributes( [ 'class' => esc_attr( $classes ), 'style' => esc_attr( $styles ) ] ); ?>>
	<div class="b-inline-notice__header">
		<?php if ( ! empty( $icon_svg ) ) : ?>
			<div class="b-inline-notice__icon-wrapper">
				<?php echo $icon_svg; ?>
			</div>
		<?php endif; ?>
		<h2 class="b-inline-notice__heading t-body s-remove-margin--top"><?php echo wp_kses_post( $heading ); ?></h2>
	</div>
	<div class="b-inline-notice__content">
		<?php echo wp_kses_post( $content ); ?>
	</div>
</aside>
