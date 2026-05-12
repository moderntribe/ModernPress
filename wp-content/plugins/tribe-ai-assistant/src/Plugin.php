<?php
/**
 * Main plugin class.
 *
 * @package Tribe\AI
 */

declare( strict_types=1 );

namespace Tribe\AI;

use Tribe\AI\Admin\Settings_Page;
use Tribe\AI\Editor\Assets_Enqueuer;
use Tribe\AI\REST\AI_Controller;
use Tribe\AI\AI\Site_Analyzer;

/**
 * Main plugin class.
 */
class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Settings page instance.
	 *
	 * @var Settings_Page
	 */
	private Settings_Page $settings_page;

	/**
	 * Assets enqueuer instance.
	 *
	 * @var Assets_Enqueuer
	 */
	private Assets_Enqueuer $assets_enqueuer;

	/**
	 * REST controller instance.
	 *
	 * @var AI_Controller
	 */
	private AI_Controller $rest_controller;

	/**
	 * Site analyzer instance.
	 *
	 * @var Site_Analyzer
	 */
	private Site_Analyzer $site_analyzer;

	/**
	 * Block registry analyzer instance.
	 *
	 * @var \Tribe\AI\AI\Block_Registry_Analyzer
	 */
	private \Tribe\AI\AI\Block_Registry_Analyzer $block_registry_analyzer;

	/**
	 * Get plugin instance.
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init_components();
		$this->init_hooks();
	}

	/**
	 * Initialize plugin components.
	 *
	 * @return void
	 */
	private function init_components(): void {
		$this->settings_page          = new Settings_Page();
		$this->assets_enqueuer        = new Assets_Enqueuer();
		$this->rest_controller        = new AI_Controller();
		$this->site_analyzer          = new Site_Analyzer();
		$this->block_registry_analyzer = new \Tribe\AI\AI\Block_Registry_Analyzer();
	}

	/**
	 * Initialize WordPress hooks.
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'admin_menu', [ $this->settings_page, 'register_menu' ] );
		add_action( 'admin_init', [ $this->settings_page, 'register_settings' ] );
		add_action( 'enqueue_block_editor_assets', [ $this->assets_enqueuer, 'enqueue' ] );
		add_action( 'rest_api_init', [ $this->rest_controller, 'register_routes' ] );

		// Cache management hooks.
		$this->init_cache_hooks();
	}

	/**
	 * Initialize cache invalidation hooks.
	 *
	 * @return void
	 */
	private function init_cache_hooks(): void {
		// Plugin changes - invalidate registry cache.
		add_action( 'activated_plugin', [ $this, 'invalidate_registry_cache' ] );
		add_action( 'deactivated_plugin', [ $this, 'invalidate_registry_cache' ] );
		add_action( 'upgrader_process_complete', [ $this, 'invalidate_registry_cache' ] );

		// Theme changes - invalidate all caches.
		add_action( 'switch_theme', [ $this, 'invalidate_all_caches' ] );
		add_action( 'customize_save_after', [ $this, 'invalidate_site_cache' ] );

		// Content changes - debounced site analysis refresh.
		add_action( 'save_post', [ $this, 'schedule_site_refresh' ], 10, 2 );
		add_action( 'tribe_ai_refresh_site_context', [ $this, 'refresh_site_context' ] );
	}

	/**
	 * Invalidate block registry cache.
	 *
	 * Called when plugins are activated, deactivated, or updated.
	 *
	 * @return void
	 */
	public function invalidate_registry_cache(): void {
		$this->block_registry_analyzer->invalidate_cache();
	}

	/**
	 * Invalidate site analysis cache.
	 *
	 * Called when content or theme customizations change.
	 *
	 * @return void
	 */
	public function invalidate_site_cache(): void {
		delete_transient( 'tribe_ai_site_context' );
	}

	/**
	 * Invalidate all caches.
	 *
	 * Called when themes are switched or major changes occur.
	 *
	 * @return void
	 */
	public function invalidate_all_caches(): void {
		$this->invalidate_registry_cache();
		$this->invalidate_site_cache();
	}

	/**
	 * Schedule a site context refresh (debounced).
	 *
	 * Only refreshes for published content to avoid unnecessary cache clears.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function schedule_site_refresh( int $post_id, \WP_Post $post ): void {
		// Only for published content.
		if ( $post->post_status !== 'publish' ) {
			return;
		}

		// Debounce: schedule for 5 minutes from now, replacing any existing schedule.
		$hook      = 'tribe_ai_refresh_site_context';
		$timestamp = time() + 300; // 5 minutes.

		// Clear any existing scheduled event.
		wp_clear_scheduled_hook( $hook );

		// Schedule new event.
		wp_schedule_single_event( $timestamp, $hook );
	}

	/**
	 * Refresh site context cache.
	 *
	 * Called by scheduled event after content changes.
	 *
	 * @return void
	 */
	public function refresh_site_context(): void {
		$this->invalidate_site_cache();

		// Optionally pre-warm the cache.
		$this->site_analyzer->analyze_site();
	}

	/**
	 * Plugin activation.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Trigger initial site analysis on activation.
		$analyzer = new Site_Analyzer();
		$analyzer->analyze_site();

		// Flush rewrite rules for REST API.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clear all caches.
		delete_transient( 'tribe_ai_site_context' );

		// Clear any scheduled events.
		wp_clear_scheduled_hook( 'tribe_ai_refresh_site_context' );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Get site analyzer instance.
	 *
	 * @return Site_Analyzer
	 */
	public function get_site_analyzer(): Site_Analyzer {
		return $this->site_analyzer;
	}

	/**
	 * Get block registry analyzer instance.
	 *
	 * @return \Tribe\AI\AI\Block_Registry_Analyzer
	 */
	public function get_block_registry_analyzer(): \Tribe\AI\AI\Block_Registry_Analyzer {
		return $this->block_registry_analyzer;
	}
}
