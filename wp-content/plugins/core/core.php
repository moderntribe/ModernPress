<?php declare(strict_types=1);

/*
Plugin Name: Modern Tribe Core Functionality
Description: Core functionality for this site.
Author:      Modern Tribe
Version:     1.0
Author URI:  http://tri.be
Requires Plugins: advanced-custom-fields-pro, disable-emojis, safe-svg, social-sharing-block
*/

// Some hosts do not allow sub-folder WP installs, this check will cover multiple conditions.
use Tribe\Plugin\Core;

$tribe_autoloaders = [
	// WP sub folder
	trailingslashit( ABSPATH ) . '../vendor/autoload.php',
	// WP standard
	trailingslashit( ABSPATH ) . 'vendor/autoload.php',
	// In the core plugin
	trailingslashit( __DIR__ ) . 'vendor/autoload.php',
];

foreach ( $tribe_autoloaders as $autoloader ) {
	if ( file_exists( $autoloader ) ) {
		require_once $autoloader;
		break;
	}
}

// Start the core plugin
add_action( 'plugins_loaded', static function (): void {
	tribe_project()->init( __FILE__ );
}, 1, 0 );

/**
 * Shorthand to get the instance of our main core plugin class
 */
function tribe_project(): Core {
	return Core::instance();
}
