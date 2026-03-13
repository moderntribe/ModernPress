<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;
use Tribe\Plugin\Integrations\FacetWP;

class FacetWP_Grid_Controller extends Abstract_Block_Controller {

	protected \WP_Query $query;
	protected string $post_type;
	protected bool $show_all_posts;
	protected int $posts_per_page;
	protected bool $show_pagination;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->block_classes .= ' facetwp-template';

		$this->post_type       = $this->attributes['postType'] ?? 'post';
		$this->show_all_posts  = $this->attributes['showAllPosts'] ?? false;
		$this->posts_per_page  = absint( $this->attributes['postsPerPage'] ?? 12 );
		$this->show_pagination = $this->attributes['showPagination'] ?? false;

		$this->set_query();
	}

	public function get_query(): \WP_Query {
		return $this->query;
	}

	public function get_pagination_facet(): ?array {
		if ( ! function_exists( 'FWP' ) ) {
			return null;
		}

		$facets = FWP()->helper->get_facets();

		$pagination_facet = array_values( array_filter( $facets, function ( $facet ) {
			return $facet['type'] === 'pager' && $facet['pager_type'] === 'numbers';
		} ) );

		return $pagination_facet[0] ?? null;
	}

	public function should_show_pagination(): bool {
		// We need to make sure a pagination facet exists
		if ( is_null( $this->get_pagination_facet() ) ) {
			return false;
		}

		return $this->show_pagination && $this->query->max_num_pages > 1;
	}

	private function set_query(): void {
		$this->query = new \WP_Query( [
			'post_type'      => $this->post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $this->show_all_posts ? -1 : $this->posts_per_page,
			'facetwp'        => true,
		] );
	}

}
