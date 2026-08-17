<?php declare(strict_types=1);

use function Tribe\Alert\format_content;

/**
 * @var \League\Plates\Template\Template        $this
 * @var \Tribe\Alert\Components\Alert\Alert_Dto $dto
 * @var string                                  $link_attributes
 * @var string                                  $classes
 */
?>

<div
	<?php echo $classes; ?>
	id="tribe-alerts"
	data-alert-id="<?php echo $this->e( $dto->id ); ?>"
>
	<div class="tribe-alerts__container">

		<button
			class="tribe-alerts__close"
			data-alert-btn="close"
		>
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512" width="20px" height="20px">
				<path d="M207.6 256l107.72-107.72c6.23-6.23 6.23-16.34 0-22.58l-25.03-25.03c-6.23-6.23-16.34-6.23-22.58 0L160 208.4 52.28 100.68c-6.23-6.23-16.34-6.23-22.58 0L4.68 125.7c-6.23 6.23-6.23 16.34 0 22.58L112.4 256 4.68 363.72c-6.23 6.23-6.23 16.34 0 22.58l25.03 25.03c6.23 6.23 16.34 6.23 22.58 0L160 303.6l107.72 107.72c6.23 6.23 16.34 6.23 22.58 0l25.03-25.03c6.23-6.23 6.23-16.34 0-22.58L207.6 256z"/>
			</svg>
			<span class="u-visually-hidden"><?php esc_html_e( 'Close alert', 'tribe-alerts' ); ?></span>
		</button>

		<?php if ( $dto->title ) : ?>
			<h2 class="tribe-alerts__title"><?php echo esc_html( $dto->title ) ?></h2>
		<?php endif; ?>

		<?php if ( $dto->content ) : ?>
			<div class="tribe-alerts__content"><?php echo esc_html( format_content( $dto->content ) ) ?></div>
		<?php endif; ?>

		<?php if ( $dto->cta->url ) : ?>
			<a class="a-link tribe-alerts__link"
				<?php echo $link_attributes; ?>
			   href="<?php echo esc_url( $dto->cta->url ) ?>">
				<?php echo esc_html( format_content( $dto->cta->title ) ) ?>
			</a>
		<?php endif; ?>
	</div>

</div>
