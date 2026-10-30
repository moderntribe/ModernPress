<?php
/**
 * Learn site search and content retrieval.
 *
 * @package Tribe\AI
 */

declare( strict_types=1 );

namespace Tribe\AI\AI;

/**
 * Class Learn_Searcher
 */
class Learn_Searcher {
	/**
	 * Transient prefix.
	 */
	private const CACHE_PREFIX = 'tribe_ai_search_';

	/**
	 * Cache TTL — short since search results can change.
	 */
	private const CACHE_TTL = 10 * MINUTE_IN_SECONDS;

	/**
	 * Max content chars per article passed to the AI.
	 */
	private const MAX_CONTENT_CHARS = 1500;

	/**
	 * Search learn.tri.be and return enriched article data.
	 *
	 * @param string $learn_host Base URL of the learn site.
	 * @param string $query      User question or search terms.
	 * @param int    $limit      Max number of articles to return.
	 * @return array<int,array{title:string,url:string,content:string}>
	 */
	public function search( string $learn_host, string $query, int $limit = 3 ): array {
		$cache_key = self::CACHE_PREFIX . md5( $learn_host . '|' . $query . '|' . $limit );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$results = $this->fetch_results( $learn_host, $query, $limit );
		set_transient( $cache_key, $results, self::CACHE_TTL );

		return $results;
	}

	/**
	 * Run the search and fetch article content.
	 *
	 * @param string $learn_host Base URL.
	 * @param string $query      Search query.
	 * @param int    $limit      Max results.
	 * @return array
	 */
	private function fetch_results( string $learn_host, string $query, int $limit ): array {
		$search_url = trailingslashit( $learn_host ) . 'wp-json/wp/v2/search?' . http_build_query(
			[
				'search'   => $query,
				'per_page' => $limit,
				'type'     => 'post',
			]
		);

		$response = wp_remote_get(
			$search_url,
			[
				'timeout' => 8,
				'headers' => [ 'Accept' => 'application/json' ],
			]
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return [];
		}

		$items = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $items ) || empty( $items ) ) {
			return [];
		}

		$articles = [];
		foreach ( array_slice( $items, 0, $limit ) as $item ) {
			$article = $this->fetch_article( $learn_host, $item );
			if ( null !== $article ) {
				$articles[] = $article;
			}
		}

		return $articles;
	}

	/**
	 * Fetch full content for a single search result item.
	 *
	 * @param string $learn_host Base URL.
	 * @param array  $item       Search result item.
	 * @return array{title:string,url:string,content:string}|null
	 */
	private function fetch_article( string $learn_host, array $item ): ?array {
		$id      = (int) ( $item['id'] ?? 0 );
		$subtype = (string) ( $item['subtype'] ?? 'post' );
		$title   = wp_strip_all_tags( (string) ( $item['title'] ?? '' ) );
		$url     = esc_url_raw( (string) ( $item['url'] ?? '' ) );

		if ( $id <= 0 ) {
			return null;
		}

		$rest_base = 'page' === $subtype ? 'pages' : 'posts';
		$endpoint  = trailingslashit( $learn_host ) . "wp-json/wp/v2/{$rest_base}/{$id}";

		$response = wp_remote_get(
			$endpoint,
			[
				'timeout' => 8,
				'headers' => [ 'Accept' => 'application/json' ],
			]
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// Return stub with title/url even if full content is unavailable.
			return [
				'title'   => $title,
				'url'     => $url,
				'content' => '',
			];
		}

		$data    = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$content = wp_strip_all_tags( (string) ( $data['content']['rendered'] ?? '' ) );
		$content = mb_substr( trim( $content ), 0, self::MAX_CONTENT_CHARS );

		return [
			'title'   => $title ?: wp_strip_all_tags( (string) ( $data['title']['rendered'] ?? '' ) ),
			'url'     => $url ?: esc_url_raw( (string) ( $data['link'] ?? '' ) ),
			'content' => $content,
		];
	}
}
