<?php

class FacetWP_Integration_WP_Rocket
{

    function __construct() {
        add_filter( 'rocket_exclude_defer_js', [ $this, 'get_exclusions' ] );
        add_filter( 'rocket_delay_js_exclusions', [ $this, 'get_exclusions' ] );
        add_filter( 'rocket_cdn_reject_files', [ $this, 'get_exclusions' ] );
    }


    function get_exclusions( $excluded ) {
        $excluded[] = '(.*)facetwp(.*)';
        $excluded[] = '(.*)maps.googleapis(.*)';
        $excluded[] = '/jquery-?[0-9.]*(.min|.slim|.slim.min)?.js';
        return $excluded;
    }
}


if ( defined( 'WP_ROCKET_VERSION' ) ) {
    new FacetWP_Integration_WP_Rocket();
}
