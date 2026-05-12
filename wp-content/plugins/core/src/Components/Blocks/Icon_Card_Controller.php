<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Blocks\Helpers\Icon_Picker;
use Tribe\Plugin\Components\Abstracts\Abstract_Card_Controller;

class Icon_Card_Controller extends Abstract_Card_Controller {

	protected Icon_Picker $icon_picker;
	protected string $icon_wrapper_styles;
	protected string $icon_svg;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->icon_picker         = new Icon_Picker( $this->attributes );
		$this->icon_wrapper_styles = $this->icon_picker->get_icon_wrapper_styles();
		$this->icon_svg            = $this->icon_picker->get_svg();
	}

	public function get_icon_wrapper_styles(): string {
		return $this->icon_wrapper_styles;
	}

	public function has_icon(): bool {
		return ! empty( $this->icon_svg );
	}

	public function get_icon_svg(): string {
		return $this->icon_svg;
	}

}
