<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;
use Tribe\Plugin\Menus\Current_Page_Walker;

class Inline_Navigation_Controller extends Abstract_Block_Controller {

	protected string $mode;
	protected string $button_label;
	protected string $button_url;
	protected bool $button_opens_in_new_tab;
	protected string $mobile_menu_toggle_label;

	/**
	 * @var list<string>
	 */
	protected array $share_services;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->mode                     = $this->attributes['mode'] ?? 'siblings';
		$this->share_services           = $this->attributes['shareServices'] ?? [];
		$this->button_label             = $this->attributes['buttonLabel'] ?? '';
		$this->button_url               = $this->attributes['buttonUrl'] ?? '';
		$this->button_opens_in_new_tab  = $this->attributes['buttonOpensInNewTab'] ?? false;
		$this->mobile_menu_toggle_label = $this->attributes['mobileMenuToggleLabel'] ?? '';
	}

	public function get_mode(): string {
		return $this->mode;
	}

	public function get_mobile_menu_toggle_label(): string {
		return $this->mobile_menu_toggle_label;
	}

	public function render_pages(): string {
		if ( ! in_array( $this->mode, [ 'siblings', 'children' ], true ) ) {
			return '';
		}

		$post_id = $this->get_context_value( 'postId', 0 );
		if ( ! $post_id ) {
			return '';
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		$parent_id = 'children' === $this->mode ? $post->ID : $post->post_parent;

		return wp_list_pages( [
			'parent'       => $parent_id,
			'depth'        => 1,
			'title_li'     => '',
			'echo'         => false,
			'current_page' => $post->ID,
			'walker'       => new Current_Page_Walker(),
		] );
	}

	public function render_manual_pages(): string {
		$items = $this->attributes['manualItems'] ?? [];
		if ( ! is_array( $items ) ) {
			return '';
		}

		$post_id     = $this->get_context_value( 'postId', 0 );
		$post_id     = is_numeric( $post_id ) ? (int) $post_id : 0;
		$current_url = $post_id ? get_permalink( $post_id ) : false;
		$output      = '';

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || ! is_string( $item['label'] ?? null ) || ! is_string( $item['url'] ?? null ) ) {
				continue;
			}

			$label = trim( $item['label'] );
			$url   = esc_url_raw( trim( $item['url'] ) );
			if ( '' === $label || '' === $url ) {
				continue;
			}

			$linked_post_id = url_to_postid( $url );
			$is_current     = $post_id && (
				$linked_post_id === $post_id
				|| ( $current_url && untrailingslashit( $url ) === untrailingslashit( $current_url ) )
				|| ( $current_url && str_starts_with( $url, '/' ) && ! str_starts_with( $url, '//' )
					&& untrailingslashit( $url ) === untrailingslashit( wp_make_link_relative( $current_url ) ) )
			);
			$linked_post_id = $is_current ? $post_id : $linked_post_id;
			$classes        = 'b-inline-navigation__list-item page_item';
			if ( $linked_post_id ) {
				$classes .= ' page-item-' . $linked_post_id;
			}
			if ( $is_current ) {
				$classes .= ' current_page_item';
			}

			$output .= sprintf(
				'<li class="%s"><a href="%s" class="b-inline-navigation__list-link"%s%s>%s</a></li>',
				esc_attr( $classes ),
				esc_url( $url ),
				$is_current ? ' aria-current="page"' : '',
				! empty( $item['opensInNewTab'] ) ? ' target="_blank" rel="noopener noreferrer"' : '',
				esc_html( $label )
			);
		}

		return $output;
	}

	public function render_share_services(): string {
		if ( ! $this->share_services ) {
			return '';
		}

		$links = [];

		foreach ( $this->share_services as $service ) {
			if ( ! is_string( $service ) || '' === $service ) {
				continue;
			}

			$links[] = [
				'blockName'    => 'outermost/social-sharing-link',
				'attrs'        => [ 'service' => $service ],
				'innerBlocks'  => [],
				'innerHTML'    => '',
				'innerContent' => [],
			];
		}

		if ( ! $links ) {
			return '';
		}

		return render_block( [
			'blockName'    => 'outermost/social-sharing',
			'attrs'        => [
				'className'      => 'is-style-logos-only b-inline-navigation__social',
				'iconColor'      => 'base-black',
				'iconColorValue' => '#000000',
			],
			'innerBlocks'  => $links,
			'innerHTML'    => '<ul class="wp-block-outermost-social-sharing is-style-logos-only b-inline-navigation__social"></ul>',
			'innerContent' => array_merge(
				[ '<ul class="wp-block-outermost-social-sharing is-style-logos-only b-inline-navigation__social">' ],
				array_fill( 0, count( $links ), null ),
				[ '</ul>' ]
			),
		] );
	}

	public function render_cta(): string {
		$label = trim( $this->button_label );
		$url   = esc_url( $this->button_url );

		if ( '' === $label || '' === $url ) {
			return '';
		}

		$target = $this->button_opens_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
		$button = sprintf(
			'<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s"%s>%s</a></div>',
			$url,
			$target,
			esc_html( $label )
		);

		return render_block( [
			'blockName'    => 'core/buttons',
			'attrs'        => [
				'className' => 'b-inline-navigation__cta',
			],
			'innerBlocks'  => [
				[
					'blockName'    => 'core/button',
					'attrs'        => [],
					'innerBlocks'  => [],
					'innerHTML'    => $button,
					'innerContent' => [ $button ],
				],
			],
			'innerHTML'    => '<div class="wp-block-buttons b-inline-navigation__cta"></div>',
			'innerContent' => [ '<div class="wp-block-buttons b-inline-navigation__cta">', null, '</div>' ],
		] );
	}

}
