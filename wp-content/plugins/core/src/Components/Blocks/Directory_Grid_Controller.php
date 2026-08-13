<?php declare(strict_types=1);

namespace Tribe\Plugin\Components\Blocks;

use Tribe\Plugin\Components\Abstracts\Abstract_Block_Controller;
use Tribe\Plugin\Facets\Directory_Query;
use Tribe\Plugin\Facets\Facet_Registry;

class Directory_Grid_Controller extends Abstract_Block_Controller {

	private const string POST_TYPES_CONTEXT = 'tribe/faceted-directory/postTypes';

	protected \WP_Query $query;

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

		$this->post_types      = is_array( $post_types )
			? array_values( array_filter( array_map( 'sanitize_key', $post_types ) ) )
			: [ 'post' ];
		$this->post_types      = $this->filter_public_post_types( $this->post_types );
		$this->posts_per_page  = absint( $this->attributes['postsPerPage'] ?? 12 );
		$this->show_pagination = (bool) ( $this->attributes['showPagination'] ?? false );

		$this->set_query( $args['request'] ?? null );
	}

	public function get_query(): \WP_Query {
		return $this->query;
	}

	public function should_show_pagination(): bool {
		return $this->show_pagination && $this->query->max_num_pages > 1;
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
			'total'     => (int) $this->query->max_num_pages,
			'current'   => max( 1, (int) ( $this->query->get( 'paged' ) ?: 1 ) ),
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
		$paged         = absint( $this->request[ Facet_Registry::PAGE_PARAM ] ?? get_query_var( 'paged' ) ?: 1 );

		$args = tribe_project()->container()->get( Directory_Query::class )->build_args(
			$this->post_types,
			$this->posts_per_page,
			$this->request,
			$paged
		);

		$this->query = new \WP_Query( $args );
	}

	/**
	 * @param list<string> $post_types
	 *
	 * @return list<string>
	 */
	private function filter_public_post_types( array $post_types ): array {
		$allowed = get_post_types( [ 'public' => true ] );

		unset( $allowed['attachment'] );

		$sanitized = array_values( array_intersect( $post_types, array_keys( $allowed ) ) );

		return [] === $sanitized ? [ 'post' ] : $sanitized;
	}

}
