<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;

class Faceted_Directory_Controller extends Abstract_Block_Controller {

	protected string $filter_bar_position;

	/**
	 * @var list<string>
	 */
	protected array $post_types;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->filter_bar_position = $this->attributes['filterBarPosition'] ?? 'top';
		$post_types                = $this->attributes['postTypes'] ?? [ 'post' ];
		$this->post_types          = is_array( $post_types )
			? array_values( array_filter( array_map( 'sanitize_key', $post_types ) ) )
			: [ 'post' ];

		if ( [] === $this->post_types ) {
			$this->post_types = [ 'post' ];
		}

		$this->block_classes .= " b-faceted-directory--filter-bar-{$this->filter_bar_position}";
	}

	/**
	 * @return list<string>
	 */
	public function get_post_types(): array {
		return $this->post_types;
	}

}
