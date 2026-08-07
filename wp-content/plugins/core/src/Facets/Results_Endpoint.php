<?php declare(strict_types=1);

namespace Tribe\Plugin\Facets;

use Tribe\Plugin\Components\Blocks\Directory_Grid_Controller;

/**
 * Read-only endpoint that re-renders a directory grid for AJAX refreshes.
 *
 * Block attributes arrive from the client, so everything is re-validated here:
 * public post types only, capped page size, and facet params resolved against
 * the registry rather than trusted as given.
 */
class Results_Endpoint {

	public const string REST_NAMESPACE = 'tribe/v1';
	public const string ROUTE          = '/facets/results';

	private const int MAX_POSTS_PER_PAGE     = 100;
	private const int DEFAULT_POSTS_PER_PAGE = 12;

	public function __construct(
		private Facet_Registry $registry,
	) {
	}

	public function register_route(): void {
		register_rest_route( self::REST_NAMESPACE, self::ROUTE, [
			'methods'             => \WP_REST_Server::READABLE,
			'permission_callback' => '__return_true',
			'callback'            => [ $this, 'handle' ],
		] );
	}

	public function handle( \WP_REST_Request $request ): \WP_REST_Response {
		$post_types = $this->sanitize_post_types( (array) $request->get_param( 'post_types' ) );
		$paged      = max( 1, absint( $request->get_param( Facet_Registry::PAGE_PARAM ) ) );
		$path       = $this->sanitize_path( (string) $request->get_param( 'path' ) );

		$per_page = absint( $request->get_param( 'posts_per_page' ) );
		$per_page = 0 === $per_page ? self::DEFAULT_POSTS_PER_PAGE : $per_page;
		$per_page = min( self::MAX_POSTS_PER_PAGE, $per_page );

		$controller = Directory_Grid_Controller::factory( [
			'attributes'      => [
				'postTypes'      => $post_types,
				'postsPerPage'   => $per_page,
				'showPagination' => rest_sanitize_boolean( $request->get_param( 'show_pagination' ) ),
			],
			'request'         => $this->get_facet_request( $request, $paged ),
			'pagination_base' => $path,
			'block_classes'   => 'b-directory-grid',
		] );

		/**
		 * paginate_links() merges the current request's query string into its
		 * link args. Left alone, that would bake this endpoint's own params
		 * (post_types, posts_per_page, path) into the pagination URLs.
		 */
		$original_request_uri   = $_SERVER['REQUEST_URI'] ?? null;
		$_SERVER['REQUEST_URI'] = $path;

		try {
			ob_start();
			get_template_part( 'components/directory-grid/grid', null, [ 'controller' => $controller ] );
			$html = (string) ob_get_clean();
		} finally {
			if ( null === $original_request_uri ) {
				unset( $_SERVER['REQUEST_URI'] );
			} else {
				$_SERVER['REQUEST_URI'] = $original_request_uri;
			}
		}

		wp_reset_postdata();

		return new \WP_REST_Response( [
			'html'  => $html,
			'found' => (int) $controller->get_query()->found_posts,
		] );
	}

	/**
	 * @return array<string, mixed>
	 */
	private function get_facet_request( \WP_REST_Request $request, int $paged ): array {
		$facet_request = [ Facet_Registry::PAGE_PARAM => $paged ];

		foreach ( $this->registry->get_all() as $facet ) {
			$param = $this->registry->get_query_param( $facet['slug'] );
			$value = $request->get_param( $param );

			if ( null === $value ) {
				continue;
			}

			$facet_request[ $param ] = $value;
		}

		$search = $request->get_param( Facet_Registry::SEARCH_PARAM );

		if ( null !== $search ) {
			$facet_request[ Facet_Registry::SEARCH_PARAM ] = $search;
		}

		return $facet_request;
	}

	/**
	 * @param list<mixed> $post_types
	 *
	 * @return list<string>
	 */
	private function sanitize_post_types( array $post_types ): array {
		$allowed = get_post_types( [ 'public' => true ] );

		unset( $allowed['attachment'] );

		$sanitized = array_values( array_intersect(
			array_map( 'sanitize_key', array_map( 'strval', $post_types ) ),
			array_keys( $allowed )
		) );

		return [] === $sanitized ? [ 'post' ] : $sanitized;
	}

	/**
	 * Relative path used as the pagination link base.
	 */
	private function sanitize_path( string $path ): string {
		$parsed = wp_parse_url( $path, PHP_URL_PATH );

		if ( ! is_string( $parsed ) || '' === $parsed ) {
			return '/';
		}

		return '/' . ltrim( $parsed, '/' );
	}

}
