<?php

declare (strict_types=1);
namespace Tribe\Alert\Post_Fetcher;

use WP_Post;
interface Post_Fetcher
{
    /**
     * Fetch the current post, utilizing different strategies.
     */
    public function get_post() : ?WP_Post;
}
