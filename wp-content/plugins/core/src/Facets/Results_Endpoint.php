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

	/**
	 * Results are published content keyed entirely by the query string, so a
	 * short shared cache lets an edge or page cache absorb repeat requests —
	 * a live-filtering UI sends a lot of them. Core still sends no-cache for
	 * logged-in requests, which is what we want for editors.
	 *
	 * ponytail: cache headers only. Actual rate limiting belongs at the edge,
	 * not in a WordPress callback.
	 */
	private const int CACHE_MAX_AGE = MINUTE_IN_SECONDS;

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
		$post_types = Facet_Registry::filter_public_post_types( array_values( (array) $request->get_param( 'post_types' ) ) );
		$paged      = max( 1, absint( $request->get_param( Facet_Registry::PAGE_PARAM ) ) );
		$path       = $this->sanitize_path( (string) $request->get_param( 'path' ) );

		// The controller bounds this too; passing 0 through lets it apply its
		// own default rather than duplicating the number here.
		$per_page = absint( $request->get_param( 'posts_per_page' ) );

		$controller = Directory_Grid_Controller::factory( [
			'attributes'            => [
				'postTypes'      => $post_types,
				'postsPerPage'   => $per_page,
				'showPagination' => rest_sanitize_boolean( $request->get_param( 'show_pagination' ) ),
			],
			'request'               => $this->get_facet_request( $request, $paged ),
			'pagination_base'       => $path,
			'block_classes'         => 'b-directory-grid',
			'allow_preview_context' => false,
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

		$response = new \WP_REST_Response( [
			'html'  => $html,
			'found' => $controller->get_found_posts(),
		] );

		$response->header( 'Cache-Control', 'public, max-age=' . self::CACHE_MAX_AGE );

		return $response;
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
	 * Relative path used as the pagination link base.
	 *
	 * Rejects scheme/host and normalizes backslashes so values like `/\evil.com`
	 * cannot become protocol-relative URLs in pagination hrefs.
	 */
	private function sanitize_path( string $path ): string {
		$parsed = wp_parse_url( $path, PHP_URL_PATH );

		if ( ! is_string( $parsed ) || '' === $parsed ) {
			return '/';
		}

		// Browsers treat `\` as `/`; collapse so `/\evil` cannot become `//evil`.
		$parsed = str_replace( '\\', '/', $parsed );
		$parsed = (string) preg_replace( '#/+#', '/', $parsed );

		return '/' . ltrim( $parsed, '/' );
	}

}
