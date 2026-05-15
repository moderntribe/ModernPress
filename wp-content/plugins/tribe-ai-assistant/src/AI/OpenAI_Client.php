<?php
/**
 * OpenAI Client.
 *
 * @package Tribe\AI
 */

namespace Tribe\AI\AI;

use OpenAI;

/**
 * Class OpenAI_Client
 */
class OpenAI_Client {
	/**
	 * OpenAI client instance.
	 *
	 * @var \OpenAI\Client
	 */
	private $client;

	/**
	 * Constructor.
	 *
	 * @param string $api_key OpenAI API key.
	 */
	public function __construct( string $api_key ) {
		$this->client = OpenAI::client( $api_key );
	}

	/**
	 * Generate page content with reasoning.
	 *
	 * @param string $prompt User prompt.
	 * @param array  $context Full 3-tier context from Context_Builder.
	 * @return array Array with 'blocks' and 'reasoning' keys.
	 * @throws \RuntimeException If generation fails.
	 */
	public function generate_page( string $prompt, array $context ): array {
		$system_prompt = $this->build_system_prompt( $context );

		try {
			$response = $this->client->chat()->create(
				[
					'model'           => 'gpt-4o-2024-08-06',
					'messages'        => [
						[
							'role'    => 'system',
							'content' => $system_prompt,
						],
						[
							'role'    => 'user',
							'content' => $prompt,
						],
					],
					'response_format' => [
						'type' => 'json_object',
					],
					'temperature'     => 0.7,
				]
			);

			// Validate response structure before accessing.
			if ( empty( $response->choices ) || ! isset( $response->choices[0]->message->content ) ) {
				throw new \RuntimeException( 'Invalid or empty response from OpenAI API' );
			}

			$content = $response->choices[0]->message->content;
			$data    = json_decode( $content, true );

			if ( ! isset( $data['blocks'] ) ) {
				throw new \RuntimeException( 'Invalid response format from OpenAI' );
			}

			// Clean up the block markup to ensure proper parsing.
			$blocks = $this->cleanup_block_markup( $data['blocks'] );

			return [
				'blocks'    => $blocks,
				'reasoning' => $data['reasoning'] ?? null,
			];

		} catch ( \Exception $e ) {
			error_log( 'OpenAI API Error: ' . $e->getMessage() );
			throw new \RuntimeException(
				__( 'Failed to generate content. Please try again.', 'tribe-ai-assistant' )
			);
		}
	}

	/**
	 * Answer a natural language question using provided documentation articles.
	 *
	 * @param string $question User's question.
	 * @param array  $articles Articles from Learn_Searcher: [{title, url, content}].
	 * @return array Array with 'answer' (string) and 'sources' ([{title, url}]) keys.
	 * @throws \RuntimeException If the API call fails.
	 */
	public function ask_question( string $question, array $articles ): array {
		$system_prompt  = "You are a helpful assistant answering questions about WordPress website building ";
		$system_prompt .= "using Modern Tribe tools and practices. ";
		$system_prompt .= "You are given documentation articles to help answer the question. ";
		$system_prompt .= "If the articles are relevant and contain a clear answer, use them. ";
		$system_prompt .= "If the articles are not relevant to the question, answer from your general WordPress and Modern Tribe knowledge instead. ";
		$system_prompt .= "Be concise and direct. ";
		$system_prompt .= "Return a JSON object with two keys: \"answer\" (string) and \"used_documentation\" (boolean, true if the provided articles were relevant and used).";

		$user_message = "Question: {$question}\n\nDocumentation:\n\n";
		foreach ( $articles as $i => $article ) {
			$n             = $i + 1;
			$user_message .= "--- Article {$n}: {$article['title']} ---\n";
			$user_message .= $article['content'] . "\n\n";
		}

		try {
			$response = $this->client->chat()->create(
				[
					'model'           => 'gpt-4o-2024-08-06',
					'messages'        => [
						[
							'role'    => 'system',
							'content' => $system_prompt,
						],
						[
							'role'    => 'user',
							'content' => $user_message,
						],
					],
					'response_format' => [ 'type' => 'json_object' ],
					'temperature'     => 0.3,
				]
			);

			if ( empty( $response->choices ) || ! isset( $response->choices[0]->message->content ) ) {
				throw new \RuntimeException( 'Invalid or empty response from OpenAI API' );
			}

			$data             = json_decode( $response->choices[0]->message->content, true );
			$answer           = (string) ( $data['answer'] ?? $response->choices[0]->message->content );
			$used_docs        = (bool) ( $data['used_documentation'] ?? true );

			$sources = $used_docs
				? array_map(
					static fn( array $a ) => [ 'title' => $a['title'], 'url' => $a['url'] ],
					$articles
				)
				: [];

			return [
				'answer'            => $answer,
				'sources'           => $sources,
				'used_documentation' => $used_docs,
			];

		} catch ( \Exception $e ) {
			error_log( 'OpenAI Q&A Error: ' . $e->getMessage() );
			throw new \RuntimeException(
				__( 'Failed to answer question. Please try again.', 'tribe-ai-assistant' )
			);
		}
	}

	/**
	 * Answer a question from AI general knowledge when no documentation is available.
	 *
	 * @param string $question User's question.
	 * @return array Array with 'answer' (string) key.
	 * @throws \RuntimeException If the API call fails.
	 */
	public function ask_from_knowledge( string $question ): array {
		$system_prompt  = "You are a helpful assistant answering questions about WordPress website building, ";
		$system_prompt .= "the Gutenberg block editor, and Modern Tribe plugins and practices. ";
		$system_prompt .= "Answer concisely and accurately from your training knowledge. ";
		$system_prompt .= "Return a JSON object with a single key: \"answer\" (string).";

		try {
			$response = $this->client->chat()->create(
				[
					'model'           => 'gpt-4o-2024-08-06',
					'messages'        => [
						[
							'role'    => 'system',
							'content' => $system_prompt,
						],
						[
							'role'    => 'user',
							'content' => $question,
						],
					],
					'response_format' => [ 'type' => 'json_object' ],
					'temperature'     => 0.3,
				]
			);

			if ( empty( $response->choices ) || ! isset( $response->choices[0]->message->content ) ) {
				throw new \RuntimeException( 'Invalid or empty response from OpenAI API' );
			}

			$data = json_decode( $response->choices[0]->message->content, true );

			return [
				'answer' => (string) ( $data['answer'] ?? $response->choices[0]->message->content ),
			];

		} catch ( \Exception $e ) {
			error_log( 'OpenAI knowledge fallback error: ' . $e->getMessage() );
			throw new \RuntimeException(
				__( 'Failed to answer question. Please try again.', 'tribe-ai-assistant' )
			);
		}
	}

	/**
	 * Clean up block markup from AI response.
	 *
	 * Fixes common issues with AI-generated block markup:
	 * - Double-escaped characters from JSON encoding
	 * - Missing/extra whitespace between blocks
	 *
	 * @param string $blocks Raw block markup from AI.
	 * @return string Cleaned block markup.
	 */
	private function cleanup_block_markup( string $blocks ): string {
		// Step 1: Fix escaped newlines (from JSON string encoding).
		// The AI returns \n in the JSON string, which after json_decode becomes literal \n.
		$blocks = str_replace( '\\n', "\n", $blocks );

		// Step 2: Fix escaped forward slashes (common in JSON).
		$blocks = str_replace( '\\/', '/', $blocks );

		// Step 3: Fix double-escaped quotes, but ONLY outside of block attribute JSON.
		// We need to be careful not to break valid JSON inside <!-- wp:block {"attr":"value"} -->
		// First, let's check if quotes are already properly formatted.
		if ( strpos( $blocks, '\\"' ) !== false && strpos( $blocks, '{"' ) === false ) {
			// Quotes are double-escaped and need fixing.
			$blocks = str_replace( '\\"', '"', $blocks );
		}

		// Step 4: Ensure proper spacing between blocks.
		// Fix cases where there's no newline between closing and opening blocks.
		$blocks = preg_replace(
			'/<!-- \/wp:([a-z0-9\-\/]+) --><!-- wp:/',
			"<!-- /wp:$1 -->\n\n<!-- wp:",
			$blocks
		);

		// Step 5: Ensure block comments start on their own lines.
		$blocks = preg_replace(
			'/([^>\s])<!-- wp:/',
			"$1\n<!-- wp:",
			$blocks
		);

		// Step 6: Fix any broken attribute JSON (common AI mistake: using single quotes).
		// Convert single quotes in block attributes to double quotes.
		$blocks = preg_replace_callback(
			'/<!-- wp:([a-z0-9\-\/]+) (\{[^}]+\}) -->/',
			function ( $matches ) {
				$block_name = $matches[1];
				$attrs_json = $matches[2];

				// Check if it's using single quotes (invalid JSON).
				if ( strpos( $attrs_json, "'" ) !== false && strpos( $attrs_json, '"' ) === false ) {
					$attrs_json = str_replace( "'", '"', $attrs_json );
				}

				// Validate JSON - if invalid, try to fix common issues.
				$decoded = json_decode( $attrs_json, true );
				if ( null === $decoded && '' !== $attrs_json && '{}' !== $attrs_json ) {
					// Try fixing unquoted keys.
					$fixed = preg_replace( '/(\{|,)\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*:/', '$1"$2":', $attrs_json );
					$decoded = json_decode( $fixed, true );
					if ( null !== $decoded ) {
						$attrs_json = $fixed;
					}
				}

				return "<!-- wp:{$block_name} {$attrs_json} -->";
			},
			$blocks
		);

		// Step 7: Trim whitespace.
		$blocks = trim( $blocks );

		// Step 8: Validate that we have at least one block comment.
		if ( strpos( $blocks, '<!-- wp:' ) === false ) {
			error_log( 'Tribe AI: Block markup missing WordPress block comments: ' . substr( $blocks, 0, 500 ) );
		}

		return $blocks;
	}

	/**
	 * Build system prompt with 3-tier context and reasoning instructions.
	 *
	 * @param array $context Full 3-tier context from Context_Builder.
	 * @return string System prompt.
	 */
	private function build_system_prompt( array $context ): string {
		$prompt = "You are an AI assistant creating WordPress page content using the Gutenberg block editor.\n\n";
		$prompt .= "CRITICAL RULES:\n";
		$prompt .= "1. ONLY use blocks listed in the AVAILABLE BLOCKS section\n";
		$prompt .= "2. For each block, ONLY use attributes defined in its schema\n";
		$prompt .= "3. For CUSTOM blocks (non-core/*): Copy the EXACT markup from EXAMPLES - do not modify the structure, attribute names, or JSON format\n";
		$prompt .= "4. For CORE blocks (core/*): Follow standard WordPress block format\n";
		$prompt .= "5. Prefer blocks with higher usage counts to match the site's established style\n";
		$prompt .= "6. Block attribute JSON MUST use double quotes, not single quotes\n\n";

		// Tier 1: Available Blocks.
		$prompt .= "=== AVAILABLE BLOCKS ===\n";
		$prompt .= $this->format_tier_1( $context['tier_1_registry'] ?? [] );

		// Tier 2: Usage Patterns.
		$prompt .= "\n\n=== SITE USAGE PATTERNS ===\n";
		$prompt .= $this->format_tier_2( $context['tier_2_patterns'] ?? [] );

		// Tier 3: Examples.
		$prompt .= "\n\n=== BLOCK EXAMPLES (use these as templates) ===\n";
		$prompt .= $this->format_tier_3( $context['tier_3_examples'] ?? [] );

		// Output format.
		$prompt .= "\n\n=== OUTPUT FORMAT (JSON) ===\n";
		$prompt .= "Return a JSON object with two keys: \"blocks\" and \"reasoning\".\n\n";
		$prompt .= "The \"blocks\" value MUST be a string containing valid WordPress block markup.\n";
		$prompt .= "Use \\n for newlines within the blocks string.\n\n";
		$prompt .= "BLOCK MARKUP FORMAT:\n";
		$prompt .= "<!-- wp:paragraph -->\\n<p>Your text here</p>\\n<!-- /wp:paragraph -->\n";
		$prompt .= "<!-- wp:heading {\"level\":2} -->\\n<h2>Your heading</h2>\\n<!-- /wp:heading -->\n\n";
		$prompt .= "Example JSON response:\n";
		$prompt .= "{\n";
		$prompt .= '  "blocks": "<!-- wp:heading {\\"level\\":2} -->\\n<h2 class=\\"wp-block-heading\\">Welcome</h2>\\n<!-- /wp:heading -->\\n\\n<!-- wp:paragraph -->\\n<p>Content here.</p>\\n<!-- /wp:paragraph -->",';
		$prompt .= "\n";
		$prompt .= '  "reasoning": {' . "\n";
		$prompt .= '    "prompt_interpretation": "What the user wants",' . "\n";
		$prompt .= '    "blocks_chosen": [{"block": "core/heading", "reason": "For the title", "usage_count": 100}],' . "\n";
		$prompt .= '    "structure_decisions": "Why this layout",' . "\n";
		$prompt .= '    "tone_matching": "How the style matches"' . "\n";
		$prompt .= '  }' . "\n";
		$prompt .= "}\n\n";

		$prompt .= "CRITICAL BLOCK MARKUP RULES:\n";
		$prompt .= "1. Each block MUST start with <!-- wp:blockname --> or <!-- wp:blockname {\"attrs\"} -->\n";
		$prompt .= "2. Each block MUST end with <!-- /wp:blockname -->\n";
		$prompt .= "3. Self-closing blocks use: <!-- wp:blockname /-->\n";
		$prompt .= "4. Separate blocks with \\n\\n (two newlines)\n";
		$prompt .= "5. Put content between the opening and closing comments\n";
		$prompt .= "6. Match the tone of the provided content samples\n";

		return $prompt;
	}

	/**
	 * Format Tier 1 (Registry) context for prompt.
	 *
	 * @param array $tier_1 Tier 1 registry data.
	 * @return string Formatted tier 1 context.
	 */
	private function format_tier_1( array $tier_1 ): string {
		if ( empty( $tier_1['blocks'] ) ) {
			return "No blocks available.\n";
		}

		$output = "Total Available: " . ( $tier_1['total_available'] ?? count( $tier_1['blocks'] ) ) . " blocks\n\n";

		// Group blocks by custom vs core.
		$custom_blocks = [];
		$core_blocks   = [];

		foreach ( $tier_1['blocks'] as $block_name => $block_data ) {
			if ( strpos( $block_name, 'core/' ) !== 0 ) {
				$custom_blocks[ $block_name ] = $block_data;
			} else {
				$core_blocks[ $block_name ] = $block_data;
			}
		}

		// List custom blocks first (high priority).
		if ( ! empty( $custom_blocks ) ) {
			$output .= "CUSTOM BLOCKS (prioritize these):\n";
			foreach ( $custom_blocks as $block_name => $block_data ) {
				$output .= $this->format_block_schema( $block_name, $block_data );
			}
			$output .= "\n";
		}

		// List top core blocks.
		$output .= "CORE BLOCKS:\n";
		$count = 0;
		foreach ( $core_blocks as $block_name => $block_data ) {
			$output .= $this->format_block_schema( $block_name, $block_data );
			$count++;
			if ( $count >= 30 ) {
				// Limit core blocks to save tokens.
				$output .= "... and " . ( count( $core_blocks ) - 30 ) . " more core blocks\n";
				break;
			}
		}

		return $output;
	}

	/**
	 * Format a single block schema.
	 *
	 * @param string $block_name Block name.
	 * @param array  $block_data Block data.
	 * @return string Formatted block schema.
	 */
	private function format_block_schema( string $block_name, array $block_data ): string {
		$output = "- {$block_name}";

		if ( ! empty( $block_data['title'] ) ) {
			$output .= " ({$block_data['title']})";
		}

		if ( ! empty( $block_data['attrs'] ) ) {
			$output .= "\n  Attributes: " . implode( ', ', $block_data['attrs'] );
		}

		if ( ! empty( $block_data['parent'] ) ) {
			$parent = is_array( $block_data['parent'] ) ? implode( ', ', $block_data['parent'] ) : $block_data['parent'];
			$output .= "\n  Parent: {$parent}";
		}

		if ( ! empty( $block_data['is_container'] ) ) {
			$output .= "\n  Supports inner blocks";
		}

		$output .= "\n";

		return $output;
	}

	/**
	 * Format Tier 2 (Patterns) context for prompt.
	 *
	 * @param array $tier_2 Tier 2 pattern data.
	 * @return string Formatted tier 2 context.
	 */
	private function format_tier_2( array $tier_2 ): string {
		$output = '';

		// Usage counts.
		if ( ! empty( $tier_2['usage_counts'] ) ) {
			$output .= "Most Used Blocks:\n";
			foreach ( $tier_2['usage_counts'] as $block_name => $count ) {
				$output .= "- {$block_name}: {$count} uses\n";
			}
			$output .= "\n";
		}

		// Custom blocks used.
		if ( ! empty( $tier_2['custom_blocks_used'] ) ) {
			$output .= "Custom Blocks in Use:\n";
			foreach ( $tier_2['custom_blocks_used'] as $block_name => $count ) {
				$output .= "- {$block_name}: {$count} uses\n";
			}
			$output .= "\n";
		}

		// Nesting patterns (top 10).
		if ( ! empty( $tier_2['nesting_patterns'] ) ) {
			$output .= "Common Nesting Patterns:\n";
			$count = 0;
			foreach ( $tier_2['nesting_patterns'] as $parent => $children ) {
				if ( empty( $children ) ) {
					continue;
				}
				arsort( $children );
				$top_child = array_key_first( $children );
				$output .= "- {$parent} commonly contains: {$top_child}\n";
				$count++;
				if ( $count >= 10 ) {
					break;
				}
			}
			$output .= "\n";
		}

		// Heading levels.
		if ( ! empty( $tier_2['heading_levels'] ) ) {
			$output .= "Heading Level Distribution: ";
			$levels = [];
			foreach ( $tier_2['heading_levels'] as $level => $count ) {
				$levels[] = "H{$level}:{$count}";
			}
			$output .= implode( ', ', $levels ) . "\n";
		}

		return $output;
	}

	/**
	 * Format Tier 3 (Examples) context for prompt.
	 *
	 * @param array $tier_3 Tier 3 example data.
	 * @return string Formatted tier 3 context.
	 */
	private function format_tier_3( array $tier_3 ): string {
		$output = '';

		// Custom block examples (highest priority).
		if ( ! empty( $tier_3['custom_blocks'] ) ) {
			$output .= "=== CUSTOM BLOCK EXAMPLES ===\n";
			$output .= "CRITICAL: For custom blocks (non-core/*), you MUST copy the EXACT markup structure shown below.\n";
			$output .= "Do NOT modify attribute names, JSON structure, or HTML format. Copy verbatim and only change content values.\n\n";

			foreach ( $tier_3['custom_blocks'] as $block_name => $examples ) {
				$output .= "--- {$block_name} ---\n";
				$output .= "Copy this exact format:\n";
				foreach ( $examples as $index => $example ) {
					if ( ! empty( $example['markup'] ) ) {
						$output .= "```\n" . $example['markup'] . "\n```\n";
					}
				}
				$output .= "\n";
			}
		}

		// Core block examples (limited to save tokens).
		if ( ! empty( $tier_3['core_blocks'] ) ) {
			$output .= "CORE BLOCK EXAMPLES:\n\n";
			$count = 0;
			foreach ( $tier_3['core_blocks'] as $block_name => $examples ) {
				if ( $count >= 10 ) {
					break;
				}
				$output .= "{$block_name}:\n";
				// Only show first example to save tokens.
				if ( ! empty( $examples[0]['markup'] ) ) {
					$output .= $examples[0]['markup'] . "\n";
				}
				$output .= "\n";
				$count++;
			}
		}

		// Tone samples.
		if ( ! empty( $tier_3['tone_samples'] ) ) {
			$output .= "CONTENT TONE SAMPLES (match this style):\n";
			foreach ( array_slice( $tier_3['tone_samples'], 0, 3 ) as $sample ) {
				$output .= "- {$sample}\n";
			}
		}

		return $output;
	}
}
