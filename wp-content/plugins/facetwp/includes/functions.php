<?php

// DO NOT MODIFY THIS FILE!
// Use your theme's functions.php instead

/**
 * An alternate to using do_shortcode()
 * 
 * facetwp_display( 'pager' );
 * facetwp_display( 'template', 'cars' );
 * facetwp_display( 'template', 'cars', [ 'static' => true ] );
 * 
 * @since 1.7.5
 */
function facetwp_display() {
    $args = array_replace( [ 'pager', true, [] ], func_get_args() );

    $atts = (array) $args[2];
    $atts[ $args[0] ] = $args[1];

    return FWP()->display->shortcode( $atts );
}


/**
 * Allow for translation of dynamic strings
 * @since 2.1
 */
function facetwp_i18n( $string ) {
    return apply_filters( 'facetwp_i18n', $string );
}


/**
 * Support SQL modifications
 * @since 2.7
 */
function facetwp_sql( $sql, $facet ) {
    global $wpdb;

    $sql = apply_filters( 'facetwp_wpdb_sql', $sql, $facet );
    return apply_filters( 'facetwp_wpdb_get_col', $wpdb->get_col( $sql ), $sql, $facet );
}
