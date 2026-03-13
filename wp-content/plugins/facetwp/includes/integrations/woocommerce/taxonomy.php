<?php

class FacetWP_Integration_WooCommerce_Taxonomy
{

    function __construct() {
        add_action( 'woocommerce_after_template_part', [ $this, 'add_loop_tag'] );
        add_filter( 'get_terms', [ $this, 'adjust_term_counts' ], 10, 3 );
        add_filter( 'term_link', [ $this, 'append_url_vars' ], 10, 3 );
    }


    /**
     * Support category listings (Shop page display: Show categories)
     * @since 3.3.10
     */
    function add_loop_tag( $template_name ) {
        if ( 'loop/loop-start.php' == $template_name ) {
            echo "<!--fwp-loop-->\n";
        }
    }


    /**
     * Adjust the category listing counts when facets are selected
     * @since 3.3.10
     */
    function adjust_term_counts( $terms, $taxonomy, $query_vars ) {
        if ( FWP()->request->is_refresh || ! empty( FWP()->request->url_vars ) ) {
            $fields = $query_vars['fields'] ?? 'all';
            if ( 'product_cat' == reset( $taxonomy ) && 'all' == $fields ) {
                global $wpdb, $wp_query;

                $sql = $wp_query->request;
                if ( false !== ( $pos = strpos( $sql, 'ORDER BY' ) ) ) {
                    $sql = substr( $sql, 0, $pos );
                }

                $post_ids = $wpdb->get_col( $sql );

                if ( ! empty( $post_ids ) ) {
                    $term_counts = [];
                    $post_ids_str = implode( ',', $post_ids );

                    $query = "
                    SELECT term_id, COUNT(DISTINCT post_id) AS term_count
                    FROM {$wpdb->prefix}facetwp_index
                    WHERE post_id IN ($post_ids_str)
                    GROUP BY term_id";

                    $results = $wpdb->get_results( $query );

                    foreach ( $results as $row ) {
                        $term_counts[ $row->term_id ] = (int) $row->term_count;
                    }

                    foreach ( $terms as $term ) {
                        $term->count = $term_counts[ $term->term_id ] ?? 0;
                    }
                }
            }
        }

        return $terms;
    }


    /**
     * Append facet URL variables to the category archive links
     * @since 3.3.10
     */
    function append_url_vars( $term_link, $term, $taxonomy ) {
        if ( 'product_cat' == $taxonomy && did_action( 'woocommerce_shop_loop_header' ) && !did_action( 'woocommerce_after_shop_loop' ) ) {
            $query_string = filter_var( $_SERVER['QUERY_STRING'], FILTER_SANITIZE_URL );

            if ( ! empty( $query_string ) ) {
                $prefix = ( false !== strpos( $query_string, '?' ) ) ? '&' : '?';
                $term_link .= $prefix . $query_string;
            }
        }

        return $term_link;
    }
}

new FacetWP_Integration_WooCommerce_Taxonomy();
