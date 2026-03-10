<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;
use Tribe\Plugin\Post_Types\Post\Post;
use Tribe\Plugin\Taxonomies\Category\Category;

class Related_Posts_Controller extends Abstract_Block_Controller {

	/**
	 * @var array <mixed>
	 */
	protected array $query_args;

	/**
	 * @var array <mixed>
	 */
	protected array $chosen_posts;
	protected int|false $post_id;
	protected bool $has_automatic_selection;
	protected int $posts_to_show;
	protected string $block_layout;
	protected \WP_Query $query;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->attributes              = $args['attributes'] ?? [];
		$this->post_id                 = get_the_ID();
		$this->has_automatic_selection = $this->attributes['hasAutomaticSelection'] ?? true;
		$this->chosen_posts            = $this->attributes['chosenPosts'] ?? [];
		$this->posts_to_show           = absint( $this->attributes['postsToShow'] ?? 3 );
		$this->block_layout            = $this->attributes['layout'] ?? 'grid';

		$this->block_classes .= " b-related-posts--layout-{$this->block_layout}";

		$this->set_query_args();
		$this->set_query();
	}

	public function should_bail_early(): bool {
		return ! $this->has_automatic_selection && empty( $this->chosen_posts );
	}

	public function get_query(): \WP_Query {
		return $this->query;
	}

	private function set_query_args(): void {
		$this->query_args = [
			'post_type'   => Post::NAME,
			'post_status' => 'publish',
		];

		if ( ! $this->has_automatic_selection ) {
			$this->query_args = array_merge( $this->query_args, [
				'post__in' => array_map( 'absint', wp_list_pluck( $this->chosen_posts, 'id' ) ),
				'orderby'  => 'post__in',
			] );

			return;
		}

		$this->query_args = array_merge( $this->query_args, [
			'posts_per_page' => $this->posts_to_show,
			'post__not_in'   => [ $this->post_id ],
		] );

		$post_terms = get_the_terms( $this->post_id, Category::NAME );

		if ( empty( $post_terms ) || is_wp_error( $post_terms ) ) {
			return;
		}

		$term_ids = wp_list_pluck( $post_terms, 'term_id' );

		$this->query_args['tax_query'][] = [
			'taxonomy' => Category::NAME,
			'field'    => 'term_id',
			'terms'    => $term_ids,
		];
	}

	private function set_query(): void {
		$this->query = new \WP_Query( $this->query_args );
	}

}
