<?php

declare (strict_types=1);
namespace Tribe\Alert\Post_Fetcher\Strategies;

use Tribe\Alert\Post_Fetcher\Post_Fetcher;
use WP_Post;
class Home_Page_Fetcher implements Post_Fetcher
{
    /**
     * Returns the post assigned to "Homepage"
     * in Settings > Reading.
     */
    public function get_post() : ?WP_Post
    {
        return \get_post(\get_option('page_on_front'));
    }
}
