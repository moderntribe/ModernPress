<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;

class FacetWP_Archive_Controller extends Abstract_Block_Controller {

	protected string $filter_bar_position;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->filter_bar_position = $this->attributes['filterBarPosition'] ?? 'top';

		$this->block_classes .= " b-facetwp-archive--filter-bar-{$this->filter_bar_position}";
	}
}
