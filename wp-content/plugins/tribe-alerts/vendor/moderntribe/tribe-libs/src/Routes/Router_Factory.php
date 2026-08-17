<?php

declare (strict_types=1);
/**
 * Class that is responsible for registering routes.
 *
 * @package Tribe\Project\Routes
 */
namespace Tribe\Alert_Scoped\Tribe\Libs\Routes;

use Tribe\Alert_Scoped\Tribe\Libs\Routes\Abstract_Route;
/**
 * Class to register routers for normal and REST API endpoints.
 */
class Router_Factory
{
    /**
     * Currently matched route.
     *
     * @var Abstract_Route|string
     */
    public $matched_route;
    /**
     * List of Route Instances.
     *
     * @var array
     */
    public $routes;
    /**
     * List of router vars - tied to query vars.
     *
     * @var array
     */
    public $router_vars;
    /**
     * Instance of the rewrite rule manager.
     *
     * @var Router_Rule_Manager
     */
    public $manager = null;
    /**
     * Class constructor.
     *
     * @return void
     */
    public function __construct(Router_Rule_Manager $manager)
    {
        $this->manager = $manager;
    }
    /**
     * Looks up the custom route for the pattern matched. Returns false
     * if not found.
     *
     * @param string $pattern      The regex pattern to lookup.
     * @param array $registered_routes Routes registered.
     * @return Abstract_Route|null The route or null on failure.
     */
    public function find_route($pattern, $registered_routes) : ?Abstract_Route
    {
        // Load routes if not already loaded.
        if (empty($this->routes)) {
            $this->routes = $this->manager->get_route_objects($registered_routes);
        }
        // Bail early if the route pattern doesn't exist.
        if (empty($this->routes[$pattern])) {
            return null;
        }
        return $this->routes[$pattern];
    }
    /**
     * Checks if the current route is a custom route and activates it if matched.
     *
     * @hook parse_request
     *
     * @param \WP $wp The global wp object.
     * @param array $registered_routes Routes registered.
     * @return Abstract_Route|null The matched route on success, null on failure.
     */
    public function did_parse_request(\WP $wp, $registered_routes) : ?Abstract_Route
    {
        $pattern = $wp->matched_rule;
        $matched_route = $this->find_route($pattern, $registered_routes);
        // Bail early if no matched route.
        if (empty($matched_route)) {
            return null;
        }
        return $matched_route;
    }
}
