<?php declare(strict_types=1);

namespace Tribe\Plugin\Post_Types\Page;

use Tribe\Plugin\Core\Abstract_Subscriber;

class Page_Subscriber extends Abstract_Subscriber {

	public function register(): void {
		$this->query_loop_block_filter();
	}

	public function query_loop_block_filter(): void {
		add_filter( 'query_loop_block_query_vars', function ( array $query ): array {
			return $this->container->get( Config::class )->filter_single_post_query_block( $query );
		}, 10, 1 );
	}

}
