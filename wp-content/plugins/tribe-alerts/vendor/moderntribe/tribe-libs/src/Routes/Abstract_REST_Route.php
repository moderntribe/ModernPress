<?php

declare (strict_types=1);
/**
 * Base class for REST API endpoints.
 *
 * @package Tribe\Project\Routes
 */
namespace Tribe\Alert_Scoped\Tribe\Libs\Routes;

/**
 * Base class for REST API endpoints to extend.
 */
abstract class Abstract_REST_Route
{
    /**
     * Registers the route with WP lifecycle hooks.
     *
     * @return void
     */
    public abstract function register() : void;
    /**
     * Returns the project namespace.
     *
     * @return string The project's namespace.
     */
    public function get_project_namespace() : string
    {
        return 'tribe/v1';
    }
}
