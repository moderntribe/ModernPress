<?php
/**
 * Plugin Name: Tribe AI Assistant
 * Plugin URI: https://tri.be
 * Description: AI-powered page creation with site-aware content generation and decision transparency.
 * Version: 1.0.0
 * Author: Modern Tribe
 * Author URI: https://tri.be
 * License: GPLv2 or later
 * Text Domain: tribe-ai-assistant
 *
 * @package Tribe\AI
 */

namespace Tribe\AI;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'TRIBE_AI_VERSION', '1.0.0' );
define( 'TRIBE_AI_FILE', __FILE__ );
define( 'TRIBE_AI_DIR', plugin_dir_path( __FILE__ ) );
define( 'TRIBE_AI_URL', plugin_dir_url( __FILE__ ) );
define( 'TRIBE_AI_BASENAME', plugin_basename( __FILE__ ) );

// Load Composer autoloader.
if ( file_exists( TRIBE_AI_DIR . 'vendor/autoload.php' ) ) {
	require_once TRIBE_AI_DIR . 'vendor/autoload.php';
}

/**
 * Main plugin class.
 */
class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

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
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks(): void {
		add_action( 'plugins_loaded', [ $this, 'init' ] );
		register_activation_hook( TRIBE_AI_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( TRIBE_AI_FILE, [ $this, 'deactivate' ] );
	}

	/**
	 * Initialize plugin.
	 */
	public function init(): void {
		// Load text domain.
		load_plugin_textdomain(
			'tribe-ai-assistant',
			false,
			dirname( TRIBE_AI_BASENAME ) . '/languages'
		);

		// Initialize admin settings.
		if ( is_admin() ) {
			$settings = new Admin\Settings_Page();
			$settings->init();
		}

		// Initialize REST API.
		$rest_controller = new REST\AI_Controller();
		add_action( 'rest_api_init', [ $rest_controller, 'register_routes' ] );

		$documentation_controller = new REST\Documentation_Controller();
		add_action( 'rest_api_init', [ $documentation_controller, 'register_routes' ] );

		// Initialize editor assets.
		$assets_enqueuer = new Editor\Assets_Enqueuer();
		add_action( 'enqueue_block_editor_assets', [ $assets_enqueuer, 'enqueue' ] );
	}

	/**
	 * Plugin activation.
	 */
	public function activate(): void {
		// Trigger initial site analysis.
		if ( class_exists( 'Tribe\AI\AI\Site_Analyzer' ) ) {
			$analyzer = new AI\Site_Analyzer();
			$analyzer->analyze_site();
		}

		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate(): void {
		// Clear cached site context.
		delete_transient( 'tribe_ai_site_context' );

		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}

// Initialize plugin.
Plugin::instance();
