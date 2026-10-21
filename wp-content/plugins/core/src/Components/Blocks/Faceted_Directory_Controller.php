<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;

class Faceted_Directory_Controller extends Abstract_Block_Controller {

	public const string FILTER_BAR_POSITION_CONTEXT = 'tribe/faceted-directory/filterBarPosition';
	public const string POST_TYPES_CONTEXT          = 'tribe/faceted-directory/postTypes';

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		// block.json declares an enum, but WordPress does not enforce it at
		// render time and this value becomes a class name.
		$position = 'sidebar' === ( $this->attributes['filterBarPosition'] ?? '' ) ? 'sidebar' : 'top';

		$this->block_classes .= " b-faceted-directory--filter-bar-{$position}";
	}

}
