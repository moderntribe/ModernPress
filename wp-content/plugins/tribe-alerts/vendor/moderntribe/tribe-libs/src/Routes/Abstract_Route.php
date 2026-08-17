<?php

declare (strict_types=1);
/**
 * Route is the base class for Routes. At the least a Route must declare,
 *
 * 1. Name
 * 2. Pattern
 * 3. Matches
 * 4. Template
 *
 * It can also hook into the route's lifecyle to alter the WP & WP_Query
 * objects by overriding parse and before methods.
 *
 * Usage: To create a new route /things/<thing>/ at ThingRoute,
 *
 * class ThingRoute extends Route {
 *
 *     public function get_name() {
 *         return 'things';
 *     }
 *
 *     public function get_pattern() {
 *         return 'things/([^/]+)/?&';
 *     }
 *
 *     public function get_matches() {
 *         return [ 'thing' => '$matches[1]' ];
 *     }
 *
 *     public function get_template() {
 *         return 'thing.php';
 *     }
 *
 * }
 *
 * @package Tribe\Project\Routes
 */
namespace Tribe\Alert_Scoped\Tribe\Libs\Routes;

/**
 * Base class for routes.
 */
abstract class Abstract_Route
{
    /**
     * Required - The Route's name. This is useful to refer to the route
     * by name. Eg:- \MTTrial\is_route( 'foo' );
     *
     * @return string The name for the route.
     */
    public function get_name() : string
    {
        return 'base_route';
    }
    /**
     * Required - The Route's regex pattern.
     *
     * @return string The pattern for the route.
     */
    public function get_pattern() : string
    {
        return '^$';
    }
    /**
     * Returns the priority of the rewrite rule.
     *
     * @return string
     */
    public function get_priority() : string
    {
        return 'top';
    }
    /**
     * Multi-pattern routes can use this to declare their patterns.
     *
     * @return array Patterns for the route.
     */
    public function get_patterns() : array
    {
        return [$this->get_pattern()];
    }
    /**
     * The Route's matches corresponding to the regex pattern.
     *
     * Eg:- Given the pattern /things/(a|b)/(c|d)/$,
     *
     * The corresponding matches array will be,
     *
     * [ 'first' => '$matches[1]', 'second' => '$matches[2]' ].
     *
     * Additional static matches can also be included, For example
     *
     * [ 'static' => 'foo', 'first' => '$matches[1]', 'second' => '$matches[2]' ].
     *
     * @return array Matches for the route.
     */
    public function get_matches() : array
    {
        return ['base_route' => \true];
    }
    /**
     * The Route's template file name. This resolves to the template in
     * the current theme's directory.
     *
     * @return string The template to use for the route.
     */
    public function get_template() : string
    {
        return 'index.php';
    }
    /**
     * Returns a bool based on whether this is a WP-Admin only route.
     *
     * @return bool True if the route is an admin only route, false otherwise.
     */
    public function is_admin() : bool
    {
        return \false;
    }
    /**
     * Returns a bool based on whether this is a public route.
     *
     * @return bool
     */
    public function is_public() : bool
    {
        return !$this->is_admin();
    }
    /**
     * Routes that use patterns that shadow the WordPress Core Pattern
     * should set this to true. This prevents registration of the Core
     * WordPress pattern.
     *
     * @return bool
     */
    public function is_core() : bool
    {
        return \false;
    }
    /**
     * Optional - Title for this Route.
     *
     * @return string The title for this route.
     */
    public function get_title() : string
    {
        return \ucwords(\str_replace('_', ' ', $this->get_name()));
    }
    /**
     * Optional - Body class for this Route. Defaults to slug form of
     * name.
     *
     * @return string Returns body classes for this route.
     */
    public function get_body_class() : string
    {
        return \sanitize_title_with_dashes($this->get_title());
    }
    /**
     * Returns the query var names for this route. The query vars are
     * computed from the matches array by default.
     *
     * Eg:- For the matches array,
     *
     * [ 'first' => '$matches[1]', 'second' => '$matches[2]' ].
     *
     * will return [ 'first', 'second' ]
     *
     * This vars are added to the WP query vars array.
     *
     * @return array Query variable names.
     */
    public function get_query_var_names() : array
    {
        return \array_keys($this->get_matches());
    }
    /**
     * Returns an associative array of the current routes query vars
     * values.
     *
     * Given the matches,
     *
     * [ 'first' => '$matches[1]', 'second' => '$matches[2]' ].
     *
     * and the URL: /things/a/b/, the query vars will be,
     *
     * [
     *     'first' => 'a', 'second' => 'b',
     * ]
     *
     * @return array
     */
    public function get_query_vars() : array
    {
        $query_vars = $this->get_query_var_names();
        $values = [];
        foreach ($query_vars as $query_var) {
            $values[$query_var] = \get_query_var($query_var);
        }
        return $values;
    }
    /**
     * The request methods that are authorized on this route. Only GET
     * is authorized by default.
     *
     * @return array Accepted request methods for the route.
     */
    public function get_request_methods() : array
    {
        return [\WP_REST_Server::READABLE];
    }
    /**
     * Authorization hook. Custom authorization can be done here.
     *
     * By default only checks if request method is authorized.
     *
     * @return void
     */
    public function authorize() : void
    {
        $this->authorize_request_method();
    }
    /**
     * Override to change the $wp global object.
     *
     * @param \WP $wp The WP object.
     * @return void
     */
    public function parse(\WP $wp) : void
    {
        return;
    }
    /**
     * Override to change the wp_query. This is equivalent to using 'pre_get_posts'.
     *
     * @param \WP_Query $wp_query The WP query object.
     * @return void
     */
    public function before(\WP_Query $wp_query) : void
    {
        return;
    }
    /**
     * Has the template included been fired.
     *
     * @param string $template The template name.
     * @return string          The modified template path.
     */
    public function did_template_include($template) : string
    {
        $template_path = $this->get_template();
        // Bail early if no template path.
        if (empty($template_path)) {
            return $template;
        }
        global $wp_query;
        // Bail early if this is the 404 template.
        if (\false !== \strpos($template_path, '404')) {
            $wp_query->is_404 = \true;
            $protocol = $_SERVER['SERVER_PROTOCOL'] ?? '';
            \header("{$protocol} 404 Not Found", \true, 404);
        }
        // Use the theme path if the passed in path doesn't exist.
        if (!\file_exists($template_path)) {
            $template_path = \get_template_directory() . '/' . $template_path;
        }
        // Bail ealy if the template file exists.
        if (\file_exists($template_path)) {
            $wp_query->is_home = \false;
            $this->before($wp_query);
            return $template_path;
        }
        return $template;
    }
    /**
     * Get document title.
     *
     * @param string $title The current document title.
     * @return string       The modified document title.
     */
    public function did_pre_get_document_title($title) : string
    {
        $route_title = $this->get_title();
        if (!empty($route_title)) {
            $title = $route_title;
        }
        return $title;
    }
    /**
     * Determines if the current request method is valid for a given route.
     *
     * @return bool True if the request method is valid, false otherwise.
     */
    public function is_valid_request_method() : bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? \false;
        return \in_array($method, $this->get_request_methods(), \true);
    }
    /**
     * Determine if the request is valid.
     *
     * @return bool|void True if the request method is valid, throws a 403 forbidden otherwise.
     */
    public function authorize_request_method()
    {
        $valid = $this->is_valid_request_method();
        // Bail early if this is the PHPUnit runner.
        if (\defined('PHPUNIT_RUNNER')) {
            return $valid;
        }
        // Bail early if the method is valid.
        if ($valid) {
            return $valid;
        }
        \wp_die('Not Authorized', 403);
    }
}
