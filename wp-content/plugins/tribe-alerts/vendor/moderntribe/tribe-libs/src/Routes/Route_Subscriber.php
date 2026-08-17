<?php

declare (strict_types=1);
/**
 * Route subscriber class.
 *
 * @package Tribe\Project\Routes
 */
namespace Tribe\Alert_Scoped\Tribe\Libs\Routes;

use Tribe\Alert_Scoped\Tribe\Libs\Container\Abstract_Subscriber;
use Tribe\Alert_Scoped\Tribe\Libs\Routes\Router_Factory;
/**
 * Class to subscribe routes to the WP lifecycle hooks.
 */
class Route_Subscriber extends Abstract_Subscriber
{
    /**
     * Subscribes router to WP lifecycle hooks.
     *
     * @return void
     */
    public function register() : void
    {
        \add_action('wp_loaded', function () {
            $this->container->get(Cache_Manager::class)->flush_if_changed();
        }, 999);
        \add_filter('rewrite_rules_array', function (array $rules) : array {
            return $this->container->get(Router_Rule_Manager::class)->load($rules, $this->container->get(Route_Definer::ROUTES));
        }, 999);
        \add_action('rest_api_init', function () {
            $this->container->get(Router_Rule_Manager::class)->init_rest_routes($this->container->get(Route_Definer::REST_ROUTES));
        });
        \add_filter('query_vars', function ($vars) {
            return $this->container->get(Router_Rule_Manager::class)->did_query_vars($vars, $this->container->get(Route_Definer::ROUTES));
        });
        \add_action('parse_request', function (\WP $wp) {
            $matched_route = $this->container->get(Router_Factory::class)->did_parse_request($wp, $this->container->get(Route_Definer::ROUTES));
            // Bail early if no matched route.
            if (!$matched_route instanceof Abstract_Route) {
                return;
            }
            // Avoid a canonical redirect since we're using a route.
            \add_filter('redirect_canonical', '__return_false');
            \add_filter('template_include', [$matched_route, 'did_template_include']);
            \add_filter('pre_get_document_title', [$matched_route, 'did_pre_get_document_title']);
            $matched_route->authorize();
            $matched_route->parse($wp);
        });
    }
}
