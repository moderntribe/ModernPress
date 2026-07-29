<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks\Tabs;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;

/**
 * Shared controller plumbing for tab parent blocks.
 * Subclasses set block name constants and map inner-block attributes to a tab row.
 */
abstract class Base_Tabs_Block_Controller extends Abstract_Block_Controller {

	public const INNER_BLOCK_NAME = '';
	public const MAIN_BLOCK_NAME  = '';

	/**
	 * @var array<int, array<string, mixed>>
	 */
	protected array $tabs = [];

	protected \WP_Block $block;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->block = $args['block'] ?? new \WP_Block( [ 'blockName' => static::MAIN_BLOCK_NAME ] );
		$this->tabs  = $this->build_tabs_from_inner_blocks();
	}

	/**
	 * @return array<int, array<string, mixed>>
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
	 * @return array<int, array<string, mixed>>
	 */
	protected function build_tabs_from_inner_blocks(): array {
		$parsed_block = $this->block->parsed_block ?? [];
		$inner_blocks = $parsed_block['innerBlocks'] ?? [];
		$tabs         = [];

		foreach ( $inner_blocks as $inner ) {
			if ( ( $inner['blockName'] ?? '' ) !== static::INNER_BLOCK_NAME ) {
				continue;
			}

			$tabs[] = $this->map_tab_attributes( $inner['attrs'] ?? [] );
		}

		return $tabs;
	}

	/**
	 * Map a single inner block's attributes to a tab row for the template.
	 *
	 * @param array<string, mixed> $attributes
	 *
	 * @return array<string, mixed>
	 */
	abstract protected function map_tab_attributes( array $attributes ): array;

}
