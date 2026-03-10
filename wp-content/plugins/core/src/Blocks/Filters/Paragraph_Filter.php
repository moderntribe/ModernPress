<?php declare(strict_types=1);

namespace Tribe\Plugin\Blocks\Filters;

use Tribe\Plugin\Blocks\Filters\Contracts\Block_Content_Filter;
use Tribe\Plugin\Blocks\Filters\Traits\Add_Block_Default_Class_Name;

class Paragraph_Filter extends Block_Content_Filter {

	use Add_Block_Default_Class_Name;

	public const string BLOCK = 'core/paragraph';

	public function filter_block_content( string $block_content, array $parsed_block, object $block ): string {
		return $this->add_class_name( $block_content, $parsed_block, $block );
	}

}
