<?php declare(strict_types=1);

namespace Tribe\Mu;

/*
Plugin Name: Force Plugin Activation
Description: Make sure the required plugins are always active.
Version: 2.0.0
Update URI: false
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The ForcePluginActivation plugin class.
 *
 * @since 2.0.0
 */
final class ForcePluginActivation {

	/**
	 * The Instance
	 *
	 * @since 2.0.0
	 */
	protected static self $instance;

	/**
	 * The registry of plugins to activate by environment and otherwise will be deactivated.
	 *
	 * Add your plugins here.
	 *
	 * Example:
	 *
	 * `'wp-force-login/wp-force-login.php' => ['development', 'staging'],`
	 * `'kadence-blocks/kadence-blocks.php' => ['all'],`
	 *
	 * @since 2.0.0
	 *
	 * @var array<string, string[]> 'directory/file.php' => (WP_ENVIRONMENT_TYPE|'all')[]
	 */
	private array $plugins = [
		'core/core.php'                      => [ 'all' ],
		'advanced-custom-fields-pro/acf.php' => [ 'all' ],
		'disable-emojis/disable-emojis.php'  => [ 'all' ],
		'safe-svg/safe-svg.php'              => [ 'all' ],
		'tribe-glomar/tribe-glomar.php'      => [ 'development', 'staging' ],
	];

	/**
	 * The registry of plugins to only show in the network admin.
	 *
	 * Add your plugins here.
	 *
	 * @since 2.0.0
	 *
	 *  @var array<string, string[]>  'directory/file.php' => (WP_ENVIRONMENT_TYPE|'all')[]
	 */
	private array $networkOnlyPlugins = [];

	/**
	 * The list of plugins to activate.
	 *
	 * Don't add anything here, this gets updated automatically.
	 *
	 * @since 2.0.0
	 *
	 * @var string[] directory/file.php
	 */
	private array $pluginsToActivate = [];

	/**
	 * The list of plugins to deactivate.
	 *
	 * Don't add anything here, this gets updated automatically.
	 *
	 * @since 2.0.0
	 *
	 * @var array<string, string[]> 'directory/file.php' => (WP_ENVIRONMENT_TYPE|'all')[]
	 */
	private array $pluginsToDeactivate = [];

	/**
	 * Main plugin instance.
	 *
	 * @since 2.0.0
	 */
	public static function getInstance(): self {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Bootstraps the plugin.
	 *
	 * @since 2.0.0
	 */
	public function boot(): void {
		add_filter( 'option_active_plugins', [ $this, 'forcePlugins' ] );
		add_filter( 'site_option_active_sitewide_plugins', [ $this, 'forcePlugins' ] );
		add_filter( 'plugin_action_links', [ $this, 'pluginActionLinks' ], 99, 2 );
		add_filter( 'network_admin_plugin_action_links', [ $this, 'pluginActionLinks' ], 99, 2 );
		add_filter( 'all_plugins', [ $this, 'hideFromBlog' ], 99 );
	}

	/**
	 * Enforce the activate/deactivate plugin rules.
	 *
	 * @since 2.0.0
	 */
	public function forcePlugins( array $plugins ): array {
		$env = wp_get_environment_type();

		/*
		 * WordPress works in mysterious ways active_plugins has the plugin paths
		 * as the key and a number as the value active_sitewide_plugins has the
		 * number as the key and the plugin path as the value I'm standardizing so
		 * that we can run the array operations below, then flipping back if needed.
		 */
		if ( current_filter() === 'site_option_active_sitewide_plugins' ) {
			$plugins = array_flip( $plugins );
		}

		// First, deactivate all the forced plugins, this is an additive solution.
		$plugins                   = array_diff( $plugins, array_keys( $this->plugins ) );
		$this->pluginsToDeactivate = $this->plugins;

		// Get the forced plugins by environment.
		foreach ( $this->plugins as $plugin => $environments ) {
			if ( in_array( 'all', $environments ) ) {
				$this->pluginsToActivate[] = $plugin;
				unset( $this->pluginsToDeactivate[ $plugin ] );
				continue;
			}

			if ( $env === 'local' && in_array( 'local', $environments ) ) {
				$this->pluginsToActivate[] = $plugin;
				unset( $this->pluginsToDeactivate[ $plugin ] );
				continue;
			}

			if ( $env === 'development' && in_array( 'development', $environments ) ) {
				$this->pluginsToActivate[] = $plugin;
				unset( $this->pluginsToDeactivate[ $plugin ] );
				continue;
			}

			if ( $env === 'staging' && in_array( 'staging', $environments ) ) {
				$this->pluginsToActivate[] = $plugin;
				unset( $this->pluginsToDeactivate[ $plugin ] );
				continue;
			}

			if ( $env !== 'production' || ! in_array( 'production', $environments ) ) {
				continue;
			}

			$this->pluginsToActivate[] = $plugin;
			unset( $this->pluginsToDeactivate[ $plugin ] );
		}

		// Add the forced plugins by environment.
		$plugins = array_merge( $plugins, $this->pluginsToActivate );

		// Deduplicate
		$plugins = array_unique( $plugins );

		// Flip back if needed (see comment above).
		if ( current_filter() === 'site_option_active_sitewide_plugins' ) {
			$plugins = array_flip( $plugins );
		}

		return $plugins;
	}

	/**
	 * Removes the activate/deactivate links from the plugins list
	 * if they are in the force active or force deactivate lists.
	 *
	 * @since 2.0.0
	 */
	public function pluginActionLinks( array $actions, string $pluginFile ): array {
		if ( in_array( $pluginFile, $this->pluginsToActivate, true ) ) {
			unset( $actions['deactivate'] );
		}

		if ( array_key_exists( $pluginFile, $this->pluginsToDeactivate ) ) {
			unset( $actions['activate'] );
		}

		return $actions;
	}

	/**
	 * Removes plugins from the blog plugins list
	 * if they are in the $force_network_only list
	 *
	 * Only on multisite.
	 *
	 * @since 2.0.0
	 */
	public function hideFromBlog( array $plugins ): array {
		if ( ! is_multisite() ) {
			return $plugins;
		}

		$screen = get_current_screen();
		if ( $screen && $screen->in_admin( 'network' ) ) {
			return $plugins;
		}

		foreach ( $this->networkOnlyPlugins as $slug => $environments ) {
			if ( ! isset( $plugins[ $slug ] ) ) {
				continue;
			}

			unset( $plugins[ $slug ] );
		}

		return $plugins;
	}

}

$instance = ForcePluginActivation::getInstance();
$instance->boot();
