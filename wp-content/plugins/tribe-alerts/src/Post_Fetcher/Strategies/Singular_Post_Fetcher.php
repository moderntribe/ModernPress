<?php

declare (strict_types=1);
namespace Tribe\Alert\Post_Fetcher\Strategies;

use Tribe\Alert\Post_Fetcher\Post_Fetcher;
use WP_Post;
class Singular_Post_Fetcher implements Post_Fetcher
{
    public function get_post() : ?WP_Post
    {
        if (!\is_singular()) {
            return null;
        }
        return \get_post();
    }
}
