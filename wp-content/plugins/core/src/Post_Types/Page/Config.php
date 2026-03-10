<?php declare(strict_types=1);

namespace Tribe\Plugin\Post_Types\Page;

class Config {

	protected string $post_type = Page::NAME;

	public function filter_single_post_query_block( array $query ): array {
		if ( ! is_single() || 'post' !== $query['post_type'] ) {
			return $query;
		}

		$query['post__not_in'] = [ get_the_ID() ];

		return $query;
	}

}
