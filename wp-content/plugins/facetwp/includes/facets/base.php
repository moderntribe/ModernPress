<?php

class FacetWP_Facet
{

    public $label;
    public $fields = [];


    /**
     * Grab the orderby, as needed by several facet types
     * @since 3.0.4
     */
    function get_orderby( $facet ) {
        $key = $facet['orderby'];

        // Count (default)
        $orderby = 'counter DESC, f.facet_display_value ASC';

        // Display value
        if ( 'display_value' == $key ) {
            $orderby = 'f.facet_display_value ASC';
        }
        // Raw value
        elseif ( 'raw_value' == $key ) {
            $orderby = 'f.facet_value ASC';
        }
        // Term order
        elseif ( 'term_order' == $key && 'tax' == substr( $facet['source'], 0, 3 ) ) {
            $term_ids = get_terms( [
                'taxonomy' => str_replace( 'tax/', '', $facet['source'] ),
                'term_order' => true, // Custom flag
                'fields' => 'ids',
            ] );

            if ( ! empty( $term_ids ) && ! is_wp_error( $term_ids ) ) {
                $term_ids = implode( ',', $term_ids );
                $orderby = "FIELD(f.term_id, $term_ids)";
            }
        }

        // Sort by depth
        if ( FWP()->helper->facet_is( $facet, 'hierarchical', 'yes' ) ) {
            $orderby = "f.depth, $orderby";
        }

        return $orderby;
    }


    /**
     * Grab the limit, and support -1
     * @since 3.5.4
     */
    function get_limit( $facet, $default = 10, $field = 'count' ) {
        $count = $facet[ $field ] ?? $default;

        if ( '-1' == $count ) {
            return 1000;
        }
        elseif ( ctype_digit( "$count" ) ) { // ctype_digit expects input string
            return $count;
        }

        return $default;
    }


    /**
     * Adjust the $where_clause for facets in "OR" mode
     *
     * FWP()->or_values contains EVERY facet and their matching post IDs
     * FWP()->unfiltered_post_ids contains original post IDs
     *
     * @since 3.2.0
     */
    function get_where_clause( $facet ) {

        // Ignore the current facet's selections
        if ( isset( FWP()->or_values ) && ( 1 < count( FWP()->or_values ) || ! isset( FWP()->or_values[ $facet['name'] ] ) ) ) {
            $post_ids = [];
            $or_values = FWP()->or_values; // Preserve original
            unset( $or_values[ $facet['name'] ] );

            $counter = 0;
            foreach ( $or_values as $name => $vals ) {
                $post_ids = ( 0 == $counter ) ? $vals : array_intersect( $post_ids, $vals );
                $counter++;
            }

            $post_ids = array_intersect( $post_ids, FWP()->unfiltered_post_ids );
        }
        else {
            $post_ids = FWP()->unfiltered_post_ids;
        }

        $post_ids = empty( $post_ids ) ? [ 0 ] : $post_ids;
        return ' AND post_id IN (' . implode( ',', $post_ids ) . ')';
    }


    /**
     * Render some commonly used admin settings
     * @since 3.5.6
     * @deprecated 3.9
     */
    function render_setting( $name ) {
        echo FWP()->settings->get_facet_field_html( $name );
    }
}
