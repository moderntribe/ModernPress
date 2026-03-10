<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;
use Tribe\Plugin\Components\Traits\Primary_Term;
use Tribe\Plugin\Taxonomies\Category\Category;

class Terms_Block_Controller extends Abstract_Block_Controller {

	use Primary_Term;

	protected string $taxonomy;
	protected bool $only_primary_term;
	protected bool $has_links;

	/**
	 * @var \WP_Term[]
	 */
	protected array $terms = [];

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->taxonomy          = $this->attributes['taxonomyToUse'] ?? Category::NAME;
		$this->only_primary_term = $this->attributes['onlyPrimaryTerm'] ?? false;
		$this->has_links         = $this->attributes['hasLinks'] ?? false;
	}

	/**
	 * @return \WP_Term[]
	 */
	public function get_the_terms(): array {
		$post_id = get_the_ID();

		if ( ! $post_id ) {
			return $this->terms;
		}

		if ( $this->only_primary_term ) {
			$term = $this->get_primary_term( $post_id, $this->taxonomy );
			if ( $term ) {
				$this->terms[] = $term;
			}
		} else {
			$terms = get_the_terms( $post_id, $this->taxonomy );
			if ( $terms && ! is_wp_error( $terms ) ) {
				$this->terms = $terms;
			}
		}

		return $this->terms;
	}

	public function display_as_links(): bool {
		return (bool) $this->has_links;
	}

	public function get_taxonomy_name(): string {
		$taxonomy_object = get_taxonomy( $this->taxonomy );

		return $taxonomy_object
			? $taxonomy_object->labels->name
			: esc_html_x( 'terms', 'Generic name for an unknown taxonomy\'s items', 'tribe' );
	}

}
