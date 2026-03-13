<?php

class FacetWP_Integration_EDD
{

    function __construct() {
        add_filter( 'facetwp_facet_sources', [ $this, 'exclude_data_sources' ] );
        add_filter( 'edd_downloads_query', [ $this, 'edd_downloads_query' ] );
        add_action( 'facetwp_assets', [ $this, 'assets' ] );
    }


    /**
     * Trigger some EDD code on facetwp-loaded
     * @since 2.0.4
     */
    function assets( $assets ) {
        $assets['edd.js'] = FACETWP_URL . '/includes/integrations/edd/edd.js';
        return $assets;
    }


    /**
     * Help FacetWP auto-detect the [downloads] shortcode
     * @since 2.0.4
     */
    function edd_downloads_query( $query ) {
        $query['facetwp'] = true;
        return $query;
    }


    /**
     * Exclude specific EDD custom fields
     * @since 2.4
     */
    function exclude_data_sources( $sources ) {
        foreach ( $sources['custom_fields']['choices'] as $key => $val ) {
            if ( 0 === strpos( $val, '_edd_' ) ) {
                unset( $sources['custom_fields']['choices'][ $key ] );
            }
        }

        return $sources;
    }
}


if ( is_plugin_active( 'easy-digital-downloads/easy-digital-downloads.php' ) ) {
    new FacetWP_Integration_EDD();
}
