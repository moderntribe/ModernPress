<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;
use Tribe\Plugin\Components\Traits\Post_Data;

class Post_Card_Controller extends Abstract_Block_Controller {

	use Post_Data;

	protected string $layout;
	protected string $heading_level;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );
		$this->set_post( $args['post_id'] ?? 0 );

		$this->layout         = $this->attributes['layout'] ?? 'vertical';
		$this->heading_level  = $this->attributes['heading_level'] ?? 'h3';
		$this->block_classes .= " c-post-card--layout-{$this->layout}";
	}

	public function get_heading_level(): string {
		return $this->heading_level;
	}

}
