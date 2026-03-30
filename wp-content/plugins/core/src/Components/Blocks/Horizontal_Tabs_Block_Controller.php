<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;

/**
 * Controller for the Horizontal Tabs block.
 * Builds tab data from the block's inner blocks (saved order).
 */
class Horizontal_Tabs_Block_Controller extends Abstract_Block_Controller {

	/**
	 * Tab items built from inner blocks (id, buttonId, label).
	 *
	 * @var array<int, array{id: string, buttonId: string, label: string}>
	 */
	protected array $tabs = [];

	protected \WP_Block $block;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->block = $args['block'] ?? new \WP_Block( [ 'blockName' => 'tribe/horizontal-tabs' ] );
		$this->tabs  = $this->build_tabs_from_inner_blocks();
	}

	/**
	 * @return array<int, array{id: string, buttonId: string, label: string}>
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
	 * @return array<int, array{id: string, buttonId: string, label: string}>
	 */
	protected function build_tabs_from_inner_blocks(): array {
		$block        = $this->block->parsed_block;
		$inner_blocks = $block['innerBlocks'] ?? [];
		$tabs         = [];

		foreach ( $inner_blocks as $inner ) {
			if ( ( $inner['blockName'] ?? '' ) !== 'tribe/horizontal-tab' ) {
				continue;
			}

			$attrs  = $inner['attrs'] ?? [];
			$id     = $attrs['blockId'] ?? '';
			$label  = $attrs['tabLabel'] ?? '';
			$tabs[] = [
				'id'       => $id,
				'buttonId' => 'button-' . $id,
				'label'    => $label !== '' ? $label : __( 'Tab Label', 'tribe' ),
			];
		}

		return $tabs;
	}

}
