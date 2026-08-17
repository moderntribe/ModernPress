<?php

declare (strict_types=1);
/**
 * Cache class for routes.
 *
 * @package Tribe\Project\Routes
 */
namespace Tribe\Alert_Scoped\Tribe\Libs\Routes;

/**
 * Class to manage route caching.
 */
class Cache_Manager
{
    /**
     * The current router version. This should be bumped whenever
     * changes are made to this file.
     *
     * @return string The current version of routes.
     */
    public function get_version() : string
    {
        return \apply_filters('tribe_libs_router_version', '1.0.0');
    }
    /**
     * Conditionally (soft) flushes rewrite rules. Ignored silently
     * if the saved version in the DB is also the version in code.
     *
     * @return void
     */
    public function flush_if_changed() : void
    {
        // Bail early if rules haven't changed.
        if ($this->get_version() === \get_option('lib_router_version')) {
            return;
        }
        $this->flush();
    }
    /**
     * Wrapper to the WordPress's rewrite flushing API. Triggers the
     * router_changed action on flush.
     *
     * @return void
     */
    public function flush() : void
    {
        \flush_rewrite_rules();
        \update_option('lib_router_version', $this->get_version());
    }
}
