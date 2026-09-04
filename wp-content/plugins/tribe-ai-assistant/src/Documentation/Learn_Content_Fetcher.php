<?php
/**
 * Learn content fetcher.
 *
 * @package Tribe\AI
 */

declare( strict_types=1 );

namespace Tribe\AI\Documentation;

/**
 * Class Learn_Content_Fetcher
 */
class Learn_Content_Fetcher {
	/**
	 * Transient prefix.
	 */
	private const CACHE_PREFIX = 'tribe_ai_learn_doc_';

	/**
	 * Cache TTL.
	 */
	private const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Allowed iframe hosts.
	 *
	 * @var string[]
	 */
	private array $allowed_iframe_hosts = [
		'player.vimeo.com',
		'vimeo.com',
	];

	/**
	 * Fetch normalized Learn content.
	 *
	 * @param string $learn_host Learn site host.
	 * @param array  $document   Document descriptor.
	 * @return array<string,mixed>
	 */
	public function fetch_document( string $learn_host, array $document ): array {
		$cache_key = $this->get_cache_key( $learn_host, $document );
		$cached    = get_transient( $cache_key );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		$fallback = $this->build_fallback_payload( $learn_host, $document );
		$response = $this->request_document( $learn_host, $document );

		if ( null === $response ) {
			set_transient( $cache_key, $fallback, self::CACHE_TTL );
			return $fallback;
		}

		$payload = $this->normalize_remote_document( $response, $learn_host, $document );
		set_transient( $cache_key, $payload, self::CACHE_TTL );

		return $payload;
	}

	/**
	 * Request a document from the Learn REST API.
	 *
	 * @param string $learn_host Learn host.
	 * @param array  $document   Document descriptor.
	 * @return array<string,mixed>|null
	 */
	private function request_document( string $learn_host, array $document ): ?array {
		$rest_base = sanitize_key( (string) ( $document['rest_base'] ?? 'pages' ) );
		$slug      = sanitize_title( (string) ( $document['slug'] ?? '' ) );

		if ( '' === $rest_base || '' === $slug ) {
			return null;
		}

		$url = trailingslashit( $learn_host ) . 'wp-json/wp/v2/' . $rest_base . '?slug=' . rawurlencode( $slug );
		$response = wp_remote_get(
			$url,
			[
				'timeout' => 8,
				'headers' => [
					'Accept' => 'application/json',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return null;
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body[0] ) || ! is_array( $body[0] ) ) {
			return null;
		}

		return $body[0];
	}

	/**
	 * Normalize the remote response.
	 *
	 * @param array  $response   Remote response.
	 * @param string $learn_host Learn host.
	 * @param array  $document   Document descriptor.
	 * @return array<string,mixed>
	 */
	private function normalize_remote_document( array $response, string $learn_host, array $document ): array {
		$title        = wp_strip_all_tags( (string) ( $response['title']['rendered'] ?? '' ) );
		$article_url  = esc_url_raw( (string) ( $response['link'] ?? $this->build_article_url( $learn_host, $document ) ) );
		$excerpt_html = $this->sanitize_html_fragment( (string) ( $response['excerpt']['rendered'] ?? '' ) );
		$content_html = $this->extract_table_of_contents( (string) ( $response['content']['rendered'] ?? '' ), $article_url );
		$video_embed  = $this->extract_allowed_iframe_src( (string) ( $response['content']['rendered'] ?? '' ) );

		return [
			'id'              => sanitize_title( (string) ( $document['slug'] ?? $title ) ),
			'title'           => $title,
			'article_url'     => $article_url,
			'excerpt_html'    => $excerpt_html,
			'content_html'    => $content_html,
			'video_embed_url' => $video_embed,
			'video_url'       => $video_embed,
			'type'            => 'article',
			'section'         => (string) ( $document['section'] ?? '' ),
			'is_fallback'     => false,
		];
	}

	/**
	 * Build fallback payload when remote content is unavailable.
	 *
	 * @param string $learn_host Learn host.
	 * @param array  $document   Document descriptor.
	 * @return array<string,mixed>
	 */
	private function build_fallback_payload( string $learn_host, array $document ): array {
		return [
			'id'              => sanitize_title( (string) ( $document['slug'] ?? 'learn-doc' ) ),
			'title'           => (string) ( $document['title'] ?? __( 'Learn Documentation', 'tribe-ai-assistant' ) ),
			'article_url'     => $this->build_article_url( $learn_host, $document ),
			'excerpt_html'    => '',
			'content_html'    => '',
			'video_embed_url' => '',
			'video_url'       => '',
			'type'            => 'article',
			'section'         => (string) ( $document['section'] ?? '' ),
			'is_fallback'     => true,
		];
	}

	/**
	 * Build the canonical article URL.
	 *
	 * @param string $learn_host Learn host.
	 * @param array  $document   Document descriptor.
	 * @return string
	 */
	private function build_article_url( string $learn_host, array $document ): string {
		$path = (string) ( $document['path'] ?? '/' );
		return esc_url_raw( untrailingslashit( $learn_host ) . '/' . ltrim( $path, '/' ) );
	}

	/**
	 * Extract the table of contents from rendered content.
	 *
	 * @param string $html Raw rendered content.
	 * @param string $article_url Canonical article URL.
	 * @return string
	 */
	private function extract_table_of_contents( string $html, string $article_url ): string {
		if ( '' === trim( $html ) ) {
			return '';
		}

		if ( ! preg_match( '/(<ul[^>]*>.*?<\/ul>)/is', $html, $matches ) ) {
			return '';
		}

		$toc_html = $matches[1];
		$toc_html = preg_replace_callback(
			'/href=["\']#([^"\']+)["\']/i',
			static function ( array $href_matches ) use ( $article_url ): string {
				return 'href="' . esc_url( untrailingslashit( $article_url ) . '/#' . $href_matches[1] ) . '"';
			},
			$toc_html
		);

		$heading = '<h3>' . esc_html__( 'Table of Contents', 'tribe-ai-assistant' ) . '</h3>';

		return $this->sanitize_html_fragment( $heading . $toc_html );
	}

	/**
	 * Sanitize an HTML fragment for admin rendering.
	 *
	 * @param string $html Raw HTML.
	 * @return string
	 */
	private function sanitize_html_fragment( string $html ): string {
		if ( '' === trim( $html ) ) {
			return '';
		}

		$allowed = wp_kses_allowed_html( 'post' );
		unset( $allowed['iframe'] );

		return wp_kses( $html, $allowed );
	}

	/**
	 * Extract the first allowed iframe src from content.
	 *
	 * @param string $html Raw rendered content.
	 * @return string
	 */
	private function extract_allowed_iframe_src( string $html ): string {
		if ( ! preg_match_all( '/<iframe[^>]+src=["\']([^"\']+)["\']/i', $html, $matches ) ) {
			return '';
		}

		foreach ( $matches[1] as $src ) {
			$src = esc_url_raw( (string) $src );

			if ( '' === $src ) {
				continue;
			}

			$host = wp_parse_url( $src, PHP_URL_HOST );
			if ( is_string( $host ) && in_array( $host, $this->allowed_iframe_hosts, true ) ) {
				return $src;
			}
		}

		return '';
	}

	/**
	 * Build a transient key.
	 *
	 * @param string $learn_host Learn host.
	 * @param array  $document   Document descriptor.
	 * @return string
	 */
	private function get_cache_key( string $learn_host, array $document ): string {
		return self::CACHE_PREFIX . md5(
			$learn_host .
			'|' .
			(string) ( $document['rest_base'] ?? '' ) .
			'|' .
			(string) ( $document['slug'] ?? '' )
		);
	}
}
