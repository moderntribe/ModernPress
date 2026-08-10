<?php

declare (strict_types=1);
/**
 * Manages rewrite rules.
 *
 * @package Tribe\Project\Routes
 */
namespace Tribe\Alert_Scoped\Tribe\Libs\Routes;

use Tribe\Alert_Scoped\Tribe\Libs\Routes\Abstract_Route;
/**
 * Class to manage rewrite rules.
 */
class Router_Rule_Manager
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
    public $routes = [];
    /**
     * Query parameters for the router.
     *
     * @var array
     */
    public $router_vars = [];
    /**
     * Register REST API routes.
     *
     * @return void
     */
    public function init_rest_routes(array $rest_routes = []) : void
    {
        // Register all REST routes defined.
        foreach ($rest_routes as $route) {
            $route->register();
        }
    }
    /**
     * Merges the WordPress Rewrite Rules with the CS Rules.
     *
     * @hook rewrite_rules_array hook
     *
     * @param array $wp_rules          The WP rules array.
     * @param array $registered_routes Routes registered.
     * @return array                   The modified rewrite rules array.
     */
    public function load($wp_rules = [], array $registered_routes = []) : array
    {
        $rules = $this->get_rules($registered_routes);
        // Loop through rules to determine where to add the rule.
        foreach ($rules as $pattern => $rule) {
            $wp_rules = 'top' === $rule['priority'] ? [$pattern => $rule['redirect']] + $wp_rules : $wp_rules + [$pattern => $rule['redirect']];
        }
        return $wp_rules;
    }
    /**
     * Converts Route instances into Rewrite rules for adding to
     * the WordPress rewrites
     *
     * @param array $registered_routes Routes registered.
     * @return array Rules for the route instances.
     */
    public function get_rules(array $registered_routes) : array
    {
        $rules = [];
        foreach ($this->get_route_objects($registered_routes) as $pattern => $route) {
            // Skip routes that are defined in Core.
            if ($route->is_core()) {
                continue;
            }
            $matches = $route->get_matches();
            $redirect = 'index.php';
            if (!empty($matches)) {
                $redirect .= '?' . $this->get_redirect_params($matches);
            }
            $rules[$pattern] = ['redirect' => $redirect, 'priority' => $route->get_priority()];
        }
        return $rules;
    }
    /**
     * Converts query params array into a query string. Special $matches
     * values are not URL encoded.
     *
     * @param array $params The query params.
     * @return string       Query string.
     */
    public function get_redirect_params(array $params = []) : string
    {
        $query_params = [];
        foreach ($params as $key => $value) {
            if (\false === \strpos($value, '$matches')) {
                $query_params[] = \urlencode($key) . '=' . \urlencode($value);
            } else {
                $query_params[] = \urlencode($key) . '=' . $value;
            }
        }
        return \implode('&', $query_params);
    }
    /**
     * Stores the route instances locally in an associative array on the
     * regex pattern. Any custom query vars are also scanned and stored
     * here.
     *
     * @param array $registered_routes Routes registered.
     * @return array Route instances.
     */
    public function get_route_objects(array $registered_routes = []) : array
    {
        $this->init_routes($registered_routes);
        return $this->routes;
    }
    /**
     * Lazy init routes and route vars.
     *
     * @param array $registered_routes Routes registered.
     * @return void
     */
    public function init_routes(array $registered_routes = []) : void
    {
        // Bail early if routes are already defined.
        if (!empty($this->routes)) {
            return;
        }
        // Register any routes defined.
        foreach ($registered_routes as $route) {
            $patterns = $route->get_patterns();
            $route_vars = $route->get_query_var_names();
            // Add patterns to routes array.
            foreach ($patterns as $pattern) {
                $this->routes[$pattern] = $route;
            }
        }
    }
    /**
     * Adds the custom query vars.
     *
     * @hook query_vars
     *
     * @param array $query_vars WordPress query vars.
     * @return array            Modified query vars.
     */
    public function did_query_vars($query_vars, array $registered_routes) : array
    {
        // Register any route variables defined.
        foreach ($registered_routes as $route) {
            $this->router_vars = \array_merge($this->router_vars, $route->get_query_var_names());
        }
        $this->router_vars = \array_unique($this->router_vars);
        return \array_merge($query_vars, $this->router_vars);
    }
}
