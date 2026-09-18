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

// Activation/deactivation hooks must be registered from the main plugin file.
register_activation_hook( __FILE__, [ 'Tribe\AI\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Tribe\AI\Plugin', 'deactivate' ] );

// Initialize plugin.
Tribe\AI\Plugin::instance();
