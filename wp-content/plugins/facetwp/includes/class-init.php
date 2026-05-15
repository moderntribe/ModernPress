<?php

class FacetWP_Init
{

    function __construct() {
        add_action( 'init', [ $this, 'init' ], 20 );
        add_action( 'admin_notices', [ $this, 'admin_notices' ] );
        add_filter( 'woocommerce_is_rest_api_request', [ $this, 'is_rest_api_request' ] );
    }


    /**
     * Initialize classes and WP hooks
     */
    function init() {

        // i18n
        $this->load_textdomain();

        // is_plugin_active
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

        $includes = [
            'api/fetch',
            'api/refresh',
            'class-helper',
            'class-ajax',
            'class-request',
            'class-renderer',
            'class-diff',
            'class-indexer',
            'class-display',
            'class-builder',
            'class-overrides',
            'class-settings',
            'class-upgrade',
            'functions'
        ];

        foreach ( $includes as $inc ) {
            include ( FACETWP_DIR . "/includes/$inc.php" );
        }

        new FacetWP_Upgrade();
        new FacetWP_Overrides();

        FWP()->api          = new FacetWP_API_Fetch();
        FWP()->helper       = new FacetWP_Helper();
        FWP()->facet        = new FacetWP_Renderer();
        FWP()->settings     = new FacetWP_Settings();
        FWP()->diff         = new FacetWP_Diff();
        FWP()->indexer      = new FacetWP_Indexer();
        FWP()->display      = new FacetWP_Display();
        FWP()->builder      = new FacetWP_Builder();
        FWP()->request      = new FacetWP_Request();
        FWP()->ajax         = new FacetWP_Ajax();

        // integrations
        include( FACETWP_DIR . '/includes/integrations/searchwp/searchwp.php' );
        include( FACETWP_DIR . '/includes/integrations/woocommerce/woocommerce.php' );
        include( FACETWP_DIR . '/includes/integrations/edd/edd.php' );
        include( FACETWP_DIR . '/includes/integrations/acf/acf.php' );
        include( FACETWP_DIR . '/includes/integrations/wp-cli/wp-cli.php' );
        include( FACETWP_DIR . '/includes/integrations/wp-rocket/wp-rocket.php' );

        // update checks
        include( FACETWP_DIR . '/includes/class-updater.php' );

        // hooks
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'front_scripts' ] );
        add_filter( 'redirect_canonical', [ $this, 'redirect_canonical' ], 10, 2 );
        add_filter( 'plugin_action_links_facetwp/index.php', [ $this, 'plugin_action_links' ] );

        do_action( 'facetwp_init' );
    }


    /**
     * i18n support
     */
    function load_textdomain() {

        // admin-facing
        load_plugin_textdomain( 'fwp' );

        // front-facing
        load_plugin_textdomain( 'fwp-front', false, basename( FACETWP_DIR ) . '/languages' );
    }


    /**
     * Register the FacetWP settings page
     */
    function admin_menu() {
        add_options_page( 'FacetWP', 'FacetWP', apply_filters( 'facetwp_admin_settings_capability', 'manage_options' ), 'facetwp', [ $this, 'settings_page' ] );
    }


    /**
     * Notify users to install necessary integrations
     */
    function admin_notices() {
        if ( apply_filters( 'facetwp_dismiss_notices', false ) ) {
            return;
        }

        $reqs = [
            'Beaver Builder' => [
                'is_active' => class_exists( 'FLBuilderLoader' ),
                'addon' => 'facetwp-beaver-builder/facetwp-beaver-builder.php',
                'slug' => 'beaver-builder'
            ],
            'Bricks' => [
                'is_active' => function_exists( 'bricks_is_builder' ),
                'addon' => 'facetwp-bricks/facetwp-bricks.php',
                'slug' => 'bricks'
            ],
            'Elementor' => [
                'is_active' => defined( 'ELEMENTOR_VERSION' ),
                'addon' => 'facetwp-elementor/facetwp-elementor.php',
                'slug' => 'elementor'
            ],
            'Multilingual (Polylang)' => [
                'is_active' => function_exists( 'pll_register_string' ),
                'addon' => 'facetwp-i18n/facetwp-i18n.php',
                'slug' => 'multilingual'
            ],
            'Relevanssi' => [
                'is_active' => function_exists( 'relevanssi_search' ),
                'addon' => 'facetwp-relevanssi/facetwp-relevanssi.php',
                'slug' => 'relevanssi'
            ],
            'Recipes (Tasty Recipes)' => [
                'is_active' => function_exists( 'Tasty_Recipes' ),
                'addon' => 'facetwp-recipes/facetwp-recipes.php',
                'slug' => 'recipes'
            ],
            'Multilingual (WPML)' => [
                'is_active' => defined( 'ICL_SITEPRESS_VERSION' ),
                'addon' => 'facetwp-i18n/facetwp-i18n.php',
                'slug' => 'multilingual'
            ],
            'Recipes (WP Recipe Maker)' => [
                'is_active' => function_exists( 'run_wp_recipe_maker' ),
                'addon' => 'facetwp-recipes/facetwp-recipes.php',
                'slug' => 'recipes'
            ]
        ];

        $needed = [];

        foreach ( $reqs as $name => $req ) {
            if ( $req['is_active'] && ! is_plugin_active( $req['addon'] ) ) {
                $needed[] = sprintf( '<a href="https://facetwp.com/help-center/using-facetwp-with/%s/" target="_blank">%s</a>', $req['slug'], $name );
            }
        }

        if ( ! empty( $needed ) ) {
            echo '<div class="error"><p>Please install these FacetWP integration add-ons (then re-index): ' . implode( ', ', $needed ) . '</p></div>';
        }
    }


    /**
     * Enqueue jQuery
     */
    function front_scripts() {
        if ( 'yes' == FWP()->helper->get_setting( 'load_jquery', 'yes' ) ) {
            wp_enqueue_script( 'jquery' );
        }
    }


    /**
     * Route to the correct edit screen
     */
    function settings_page() {
        include( FACETWP_DIR . '/templates/page-settings.php' );
    }


    /**
     * Prevent WP from redirecting FWP pager to /page/X
     */
    function redirect_canonical( $redirect_url, $requested_url ) {
        if ( false !== strpos( $redirect_url, FWP()->helper->get_setting( 'prefix' ) . 'paged' ) ) {
            return false;
        }
        return $redirect_url;
    }


    /**
     * Add "Settings" link to plugin listing page
     */
    function plugin_action_links( $links ) {
        $settings_link = admin_url( 'options-general.php?page=facetwp' );
        $settings_link = '<a href=" ' . $settings_link . '">' . __( 'Settings', 'fwp' )  . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }


    /**
     * WooCommerce 3.6+ doesn't load its frontend includes for REST API requests
     * We need to force-load these includes for FacetWP refreshes
     * See includes() within class-woocommerce.php
     *
     * This code isn't within /integrations/woocommerce/ because it runs *before* init
     *
     * @since 3.3.10
     */
    function is_rest_api_request( $request ) {
        if ( false !== strpos( $_SERVER['REQUEST_URI'], 'facetwp' ) ) {
            return false;
        }
        return $request;
    }
}

$this->init = new FacetWP_Init();
