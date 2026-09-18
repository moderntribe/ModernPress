<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks\Tabs;

/**
 * Controller for the Horizontal Tabs block.
 */
class Horizontal_Tabs_Block_Controller extends Base_Tabs_Block_Controller {

	public const INNER_BLOCK_NAME = 'tribe/horizontal-tab';
	public const MAIN_BLOCK_NAME  = 'tribe/horizontal-tabs';

	/**
	 * @param array<string, mixed> $attributes
	 *
	 * @return array{id: string, buttonId: string, label: string}
	 */
	protected function map_tab_attributes( array $attributes ): array {
		$id    = $attributes['blockId'] ?? '';
		$label = $attributes['tabLabel'] ?? '';

		return [
			'id'       => $id,
			'buttonId' => 'button-' . $id,
			'label'    => $label !== '' ? $label : __( 'Tab Label', 'tribe' ),
		];
	}

}
