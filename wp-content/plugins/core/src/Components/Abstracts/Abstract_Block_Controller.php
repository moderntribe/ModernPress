<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Abstracts;

use Tribe\Plugin\Blocks\Helpers\Block_Animation_Attributes;

abstract class Abstract_Block_Controller extends Abstract_Controller {

	/**
	 * @var array <mixed>
	 */
	protected array $attributes;
	protected string $block_classes;
	protected string $block_styles;
	private Block_Animation_Attributes|false $block_animation_attributes;
	private string $block_animation_classes;
	private string $block_animation_styles;

	public function __construct( array $args = [] ) {
		$this->attributes                 = $args['attributes'] ?? [];
		$this->block_classes              = $args['block_classes'] ?? '';
		$this->block_styles               = $args['block_styles'] ?? '';
		$this->block_animation_attributes = $this->attributes ? new Block_Animation_Attributes( $this->attributes ) : false;
		$this->block_animation_classes    = $this->block_animation_attributes ? $this->block_animation_attributes->get_classes() : '';
		$this->block_animation_styles     = $this->block_animation_attributes ? $this->block_animation_attributes->get_styles() : '';
	}

	public function get_block_classes(): string {
		$classes = $this->block_classes;

		if ( '' !== $this->block_animation_classes ) {
			$classes .= ' ' . $this->block_animation_classes;
		}

		return $classes;
	}

	public function get_block_styles(): string {
		$styles = $this->block_styles;

		if ( '' !== $this->block_animation_styles ) {
			$styles .= ' ' . $this->block_animation_styles;
		}

		return $styles;
	}

}
