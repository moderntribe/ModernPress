<?php declare(strict_types=1);

/*
Plugin Name: Pattern Sync
Description: Sync registered block patterns between WordPress installations via REST API and Application Passwords.
Author:      Modern Tribe
Version:     1.0.0
Author URI:  http://tri.be
*/

use PatternSync\Core;

$pattern_sync_autoloaders = [
	trailingslashit( __DIR__ ) . 'vendor/autoload.php',
	trailingslashit( ABSPATH ) . 'vendor/autoload.php',
];

foreach ( $pattern_sync_autoloaders as $autoloader ) {
	if ( file_exists( $autoloader ) ) {
		require_once $autoloader;
		break;
	}
}

add_action( 'plugins_loaded', static function (): void {
	pattern_sync()->init( __FILE__ );
}, 1, 0 );

function pattern_sync(): Core {
	return Core::instance();
}
