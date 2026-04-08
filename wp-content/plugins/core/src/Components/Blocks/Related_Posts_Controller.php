<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;
use Tribe\Plugin\Post_Types\Post\Post;
use Tribe\Plugin\Taxonomies\Category\Category;

class Related_Posts_Controller extends Abstract_Block_Controller {
	private const string LATEST_ITEMS_VALUE = '__latest__';

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
	protected string $taxonomy_slug;
	protected string $current_post_type;
	protected string $card_taxonomy_slug = '';
	protected \WP_Query $query;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->attributes              = $args['attributes'] ?? [];
		$this->post_id                 = get_the_ID();
		$this->has_automatic_selection = $this->attributes['hasAutomaticSelection'] ?? true;
		$this->chosen_posts            = $this->attributes['chosenPosts'] ?? [];
		$this->posts_to_show           = absint( $this->attributes['postsToShow'] ?? 3 );
		$this->block_layout            = $this->attributes['layout'] ?? 'grid';
		$this->current_post_type       = get_post_type( $this->post_id ) ?: Post::NAME;
		$this->taxonomy_slug           = $this->get_effective_taxonomy_slug( $this->attributes['taxonomySlug'] ?? Category::NAME );

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

	public function get_card_taxonomy_slug(): string {
		return $this->card_taxonomy_slug;
	}

	private function set_query_args(): void {
		$this->query_args = [
			'post_type'   => $this->current_post_type,
			'post_status' => 'publish',
		];

		if ( ! $this->has_automatic_selection ) {
			$this->query_args = array_merge( $this->query_args, [
				'post_type' => 'any',
				'post__in' => array_map( 'absint', wp_list_pluck( $this->chosen_posts, 'id' ) ),
				'orderby'  => 'post__in',
			] );

			return;
		}

		$this->query_args = array_merge( $this->query_args, [
			'posts_per_page' => $this->posts_to_show,
			'post__not_in'   => [ $this->post_id ],
		] );

		if ( self::LATEST_ITEMS_VALUE === $this->taxonomy_slug || ! is_object_in_taxonomy( $this->current_post_type, $this->taxonomy_slug ) ) {
			return;
		}

		$post_terms = get_the_terms( $this->post_id, $this->taxonomy_slug );

		if ( empty( $post_terms ) || is_wp_error( $post_terms ) ) {
			return;
		}

		$term_ids = wp_list_pluck( $post_terms, 'term_id' );

		$this->query_args['tax_query'][] = [
			'taxonomy' => $this->taxonomy_slug,
			'field'    => 'term_id',
			'terms'    => $term_ids,
		];
	}

	private function set_query(): void {
		$this->query = new \WP_Query( $this->query_args );

		if ( $this->has_automatic_selection && ! $this->query->have_posts() && ! empty( $this->query_args['tax_query'] ) ) {
			unset( $this->query_args['tax_query'] );
			$this->query = new \WP_Query( $this->query_args );
		}

		$this->card_taxonomy_slug = ! empty( $this->query_args['tax_query'] ) ? $this->taxonomy_slug : '';
	}

	/**
	 * @return array<string, \WP_Taxonomy>
	 */
	private function get_available_taxonomies(): array {
		return array_filter(
			get_object_taxonomies( $this->current_post_type, 'objects' ),
			static fn( \WP_Taxonomy $taxonomy ): bool => (bool) $taxonomy->show_in_rest
		);
	}

	private function get_effective_taxonomy_slug( string $taxonomy_slug ): string {
		if ( self::LATEST_ITEMS_VALUE === $taxonomy_slug ) {
			return $taxonomy_slug;
		}

		$available_taxonomies = $this->get_available_taxonomies();

		if ( is_object_in_taxonomy( $this->current_post_type, $taxonomy_slug ) ) {
			return $taxonomy_slug;
		}

		if ( array_key_exists( Category::NAME, $available_taxonomies ) ) {
			return Category::NAME;
		}

		return array_key_first( $available_taxonomies ) ?: '';
	}

}
