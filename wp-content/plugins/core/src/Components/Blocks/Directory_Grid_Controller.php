<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;
use Tribe\Plugin\Facets\Directory_Query;
use Tribe\Plugin\Facets\Facet_Registry;

class Directory_Grid_Controller extends Abstract_Block_Controller {

	/**
	 * Page size bounds. Enforced here rather than at each entry point, so the
	 * REST endpoint and a hand-edited block attribute get the same ceiling.
	 */
	public const int MAX_POSTS_PER_PAGE     = 100;
	public const int DEFAULT_POSTS_PER_PAGE = 12;

	private const string POST_TYPES_CONTEXT = 'tribe/faceted-directory/postTypes';

	protected \WP_Query $query;
	protected int $found_posts;
	protected int $max_num_pages;
	protected int $paged;

	/**
	 * @var list<string>
	 */
	protected array $post_types;
	protected int $posts_per_page;
	protected bool $show_pagination;
	protected string $pagination_base;

	/**
	 * @var array<string, mixed>
	 */
	protected array $request;

	public function __construct( array $args = [] ) {
		parent::__construct( $args );

		$this->pagination_base = (string) ( $args['pagination_base'] ?? '' );

		$this->block_classes .= ' b-directory-grid__template';

		$from_context = $this->get_context_value( self::POST_TYPES_CONTEXT );
		$from_attrs   = $this->attributes['postTypes'] ?? [ 'post' ];
		$post_types   = is_array( $from_context ) ? $from_context : $from_attrs;

		$this->post_types = Facet_Registry::filter_public_post_types(
			is_array( $post_types ) ? array_values( $post_types ) : []
		);

		// A zero or missing page size means "use the default", not "one post".
		$per_page              = absint( $this->attributes['postsPerPage'] ?? 0 ) ?: self::DEFAULT_POSTS_PER_PAGE;
		$this->posts_per_page  = min( self::MAX_POSTS_PER_PAGE, $per_page );
		$this->show_pagination = (bool) ( $this->attributes['showPagination'] ?? false );

		$this->set_query( $args['request'] ?? null );
	}

	public function get_query(): \WP_Query {
		return $this->query;
	}

	/**
	 * Total matches across every page. Prefer this over the WP_Query property:
	 * on the indexed path WP_Query only ever sees one page.
	 */
	public function get_found_posts(): int {
		return $this->found_posts;
	}

	public function get_max_num_pages(): int {
		return $this->max_num_pages;
	}

	public function should_show_pagination(): bool {
		return $this->show_pagination && $this->max_num_pages > 1;
	}

	/**
	 * @return list<string>
	 */
	public function get_post_types(): array {
		return $this->post_types;
	}

	public function get_posts_per_page(): int {
		return $this->posts_per_page;
	}

	public function shows_pagination(): bool {
		return $this->show_pagination;
	}

	/**
	 * Numeric pagination. Links stay real URLs so pagination works without JS;
	 * the view script upgrades them to AJAX requests.
	 */
	public function get_pagination_html(): string {
		if ( ! $this->should_show_pagination() ) {
			return '';
		}

		$base = '' !== $this->pagination_base
			? $this->pagination_base
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			: strtok( (string) ( $_SERVER['REQUEST_URI'] ?? '/' ), '?' );

		$links = paginate_links( [
			'base'      => $base . '%_%',
			'format'    => '?' . Facet_Registry::PAGE_PARAM . '=%#%',
			'add_args'  => tribe_project()->container()->get( Directory_Query::class )
				->get_active_query_args( $this->request ),
			'total'     => $this->max_num_pages,
			'current'   => $this->paged,
			'type'      => 'list',
			'prev_text' => __( 'Previous', 'tribe' ),
			'next_text' => __( 'Next', 'tribe' ),
		] );

		return is_string( $links ) ? $links : '';
	}

	/**
	 * @param array<string, mixed>|null $request
	 */
	private function set_query( ?array $request ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$request     ??= wp_unslash( $_GET );
		$this->request = is_array( $request ) ? $request : [];
		$this->paged   = max( 1, absint( $this->request[ Facet_Registry::PAGE_PARAM ] ?? get_query_var( 'paged' ) ?: 1 ) );

		$built = tribe_project()->container()->get( Directory_Query::class )->build(
			$this->post_types,
			$this->posts_per_page,
			$this->request,
			$this->paged
		);

		$this->query = new \WP_Query( $built['args'] );

		// The index paginates before WP_Query sees the results, so its totals
		// are the only ones that describe the whole match set.
		$this->found_posts = $built['total'] ?? (int) $this->query->found_posts;

		$this->max_num_pages = null !== $built['total']
			? (int) ceil( $built['total'] / max( 1, $this->posts_per_page ) )
			: (int) $this->query->max_num_pages;
	}

}
