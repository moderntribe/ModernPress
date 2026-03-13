<?php

class FacetWP_Support
{

    public $payment_id;


    function __construct() {
        $this->payment_id = (int) FWP()->helper->get_license_meta( 'payment_id' );
    }


    function get_html() {
        if ( 0 < $this->payment_id ) {
            $output = '<iframe src="https://facetwp.com/documentation/support/create-ticket/?sysinfo=' . $this->get_sysinfo() .'"></iframe>';
        }
        else {
            $output = '<h3>Active License Required</h3>';
            $output .= '<p>Please activate or renew your license to access support.</p>';
        }

        return $output;
    }


    function get_sysinfo() {
        $plugins = get_plugins();
        $active_plugins = get_option( 'active_plugins', [] );
        $theme = wp_get_theme();
        $parent = $theme->parent();

        ob_start();

?>
Home URL:                   <?php echo home_url(); ?>

Payment ID:                 <?php echo (int) $this->payment_id; ?>

WordPress Version:          <?php echo get_bloginfo( 'version' ); ?>

Theme:                      <?php echo $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ); ?>

Parent Theme:               <?php echo empty( $parent ) ? '' : $parent->get( 'Name' ) . ' ' . $parent->get( 'Version' ); ?>


Debug Mode:                 <?php echo ( 'on' == FWP()->helper->get_setting( 'debug_mode' ) ) ? 'ON' : 'OFF'; ?>

PHP Version:                <?php echo phpversion(); ?>

MySQL Version:              <?php echo esc_html( $GLOBALS['wpdb']->db_version() ); ?>

Web Server Info:            <?php echo esc_html( $_SERVER['SERVER_SOFTWARE'] ); ?>

PHP Memory Limit:           <?php echo ini_get( 'memory_limit' ); ?>

WP_MEMORY_LIMIT:            <?php echo WP_MEMORY_LIMIT; ?>

WP_MAX_MEMORY_LIMIT:        <?php echo WP_MAX_MEMORY_LIMIT; ?>

WP_DEBUG:                   <?php echo ( WP_DEBUG ) ? 'ON' : 'OFF'; ?>

WP_DEBUG_LOG:               <?php echo ( WP_DEBUG_LOG ) ? 'ON' : 'OFF'; ?>


<?php
        if( is_multisite() ) {

            echo 'WP Multisite:' . '\n\n';

            $active_network_plugins = get_site_option( 'active_sitewide_plugins' );

            echo '### Network activated:' . '\n';

            foreach ( $active_network_plugins as $plugin_path => $plugin_data ) {
                $network_plugin = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin_path);
                echo $network_plugin['Name'] . ' ' . $network_plugin['Version'] . '\n';
            }

            $sites = get_sites();
            $current_subsite_id = get_current_blog_id();

            foreach ( $sites as $subsite ) {

                $subsite_id = get_object_vars( $subsite )['blog_id'];
                $subsite_name = get_blog_details( $subsite_id )->blogname;
                $active_plugins = get_blog_option( $subsite->blog_id, 'active_plugins' );

                if ( intval( $subsite_id ) === $current_subsite_id ) {
                    echo '\n### Sub-site #' . $subsite_id . ' - ' . $subsite_name . ' (Current):\n';
                } else {
                    echo '\n### Sub-site #' . $subsite_id . ' - ' . $subsite_name . ':\n';
                }

                if ( ! empty( $active_plugins ) ) {
                    foreach ( $plugins as $plugin_path => $plugin ) {
                        if ( in_array( $plugin_path, $active_plugins ) ) {
                            echo $plugin['Name'] . ' ' . $plugin['Version'] . '\n';
                        }
                    }
                } else {
                    echo 'No active plugins' . '\n';
                }
            }
            
        } else {
            foreach ( $plugins as $plugin_path => $plugin ) {
                if ( in_array( $plugin_path, $active_plugins ) ) {
                    echo $plugin['Name'] . ' ' . $plugin['Version'] . '\n';
                }
            }
        }

        $output = ob_get_clean();
        $output = str_replace( '.php', '-php', $output );
        $output = preg_replace( "/[ ]{2,}/", ' ', trim( $output ) );
        $output = str_replace( '\n', '{n}', $output );
        $output = urlencode( $output );
        return $output;
    }
}