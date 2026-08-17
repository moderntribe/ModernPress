<?php

declare (strict_types=1);
namespace Tribe\Alert\Post_Fetcher;

use Tribe\Alert_Scoped\DI\FactoryInterface;
use Tribe\Alert\Post_Fetcher\Strategies\Home_Page_Fetcher;
use Tribe\Alert\Post_Fetcher\Strategies\Posts_Page_Fetcher;
use Tribe\Alert\Post_Fetcher\Strategies\Singular_Post_Fetcher;
class Post_Fetcher_Factory
{
    protected FactoryInterface $container;
    public function __construct(FactoryInterface $container)
    {
        $this->container = $container;
    }
    /**
     * Create the correct post fetcher strategy based on the current page a user
     * is viewing.
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function make() : \Tribe\Alert\Post_Fetcher\Post_Fetcher
    {
        global $wp_query;
        // Viewing the Posts Page as set in Settings > Reading.
        if ($wp_query->is_posts_page) {
            return $this->container->make(Posts_Page_Fetcher::class);
        }
        // Viewing the home page if set in Settings > Reading.
        if ($this->is_home()) {
            return $this->container->make(Home_Page_Fetcher::class);
        }
        // All other singular posts.
        return $this->container->make(Singular_Post_Fetcher::class);
    }
    protected function is_home() : bool
    {
        return \is_home() && 'page' === \get_option('show_on_front') && \get_option('page_on_front');
    }
}
