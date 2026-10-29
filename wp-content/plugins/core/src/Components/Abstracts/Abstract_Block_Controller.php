<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Abstracts;

use Tribe\Plugin\Blocks\Helpers\Block_Animation_Attributes;

abstract class Abstract_Block_Controller extends Abstract_Controller {

	/**
	 * Query arg the editor uses to stand in for block context.
	 */
	private const string PREVIEW_CONTEXT_PARAM = 'tribePreviewContext';

	/**
	 * @var array <mixed>
	 */
	protected array $attributes;

	/**
	 * @var array <mixed>
	 */
	protected array $context;
	protected string $block_classes;
	protected string $block_styles;
	private Block_Animation_Attributes|false $block_animation_attributes;
	private string $block_animation_classes;
	private string $block_animation_styles;

	/**
	 * Whether editor-preview query args may stand in for missing block context.
	 * Disabled for public endpoints that share these controllers.
	 */
	private bool $allow_preview_context;

	public function __construct( array $args = [] ) {
		$this->attributes                 = $args['attributes'] ?? [];
		$this->context                    = $args['context'] ?? [];
		$this->block_classes              = $args['block_classes'] ?? '';
		$this->block_styles               = $args['block_styles'] ?? '';
		$this->allow_preview_context      = (bool) ( $args['allow_preview_context'] ?? true );
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

	/**
	 * Block context, with a fallback for the editor preview.
	 *
	 * The block-renderer REST route renders a block on its own, so `usesContext`
	 * values from a parent block never reach the preview. The editor sends them
	 * as query args instead, keyed by the last segment of the context name.
	 */
	protected function get_context_value( string $key, mixed $default = null ): mixed {
		if ( isset( $this->context[ $key ] ) ) {
			return $this->context[ $key ];
		}

		$parts = explode( '/', $key );

		return $this->get_preview_context()[ end( $parts ) ] ?? $default;
	}

	/**
	 * Only trusted inside a REST render for users who can already edit posts,
	 * which is the same bar the block-renderer route enforces.
	 *
	 * @return array<string, mixed>
	 */
	private function get_preview_context(): array {
		if (
			! $this->allow_preview_context
			|| ! defined( 'REST_REQUEST' )
			|| ! REST_REQUEST
			|| ! current_user_can( 'edit_posts' )
		) {
			return [];
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$preview = wp_unslash( $_GET[ self::PREVIEW_CONTEXT_PARAM ] ?? [] );

		return is_array( $preview ) ? $preview : [];
	}

}
