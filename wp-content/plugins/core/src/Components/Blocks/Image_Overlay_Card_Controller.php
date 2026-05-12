<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Card_Controller;

class Image_Overlay_Card_Controller extends Abstract_Card_Controller {

	protected string $overlay_color;
	protected string $overlay_hover_color;
	protected bool $card_uses_dark_theme;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->overlay_color        = $this->attributes['overlayColor'] ?? '#0000001C';
		$this->overlay_hover_color  = $this->attributes['overlayHoverColor'] ?? '#00000033';
		$this->card_uses_dark_theme = $this->attributes['cardUsesDarkTheme'] ?? false;
		$this->block_styles        .= sprintf(
			'--card-image-overlay-color: %s;--card-image-overlay-hover-color: %s;',
			$this->overlay_color,
			$this->overlay_hover_color
		);

		if ( ! $this->card_uses_dark_theme ) {
			return;
		}

		$this->block_classes .= ' b-image-overlay-card--dark-theme';
	}

}
