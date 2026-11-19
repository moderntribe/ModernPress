<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;
use Tribe\Plugin\Components\Traits\Card_Image_Aspect_Ratio;
use Tribe\Plugin\Components\Traits\Post_Data;

class Search_Card_Controller extends Abstract_Block_Controller {

	use Card_Image_Aspect_Ratio;
	use Post_Data;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );
		$this->set_post( $args['post_id'] ?? 0 );
	}

}
