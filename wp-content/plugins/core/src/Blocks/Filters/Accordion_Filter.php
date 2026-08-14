<?php declare(strict_types=1);

namespace Tribe\Plugin\Blocks\Filters;

use Tribe\Plugin\Blocks\Filters\Contracts\Block_Content_Filter;
use WP_HTML_Processor;

/**
 * Appends FAQPage structured data to accordions that opt in via the
 * "Enable FAQ Schema" toggle (see blocks/core/accordion/editor.js).
 */
class Accordion_Filter extends Block_Content_Filter {

	public const string BLOCK = 'core/accordion';

	private const QUESTION_CLASS = 'wp-block-accordion-heading__toggle-title';

	private const ANSWER_CLASS = 'wp-block-accordion-panel';

	/**
	 * @param array<string, mixed> $parsed_block
	 */
	public function filter_block_content( string $block_content, array $parsed_block, object $block ): string {
		if ( ! $block_content || ! wp_validate_boolean( $parsed_block['attrs']['enableFaqSchema'] ?? false ) ) {
			return $block_content;
		}

		$entities = $this->extract_entities( $block_content );

		if ( ! $entities ) {
			return $block_content;
		}

		// JSON_HEX_TAG so answer text containing `</script>` cannot break out of the tag.
		$json = wp_json_encode( [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => $entities,
		], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG );

		if ( false === $json ) {
			return $block_content;
		}

		return $block_content . wp_get_inline_script_tag( $json, [ 'type' => 'application/ld+json' ] );
	}

	/**
	 * Walks the rendered accordion once, pairing each heading title with the panel that follows it.
	 *
	 * A nested accordion's headings/panels are consumed as part of the outer answer text, and the
	 * nested block emits its own schema if it also opted in.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function extract_entities( string $block_content ): array {
		$processor = WP_HTML_Processor::create_fragment( $block_content );

		if ( ! $processor ) {
			return [];
		}

		$entities = [];
		$question = '';

		while ( $processor->next_tag() ) {
			if ( $processor->has_class( self::QUESTION_CLASS ) ) {
				$question = $this->inner_text( $processor );

				continue;
			}

			if ( ! $question || ! $processor->has_class( self::ANSWER_CLASS ) ) {
				continue;
			}

			$answer = $this->inner_text( $processor );

			if ( $answer ) {
				$entities[] = [
					'@type'          => 'Question',
					'name'           => $question,
					'acceptedAnswer' => [
						'@type' => 'Answer',
						'text'  => $answer,
					],
				];
			}

			$question = '';
		}

		return $entities;
	}

	/**
	 * Collapsed text content of the element the processor is currently on, consuming it.
	 *
	 * ponytail: text only — schema.org accepts HTML in acceptedAnswer.text, but the HTML API
	 * has no inner-HTML accessor, so links/lists in answers flatten to their text. Swap in
	 * DOMDocument + wp_kses if a rich result ever needs the markup.
	 */
	private function inner_text( WP_HTML_Processor $processor ): string {
		$depth = $processor->get_current_depth();
		$text  = '';

		// A child's closer returns to $depth; only this element's closer drops below it.
		while ( $processor->next_token() && $processor->get_current_depth() >= $depth ) {
			if ( '#text' !== $processor->get_token_type() ) {
				continue;
			}

			$text .= ' ' . $processor->get_modifiable_text();
		}

		// Cast covers preg_replace returning null on invalid UTF-8: the pair is dropped, not mangled.
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}

}
