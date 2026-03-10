<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Abstracts;

abstract class Abstract_Card_Controller extends Abstract_Block_Controller {

	private int $media_id;
	private string $media_url;
	private string $title;
	private string $description;
	private string $link_url;
	private bool $link_opens_in_new_tab;
	private string $link_text;
	private string $link_a11y_label;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->media_id              = absint( $this->attributes['mediaId'] ?? 0 );
		$this->media_url             = $this->attributes['mediaUrl'] ?? '';
		$this->title                 = $this->attributes['title'] ?? '';
		$this->description           = $this->attributes['description'] ?? '';
		$this->link_url              = $this->attributes['linkUrl'] ?? '';
		$this->link_opens_in_new_tab = $this->attributes['linkOpensInNewTab'] ?? false;
		$this->link_text             = $this->attributes['linkText'] ?? '';
		$this->link_a11y_label       = $this->attributes['linkA11yLabel'] ?? '';
	}

	public function has_media(): bool {
		return 0 !== $this->media_id || '' !== $this->media_url;
	}

	public function get_media( string $image_size = 'large' ): string {
		if ( 0 !== $this->media_id ) {
			return wp_get_attachment_image( $this->media_id, $image_size );
		}

		if ( '' !== $this->media_url ) {
			return '<img src="' . esc_url( $this->media_url ) . '" alt="' . esc_attr__( 'Block placeholder image', 'tribe' ) . '">';
		}

		return '';
	}

	public function get_title(): string {
		return $this->title;
	}

	public function has_description(): bool {
		return '' !== $this->description;
	}

	public function get_description(): string {
		return $this->description;
	}

	public function has_link_url(): bool {
		return '' !== $this->link_url;
	}

	public function get_link_url(): string {
		return $this->link_url;
	}

	public function does_link_open_in_new_tab(): bool {
		return $this->link_opens_in_new_tab;
	}

	public function get_link_text(): string {
		return $this->link_text;
	}

	public function get_link_a11y_label(): string {
		return $this->link_a11y_label;
	}

}
