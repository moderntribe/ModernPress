<?php

declare (strict_types=1);
namespace Tribe\Alert\Post_Fetcher\Strategies;

use Tribe\Alert\Post_Fetcher\Post_Fetcher;
use WP_Post;
class Posts_Page_Fetcher implements Post_Fetcher
{
    /**
     * Returns the post assigned to the "Posts Page" in
     * Settings > Reading.
     */
    public function get_post() : ?WP_Post
    {
        global $wp_query;
        return $wp_query->get_queried_object();
    }
}
