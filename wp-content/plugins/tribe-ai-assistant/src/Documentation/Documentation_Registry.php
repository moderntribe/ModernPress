<?php
/**
 * Documentation Registry.
 *
 * @package Tribe\AI
 */

declare( strict_types=1 );

namespace Tribe\AI\Documentation;

/**
 * Class Documentation_Registry
 */
class Documentation_Registry {
	/**
	 * Learn content fetcher.
	 *
	 * @var Learn_Content_Fetcher
	 */
	private Learn_Content_Fetcher $fetcher;

	/**
	 * Loaded documentation map.
	 *
	 * @var array<string,mixed>|null
	 */
	private ?array $config = null;

	/**
	 * Constructor.
	 *
	 * @param Learn_Content_Fetcher|null $fetcher Fetcher instance.
	 */
	public function __construct( ?Learn_Content_Fetcher $fetcher = null ) {
		$this->fetcher = $fetcher ?? new Learn_Content_Fetcher();
	}

	/**
	 * Get documentation for a block.
	 *
	 * @param string      $block_name Block name.
	 * @param string|null $post_type Optional post type.
	 * @return array<string,mixed>
	 */
	public function get_block_documentation( string $block_name, ?string $post_type = null ): array {
		$config       = $this->get_config();
		$learn_host   = untrailingslashit( (string) ( $config['learn_host'] ?? 'https://learn.tri.be' ) );
		$block_name   = trim( $block_name );
		$document     = $config['blocks'][ $block_name ] ?? null;
		$match_type   = 'exact';
		$article_data = null;

		if ( null === $document ) {
			$document   = $this->find_fallback_entry( $block_name, $post_type );
			$match_type = null === $document ? 'none' : 'fallback';
		}

		if ( null === $document ) {
			return [
				'block'        => [
					'name'       => $block_name,
					'summary'    => '',
					'match_type' => 'none',
				],
				'items'        => [],
				'source'       => 'learn',
				'last_updated' => (string) ( $config['last_updated'] ?? '' ),
			];
		}

		$article_data = $this->fetcher->fetch_document( $learn_host, $document );

		return [
			'block'        => [
				'name'       => $block_name,
				'summary'    => wp_strip_all_tags( (string) ( $article_data['excerpt_html'] ?? '' ) ),
				'match_type' => $match_type,
			],
			'items'        => [ $article_data ],
			'source'       => 'learn',
			'last_updated' => (string) ( $config['last_updated'] ?? '' ),
		];
	}

	/**
	 * Load config once.
	 *
	 * @return array<string,mixed>
	 */
	private function get_config(): array {
		if ( null !== $this->config ) {
			return $this->config;
		}

		$config_path = TRIBE_AI_DIR . 'config/documentation-map.php';

		if ( ! file_exists( $config_path ) ) {
			$this->config = [
				'last_updated' => '',
				'learn_host'   => 'https://learn.tri.be',
				'blocks'       => [],
				'fallbacks'    => [],
			];

			return $this->config;
		}

		$config = include $config_path;

		$this->config = is_array( $config ) ? $config : [
			'last_updated' => '',
			'learn_host'   => 'https://learn.tri.be',
			'blocks'       => [],
			'fallbacks'    => [],
		];

		return $this->config;
	}

	/**
	 * Find a configured fallback entry.
	 *
	 * @param string      $block_name Block name.
	 * @param string|null $post_type Optional post type.
	 * @return array<string,mixed>|null
	 */
	private function find_fallback_entry( string $block_name, ?string $post_type = null ): ?array {
		$config = $this->get_config();

		foreach ( $config['fallbacks'] ?? [] as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			if ( ! $this->matches_rule( $entry['match'] ?? [], $block_name ) ) {
				continue;
			}

			$doc = $entry['doc'] ?? null;
			if ( ! is_array( $doc ) ) {
				continue;
			}

			return $doc;
		}

		return null;
	}

	/**
	 * Determine if a block matches a fallback rule.
	 *
	 * @param array<string,mixed> $match Match rule config.
	 * @param string              $block_name Block name.
	 * @return bool
	 */
	private function matches_rule( array $match, string $block_name ): bool {
		$block_names = $match['block_names'] ?? [];
		$prefixes    = $match['prefixes'] ?? [];

		if ( is_array( $block_names ) && in_array( $block_name, $block_names, true ) ) {
			return true;
		}

		if ( is_array( $prefixes ) ) {
			foreach ( $prefixes as $prefix ) {
				if ( is_string( $prefix ) && str_starts_with( $block_name, $prefix ) ) {
					return true;
				}
			}
		}

		return false;
	}

}
