<?php

namespace Tribe\Alert_Scoped\Tribe\Libs\Cache;

/**
 * Class Purger
 *
 * An admin bar control for completely purging the cache
 */
class Purger
{
    private $query_arg = 'tribe-clear-cache';
    private $nonce_action = 'clear-cache';
    /**
     * @var string The capability required to purge the cache
     */
    private $cap;
    public function __construct($cap = 'manage_options')
    {
        $this->cap = $cap;
    }
    /**
     * Hook into WordPress to register the button and its handler
     * @return void
     */
    public function hook()
    {
        if (\current_user_can($this->cap)) {
            \add_action('init', [$this, 'maybe_purge_cache'], 9, 0);
            \add_action('admin_bar_menu', [$this, 'add_admin_bar_button'], 100, 1);
        }
    }
    /**
     * Handle requests to clear the cache
     *
     * @return void
     * @action init 9
     */
    public function maybe_purge_cache()
    {
        if (empty($_REQUEST[$this->query_arg]) || empty($_REQUEST['_wpnonce']) || !\wp_verify_nonce($_REQUEST['_wpnonce'], $this->nonce_action)) {
            return;
            // nothing to do here
        }
        if (!\current_user_can($this->cap)) {
            return;
            // user shouldn't be here
        }
        $this->do_purge_cache();
        \wp_redirect(\esc_url_raw(\remove_query_arg([$this->query_arg, '_wpnonce'])));
        exit;
    }
    /**
     * Purge everything from the cache
     *
     * TODO: break this into separate strategy classes
     * @return void
     */
    private function do_purge_cache()
    {
        if (\class_exists('Tribe\\Alert_Scoped\\WpeCommon')) {
            \Tribe\Alert_Scoped\WpeCommon::purge_memcached();
            \Tribe\Alert_Scoped\WpeCommon::clear_maxcdn_cache();
            \Tribe\Alert_Scoped\WpeCommon::purge_varnish_cache();
        } else {
            if (\function_exists('pantheon_clear_edge_all')) {
                \pantheon_clear_edge_all();
            }
            \wp_cache_flush();
            if (\function_exists('opcache_reset')) {
                \opcache_reset();
            }
        }
        \do_action('tribe_purge_cache');
    }
    /**
     * @param \WP_Admin_Bar $admin_bar
     * @action admin_bar_menu 100
     */
    public function add_admin_bar_button($admin_bar)
    {
        if (!\current_user_can($this->cap)) {
            return;
            // user doesn't have access to purge, so no button
        }
        $admin_bar->add_menu(['parent' => '', 'id' => 'clear-cache', 'title' => \__('Clear Cache', 'tribe'), 'meta' => ['title' => \__('Clear the cache for this site', 'tribe')], 'href' => \wp_nonce_url(\add_query_arg([$this->query_arg => 1]), $this->nonce_action)]);
    }
}
