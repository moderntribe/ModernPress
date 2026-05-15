<?php
/*
Plugin Name: FacetWP
Description: Advanced Filtering for WordPress
Version: 4.4.1
Text Domain: fwp-front
Domain Path: /languages
Author: FacetWP, LLC
Author URI: https://facetwp.com/

Copyright 2025 FacetWP, LLC

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/>.
*/

defined( 'ABSPATH' ) or exit;

class FacetWP
{

    private static $instance;

    public $filtered_post_ids;
    public $unfiltered_post_ids;
    public $is_filtered;
    public $is_modified;
    public $or_values;

    public $init;
    public $api;
    public $helper;
    public $facet;
    public $settings;
    public $diff;
    public $indexer;
    public $display;
    public $builder;
    public $request;
    public $ajax;
    public $acf;


    function __construct() {

        // php check
        if ( version_compare( phpversion(), '7.0', '<' ) ) {
            add_action( 'admin_notices', array( $this, 'upgrade_notice' ) );
            return;
        }

        // setup variables
        define( 'FACETWP_VERSION', '4.4.1' );
        define( 'FACETWP_DIR', dirname( __FILE__ ) );
        define( 'FACETWP_URL', plugins_url( '', __FILE__ ) );
        define( 'FACETWP_BASENAME', plugin_basename( __FILE__ ) );

        // get the gears turning
        include( FACETWP_DIR . '/includes/class-init.php' );
    }


    /**
     * Singleton
     */
    public static function instance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }


    /**
     * Require PHP 7.0+
     */
    function upgrade_notice() {
        $message = __( 'FacetWP requires PHP %s or above. Please contact your host and request a PHP upgrade.', 'fwp' );
        echo '<div class="error"><p>' . sprintf( $message, '7.0' ) . '</p></div>';
    }
}


function FWP() {
    return FacetWP::instance();
}


FWP();
