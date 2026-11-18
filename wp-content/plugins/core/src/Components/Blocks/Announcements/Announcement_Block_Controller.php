<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks\Announcements;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;

class Announcement_Block_Controller extends Abstract_Block_Controller {

	protected const array DARK_THEMES = [ 'brand', 'black', 'error' ];

	protected string $announcement_id;
	protected string $heading;
	protected string $body;
	protected string $cta_label;
	protected string $cta_link;
	protected string $cta_style;
	protected string $text_alignment;
	protected string $orientation;
	protected string $theme;
	protected bool $dismissible;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->heading         = $this->attributes['heading'] ?? '';
		$this->body            = $this->attributes['body'] ?? '';
		$this->cta_label       = $this->attributes['ctaLabel'] ?? '';
		$this->cta_link        = $this->attributes['ctaLink'] ?? '';
		$this->announcement_id = $this->resolve_announcement_id( absint( $this->attributes['announcementId'] ?? 0 ) );
		$this->cta_style       = $this->attributes['ctaStyle'] ?? 'outlined';
		$this->text_alignment  = $this->attributes['textAlignment'] ?? 'center';
		$this->orientation     = $this->attributes['orientation'] ?? 'horizontal';
		$this->theme           = $this->attributes['theme'] ?? 'brand';
		$this->dismissible     = $this->attributes['dismissible'] ?? false;

		// set classes
		$this->block_classes .= " b-announcement--theme-{$this->theme}";
		$this->block_classes .= " b-announcement--align-{$this->text_alignment}";

		if ( 'vertical' === $this->orientation ) {
			$this->block_classes .= ' b-announcement--orientation-vertical';
		}

		if ( ! in_array( $this->theme, self::DARK_THEMES ) ) {
			return;
		}

		$this->block_classes .= ' is-style-dark';
	}

	public function get_announcement_id(): string {
		return $this->announcement_id;
	}

	public function has_heading(): bool {
		return ! empty( $this->heading );
	}

	public function get_heading(): string {
		return $this->heading;
	}

	public function has_body(): bool {
		return ! empty( $this->body );
	}

	public function get_body(): string {
		return $this->body;
	}

	public function has_cta(): bool {
		return ! empty( $this->cta_label ) && ! empty( $this->cta_link );
	}

	public function get_cta_link(): string {
		return $this->cta_link;
	}

	public function get_cta_style(): string {
		return $this->cta_style;
	}

	public function get_cta_label(): string {
		return $this->cta_label;
	}

	public function is_dismissible(): bool {
		return filter_var( $this->dismissible, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * CPT announcements use their post ID. Inline blocks get the current post ID plus a hash of their content.
	 */
	private function resolve_announcement_id( int $announcement_id ): string {
		if ( $announcement_id > 0 ) {
			return (string) $announcement_id;
		}

		return sprintf(
			'%d-%s',
			(int) get_the_ID(),
			substr( md5( implode( '|', [ $this->heading, $this->body, $this->cta_label, $this->cta_link ] ) ), 0, 8 )
		);
	}

}
