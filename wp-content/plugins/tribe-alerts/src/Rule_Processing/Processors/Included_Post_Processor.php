<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing\Processors;

use Tribe\Alert\Meta\Alert_Meta;
use Tribe\Alert\Post_Fetcher\Post_Fetcher;
use Tribe\Alert\Rule_Processing\Processor;
class Included_Post_Processor implements Processor
{
    protected Post_Fetcher $post_fetcher;
    public function __construct(Post_Fetcher $post_fetcher)
    {
        $this->post_fetcher = $post_fetcher;
    }
    /**
     * Determine if the currently viewed post should display the Alert Banner.
     *
     * @param array $rules
     */
    public function process(array $rules) : bool
    {
        $included_posts = $rules[Alert_Meta::FIELD_RULES_INCLUDE_PAGES] ?? [];
        // No posts found, assume this page isn't included.
        if (!$included_posts) {
            return \false;
        }
        $post = $this->post_fetcher->get_post();
        foreach ($included_posts as $included_post) {
            if ($post->ID === $included_post->ID) {
                return \true;
            }
        }
        return \false;
    }
}
