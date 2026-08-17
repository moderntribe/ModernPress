<?php

declare (strict_types=1);
namespace Tribe\Alert_Scoped\Tribe\Libs\Required_Page;

use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
class Required_Page_Subscriber extends Abstract_Subscriber
{
    public function register() : void
    {
        $this->required_pages();
    }
    private function required_pages() : void
    {
        \add_action('admin_init', function () {
            foreach ($this->container->get(Required_Page_Definer::PAGES) as $page) {
                $page->ensure_page_exists();
            }
        }, 10, 0);
        \add_action('trashed_post', function ($post_id) {
            foreach ($this->container->get(Required_Page_Definer::PAGES) as $page) {
                $page->clear_option_on_delete($post_id);
            }
        }, 10, 1);
        \add_action('deleted_post', function ($post_id) {
            foreach ($this->container->get(Required_Page_Definer::PAGES) as $page) {
                $page->clear_option_on_delete($post_id);
            }
        }, 10, 1);
        \add_action('acf/init', function () {
            foreach ($this->container->get(Required_Page_Definer::PAGES) as $page) {
                $page->register_setting();
            }
        }, 10, 0);
        \add_filter('display_post_states', function ($post_states, $post) {
            foreach ($this->container->get(Required_Page_Definer::PAGES) as $page) {
                $post_states = $page->indicate_post_state($post_states, $post);
            }
            return $post_states;
        }, 10, 2);
    }
}
