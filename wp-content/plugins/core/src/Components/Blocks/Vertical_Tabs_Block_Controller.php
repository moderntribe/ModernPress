<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;

/**
 * Controller for the Vertical Tabs block.
 * Builds tab data from inner blocks (saved order).
 */
class Vertical_Tabs_Block_Controller extends Abstract_Block_Controller {

	/**
	 * Tab items built from inner blocks.
	 *
	 * @var array<int, array{id: string, buttonId: string, title: string, content: string}>
	 */
	protected array $tabs = [];

	protected \WP_Block $block;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->block = $args['block'] ?? new \WP_Block( [ 'blockName' => 'tribe/vertical-tabs' ] );
		$this->tabs  = $this->build_tabs_from_inner_blocks();
	}

	/**
	 * @return array<int, array{id: string, buttonId: string, title: string, content: string}>
	 */
	public function get_tabs(): array {
		return $this->tabs;
	}

	/**
	 * Whether the given tab (by index) should be selected. First tab is always active on the front-end.
	 */
	public function is_tab_selected( int $index ): bool {
		return $index === 0;
	}

	/**
	 * Build tab list from parsed inner blocks (preserves saved order).
	 *
	 * @return array<int, array{id: string, buttonId: string, title: string, content: string}>
	 */
	protected function build_tabs_from_inner_blocks(): array {
		$block        = $this->block->parsed_block;
		$inner_blocks = $block['innerBlocks'] ?? [];
		$tabs         = [];

		foreach ( $inner_blocks as $inner ) {
			if ( ( $inner['blockName'] ?? '' ) !== 'tribe/vertical-tab' ) {
				continue;
			}

			$attrs   = $inner['attrs'] ?? [];
			$id      = $attrs['blockId'] ?? '';
			$title   = $attrs['title'] ?? '';
			$content = $attrs['content'] ?? '';

			$tabs[] = [
				'id'       => $id,
				'buttonId' => 'vt-button-' . $id,
				'title'    => $title !== '' ? $title : __( 'Tab Heading', 'tribe' ),
				'content'  => $content,
			];
		}

		return $tabs;
	}

}
