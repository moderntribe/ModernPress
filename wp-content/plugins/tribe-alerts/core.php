<?php

declare (strict_types=1);
/**
 * Plugin Name:       Tribe Alerts
 * Plugin URI:        https://github.com/moderntribe/tribe-alerts
 * Description:       Tribe Alerts WordPress Plugin
 * Version:           1.8.0
 * Requires PHP:      8.0
 * Author:            Modern Tribe
 * Author URI:        https://tri.be
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tribe-alerts
 * Domain Path:       /languages
 */
namespace Tribe\Alert;

use Tribe\Alert\Activation\Activator;
use Tribe\Alert\Activation\Deactivator;
if (!\defined('ABSPATH')) {
    die;
}
// Prevent duplicate autoloading during tests
if (!\class_exists(\Tribe\Alert\Core::class)) {
    // Require the vendor folder via multiple locations
    $autoloaders = (array) \apply_filters('tribe/alerts/autoloaders', [\trailingslashit(__DIR__) . 'vendor/scoper-autoload.php', \trailingslashit(__DIR__) . 'vendor/autoload.php', \trailingslashit(\WP_CONTENT_DIR) . '../vendor/autoload.php', \trailingslashit(\WP_CONTENT_DIR) . 'vendor/autoload.php']);
    $autoload = \current(\array_filter($autoloaders, 'file_exists'));
    require_once $autoload;
}
\add_action('plugins_loaded', static function () : void {
    if (!\class_exists('ACF')) {
        \add_action('admin_notices', static function () : void {
            ?>
				<div class="notice notice-error">
					<p><?php 
            \esc_html_e('Tribe Alerts requires Advanced Custom Fields Pro to be installed and activated!', 'tribe-alerts');
            ?></p>
				</div>
			<?php 
        });
        return;
    }
    if (\defined('TRIBE_ALERTS_COLOR_OPTIONS') && TRIBE_ALERTS_COLOR_OPTIONS && !\function_exists('include_field_types_swatch')) {
        \add_action('admin_notices', static function () : void {
            ?>
				<div class="notice notice-error">
					<p><?php 
            \esc_html_e('Tribe Alerts requires the "Advanced Custom Fields: Color Swatches" plugin to be installed and activated!', 'tribe-alerts');
            ?></p>
				</div>
			<?php 
        });
    }
    // Bootstrap on init so translations follow WordPress 6.7+ rules (load_plugin_textdomain).
    // Use priority -1 so subscribers can register their own `init` hooks (e.g. post type at priority 0)
    // before WordPress runs priority 0; registering those during an `init` 0 callback can skip them this request.
    \add_action('init', static function () : void {
        \load_plugin_textdomain('tribe-alerts', \false, \dirname(\plugin_basename(__FILE__)) . '/languages');
        tribe_alert()->init(__FILE__);
    }, -1, 0);
}, 5, 0);
function tribe_alert() : \Tribe\Alert\Core
{
    return \Tribe\Alert\Core::instance();
}
\register_activation_hook(__FILE__, new Activator());
\register_deactivation_hook(__FILE__, new Deactivator());
