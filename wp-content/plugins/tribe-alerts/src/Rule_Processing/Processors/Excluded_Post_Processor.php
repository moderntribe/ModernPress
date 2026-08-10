<?php

declare (strict_types=1);
namespace Tribe\Alert\Rule_Processing\Processors;

use Tribe\Alert\Meta\Alert_Meta;
use Tribe\Alert\Post_Fetcher\Post_Fetcher;
use Tribe\Alert\Rule_Processing\Processor;
class Excluded_Post_Processor implements Processor
{
    protected Post_Fetcher $post_fetcher;
    public function __construct(Post_Fetcher $post_fetcher)
    {
        $this->post_fetcher = $post_fetcher;
    }
    /**
     * Determine if the currently viewed post is excluded from displaying the
     * Alert Banner.
     *
     * @param array $rules
     */
    public function process(array $rules) : bool
    {
        $excluded_posts = $rules[Alert_Meta::FIELD_RULES_EXCLUDE_PAGES] ?? [];
        if (!$excluded_posts) {
            return \false;
        }
        $post = $this->post_fetcher->get_post();
        foreach ($excluded_posts as $excluded_post) {
            if ($post->ID === $excluded_post->ID) {
                return \true;
            }
        }
        return \false;
    }
}
