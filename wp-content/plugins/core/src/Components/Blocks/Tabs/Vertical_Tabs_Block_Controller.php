<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks\Tabs;

/**
 * Controller for the Vertical Tabs block.
 */
class Vertical_Tabs_Block_Controller extends Base_Tabs_Block_Controller {

	public const INNER_BLOCK_NAME = 'tribe/vertical-tab';
	public const MAIN_BLOCK_NAME  = 'tribe/vertical-tabs';

	/**
	 * @param array<string, mixed> $attributes
	 *
	 * @return array{id: string, buttonId: string, title: string, content: string}
	 */
	protected function map_tab_attributes( array $attributes ): array {
		$id      = $attributes['blockId'] ?? '';
		$title   = $attributes['title'] ?? '';
		$content = $attributes['content'] ?? '';

		return [
			'id'       => $id,
			'buttonId' => 'vt-button-' . $id,
			'title'    => $title !== '' ? $title : __( 'Tab Heading', 'tribe' ),
			'content'  => $content,
		];
	}

}
