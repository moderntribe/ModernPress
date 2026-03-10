<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;

class Masthead_Search_Controller extends Abstract_Block_Controller {

	private string|false $search_icon;
	private string $search_icon_uri;
	private string $search_icon_path;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->search_icon      = '';
		$this->search_icon_uri  = trailingslashit( get_stylesheet_directory_uri() ) . '/assets/media/icons/search.svg';
		$this->search_icon_path = trailingslashit( get_stylesheet_directory() ) . '/assets/media/icons/search.svg';
	}

	public function get_search_icon(): string {
		// If the file doesn't exist or we have already loaded it, return early
		if ( '' !== $this->search_icon || ! file_exists( $this->search_icon_path ) ) {
			return $this->search_icon;
		}

		// Attempt to get the file contents from the file system
		$this->search_icon = file_get_contents( $this->search_icon_path );

		// Fallback to wp_remote_get if file_get_contents fails
		if ( $this->search_icon === false ) {
			$response = wp_remote_get( $this->search_icon_uri );

			if ( ! is_wp_error( $response ) ) {
				// wp_remote_retrieve_body returns an empty string on failure, so it's fine to end here
				$this->search_icon = wp_remote_retrieve_body( $response );
			}
		}

		return $this->search_icon;
	}

	public function get_toggle_button_a11y_label(): string {
		return __( 'Toggle Search Overlay', 'tribe' );
	}

	public function get_form_action(): string {
		return home_url();
	}

	public function get_label_text(): string {
		return __( 'Search', 'tribe' );
	}

	public function get_input_placeholder(): string {
		return __( 'What are you looking for?', 'tribe' );
	}

	public function get_submit_button_text(): string {
		return __( 'Search', 'tribe' );
	}

}
