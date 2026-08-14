<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;
use Tribe\Plugin\Facets\Facet_Registry;

class Faceted_Directory_Controller extends Abstract_Block_Controller {

	protected string $filter_bar_position;

	/**
	 * @var list<string>
	 */
	protected array $post_types;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		// block.json declares an enum, but WordPress does not enforce it at
		// render time and this value becomes a class name.
		$this->filter_bar_position = 'sidebar' === ( $this->attributes['filterBarPosition'] ?? '' )
			? 'sidebar'
			: 'top';

		$post_types       = $this->attributes['postTypes'] ?? [ 'post' ];
		$this->post_types = Facet_Registry::filter_public_post_types(
			is_array( $post_types ) ? array_values( $post_types ) : []
		);

		$this->block_classes .= " b-faceted-directory--filter-bar-{$this->filter_bar_position}";
	}

	/**
	 * @return list<string>
	 */
	public function get_post_types(): array {
		return $this->post_types;
	}

}
