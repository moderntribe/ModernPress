<?php

class FacetWP_Integration_WooCommerce
{

    public $cache = [];
    public $lookup = [];
    public $storage = [];
    public $variations = [];
    public $post_clauses = false;


    function __construct() {
        add_action( 'facetwp_assets', [ $this, 'assets' ] );
        add_filter( 'facetwp_facet_sources', [ $this, 'facet_sources' ] );
        add_filter( 'facetwp_facet_display_value', [ $this, 'translate_hardcoded_choices' ], 10, 2 );
        add_filter( 'facetwp_indexer_post_facet', [ $this, 'index_woo_values' ], 10, 2 );
        add_filter( 'facetwp_facet_sources', [ $this, 'exclude_data_sources' ] );

        // Support WooCommerce product variations
        $is_enabled = ( 'yes' === FWP()->helper->get_setting( 'wc_enable_variations', 'no' ) );

        if ( apply_filters( 'facetwp_enable_product_variations', $is_enabled ) ) {
            add_filter( 'facetwp_indexer_post_facet_defaults', [ $this, 'force_taxonomy' ], 10, 2 );
            add_filter( 'facetwp_indexer_query_args', [ $this, 'index_variations' ] );
            add_filter( 'facetwp_index_row', [ $this, 'attribute_variations' ], 1 );
            add_filter( 'facetwp_wpdb_sql', [ $this, 'wpdb_sql' ], 10, 2 );
            add_filter( 'facetwp_wpdb_get_col', [ $this, 'wpdb_get_col' ], 10, 3 );
            add_filter( 'facetwp_filtered_post_ids', [ $this, 'process_variations' ] );
            add_filter( 'facetwp_facet_where', [ $this, 'facet_where' ], 10, 2 );
        }

        // Preserve the WooCommerce sort
        add_filter( 'posts_clauses', [ $this, 'preserve_sort' ], 20, 2 );

        // Prevent WooCommerce from redirecting to a single result page
        add_filter( 'woocommerce_redirect_single_search_result', [ $this, 'redirect_single_search_result' ] );

        // Prevent WooCommerce sort (posts_clauses) when doing FacetWP sort
        add_filter( 'woocommerce_default_catalog_orderby', [ $this, 'default_catalog_orderby' ] );

        // Dynamic counts when Shop Page Display = "Categories" or "Both"
        if ( apply_filters( 'facetwp_woocommerce_support_categories_display', false ) ) {
            include( FACETWP_DIR . '/includes/integrations/woocommerce/taxonomy.php' );
        }

        // Prevent WooCommerce breadcrumb from showing "Page X" on page 1 and higher when there are facet selections or pre-selected facets
        add_filter( 'woocommerce_get_breadcrumb', [ $this, 'prevent_wc_breadcrumb_paged' ], 9999, 2 );

    }


    /**
     * Run WooCommerce handlers on facetwp-refresh
     * @since 2.0.9
     */
    function assets( $assets ) {
        $assets['woocommerce.js'] = FACETWP_URL . '/includes/integrations/woocommerce/woocommerce.js';
        return $assets;
    }


    /**
     * Add WooCommerce-specific data sources
     * @since 2.1.4
     */
    function facet_sources( $sources ) {
        $sources['woocommerce'] = [
            'label' => __( 'WooCommerce', 'fwp' ),
            'choices' => [
                'woo/price'             => __( 'Price' ),
                'woo/sale_price'        => __( 'Sale Price' ),
                'woo/regular_price'     => __( 'Regular Price' ),
                'woo/average_rating'    => __( 'Average Rating' ),
                'woo/stock_status'      => __( 'Stock Status' ),
                'woo/on_sale'           => __( 'On Sale' ),
                'woo/featured'          => __( 'Featured' ),
                'woo/product_type'      => __( 'Product Type' ),
            ],
            'weight' => 5
        ];

        // Move WC taxonomy choices
        foreach ( $sources['taxonomies']['choices'] as $key => $label ) {
            if ( 'tax/product_cat' == $key || 'tax/product_tag' == $key || 0 === strpos( $key, 'tax/pa_' ) ) {
                $sources['woocommerce']['choices'][ $key ] = $label;
                unset( $sources['taxonomies']['choices'][ $key ] );
            }
        }

        return $sources;
    }


    /**
     * Attributes for WC product variations are stored in postmeta
     * @since 2.7.2
     */
    function force_taxonomy( $defaults, $params ) {
        if ( 0 === strpos( $defaults['facet_source'], 'tax/pa_' ) ) {
            $post_id = (int) $defaults['post_id'];

            if ( 'product_variation' == get_post_type( $post_id ) ) {
                $defaults['facet_source'] = str_replace( 'tax/', 'cf/attribute_', $defaults['facet_source'] );
            }
        }

        return $defaults;
    }


    /**
     * Index product variations
     * @since 2.7
     */
    function index_variations( $args ) {

        // Saving a single product
        if ( ! empty( $args['p'] ) ) {
            $post_id = (int) $args['p'];
            if ( 'product' == get_post_type( $post_id ) ) {
                if ( 'variable' == $this->get_product_type( $post_id ) ) {
                    $product = wc_get_product( $post_id );

                    if ( false !== $product ) {
                        $children = $product->get_children();
                        $args['post_type'] = [ 'product', 'product_variation' ];
                        $args['post__in'] = $children;
                        $args['post__in'][] = $post_id;
                        $args['posts_per_page'] = -1;
                        unset( $args['p'] );
                    }
                }
            }
        }
        // Force product variations to piggyback products
        else {
            $pt = (array) $args['post_type'];

            if ( in_array( 'product', $pt ) ) {
                $pt[] = 'product_variation';
            }

            $args['post_type'] = $pt;
        }

        return $args;
    }


    /**
     * When indexing product variations, attribute its parent product
     * @since 2.7
     */
    function attribute_variations( $params ) {
        $post_id = (int) $params['post_id'];

        // Set variation_id for all posts
        $params['variation_id'] = $post_id;

        if ( 'product_variation' == get_post_type( $post_id ) ) {
            $params['post_id'] = wp_get_post_parent_id( $post_id );

            // Lookup the term name for variation values
            if ( 0 === strpos( $params['facet_source'], 'cf/attribute_pa_' ) ) {
                $taxonomy = str_replace( 'cf/attribute_', '', $params['facet_source'] );
                $term = get_term_by( 'slug', $params['facet_value'], $taxonomy );

                if ( false !== $term ) {
                    $params['term_id'] = $term->term_id;
                    $params['parent_id'] = $term->parent;
                    $params['facet_display_value'] = $term->name;

                    $params['depth'] = count( get_ancestors( $term->term_id, $taxonomy, 'taxonomy' ) );

                    $facet = FWP()->helper->get_facet_by_name( $params['facet_name'] );

                    // handle parent_term setting
                    if ( isset( $facet['parent_term'] ) && 0 < $facet['parent_term'] && !term_is_ancestor_of( $facet['parent_term'], $params['term_id'], $taxonomy ) ) {
                        $params['facet_value'] = ''; // don't index
                    }
                }
            }
        }

        return $params;
    }


    /**
     * Hijack filter_posts() to grab variation IDs
     * @since 2.7
     */
    function wpdb_sql( $sql, $facet ) {
        $sql = str_replace(
            'DISTINCT post_id',
            'DISTINCT post_id, GROUP_CONCAT(variation_id) AS variation_ids',
            $sql
        );

        $sql .= ' GROUP BY post_id';

        return $sql;
    }


    /**
     * Store a facet's variation IDs
     * @since 2.7
     */
    function wpdb_get_col( $result, $sql, $facet ) {
        global $wpdb;

        $facet_name = $facet['name'];
        $post_ids = $wpdb->get_col( $sql, 0 ); // arrays of product IDs
        $variations = $wpdb->get_col( $sql, 1 ); // variation IDs as arrays of comma-separated strings

        foreach ( $post_ids as $index => $post_id ) {
            $variations_array = explode( ',', $variations[ $index ] );
            $type = in_array( $post_id, $variations_array ) ? 'products' : 'variations';

            if ( isset( $this->cache[ $facet_name ][ $type ] ) ) {
                foreach ( $variations_array as $id ) {
                    $this->cache[ $facet_name ][ $type ][] = $id;
                }
            }
            else {
                $this->cache[ $facet_name ][ $type ] = $variations_array;
            }
        }

        return $result;
    }


    /**
     * We need lookup arrays for both products and variations
     * @since 2.7.1
     */
    function generate_lookup_array( $post_ids ) {
        global $wpdb;

        $output = [];

        if ( ! empty( $post_ids ) ) {
            $sql = "
            SELECT DISTINCT post_id, variation_id
            FROM {$wpdb->prefix}facetwp_index
            WHERE post_id IN (" . implode( ',', $post_ids ) . ")";
            $results = $wpdb->get_results( $sql );

            foreach ( $results as $result ) {
                $output['get_variations'][ $result->post_id ][] = $result->variation_id;
                $output['get_product'][ $result->variation_id ] = $result->post_id;
            }
        }

        return $output;
    }


    /**
     * Determine valid variation IDs
     * @since 2.7
     */
    function process_variations( $post_ids ) {
        if ( empty( $this->cache ) ) {
            return $post_ids;
        }

        $this->lookup = $this->generate_lookup_array( FWP()->unfiltered_post_ids );

        // Loop through each facet's data
        foreach ( $this->cache as $facet_name => $groups ) {
            $this->storage[ $facet_name ] = [];

            // Create an array of variation IDs
            foreach ( $groups as $type => $ids ) { // products or variations
                foreach ( $ids as $id ) {
                    $this->storage[ $facet_name ][] = $id;

                    // Lookup variation IDs for each product
                    if ( 'products' == $type ) {
                        if ( ! empty( $this->lookup['get_variations'][ $id ] ) ) {
                            foreach ( $this->lookup['get_variations'][ $id ] as $variation_id ) {
                                $this->storage[ $facet_name ][] = $variation_id;
                            }
                        }
                    }
                }
            }
        }

        $result = $this->calculate_variations();
        $this->variations = $result['variations'];
        $post_ids = array_intersect( $post_ids, $result['products'] );
        $post_ids = empty( $post_ids ) ? [ 0 ] : $post_ids;
        return $post_ids;
    }


    /**
     * Calculate variation IDs
     * @param mixed $facet_name Facet name to ignore, or FALSE
     * @return array Associative array of product IDs + variation IDs
     * @since 2.8
     */
    function calculate_variations( $facet_name = false ) {

        $new = true;
        $final_products = [];
        $final_variations = [];

        // Intersect product + variation IDs across facets
        foreach ( $this->storage as $name => $variation_ids ) {

            // Skip facets in "OR" mode
            if ( $facet_name === $name ) {
                continue;
            }

            $final_variations = ( $new ) ? $variation_ids : array_intersect( $final_variations, $variation_ids );
            $new = false;
        }

        // Lookup each variation's product ID
        foreach ( $final_variations as $variation_id ) {
            if ( isset( $this->lookup['get_product'][ $variation_id ] ) ) {
                $final_products[ $this->lookup['get_product'][ $variation_id ] ] = true; // prevent duplicates
            }
        }

        // Append product IDs to the variations array
        $final_products = array_keys( $final_products );

        foreach ( $final_products as $id ) {
            $final_variations[] = $id;
        }

        return [
            'products' => $final_products,
            'variations' => array_unique( $final_variations )
        ];
    }


    /**
     * Apply variation IDs to load_values() method
     * @since 2.7
     */
    function facet_where( $where_clause, $facet ) {

        // Support facets in "OR" mode
        if ( FWP()->helper->facet_is( $facet, 'operator', 'or' ) ) {
            $result = $this->calculate_variations( $facet['name'] );
            $variations = $result['variations'];
        }
        else {
            $variations = $this->variations;
        }

        if ( ! empty( $variations ) ) {
            $where_clause .= ' AND variation_id IN (' . implode( ',', $variations ) . ')';
        }

        return $where_clause;
    }


    /**
     * Efficiently grab the product type without wc_get_product()
     * @since 3.3.8
     */
    function get_product_type( $post_id ) {
        global $wpdb;

        $sql = "
        SELECT t.name
        FROM $wpdb->terms t
        INNER JOIN $wpdb->term_taxonomy tt ON tt.term_id = t.term_id AND tt.taxonomy = 'product_type'
        INNER JOIN $wpdb->term_relationships tr ON tr.term_taxonomy_id = tt.term_taxonomy_id AND tr.object_id = %d";

        $type = $wpdb->get_var(
            $wpdb->prepare( $sql, $post_id )
        );

        return ( null !== $type ) ? $type : 'simple';
    }


    /**
     * Index WooCommerce-specific values
     * @since 2.1.4
     */
    function index_woo_values( $return, $params ) {
        $facet = $params['facet'];
        $defaults = $params['defaults'];
        $post_id = (int) $defaults['post_id'];
        $post_type = get_post_type( $post_id );

        // Index out of stock products?
        $index_all = ( 'yes' === FWP()->helper->get_setting( 'wc_index_all', 'no' ) );
        $index_all = apply_filters( 'facetwp_index_all_products', $index_all );

        if ( 'product' == $post_type || 'product_variation' == $post_type ) {
            $product = wc_get_product( $post_id );

            if ( ! $product || ( ! $index_all && ! $product->is_in_stock() ) ) {
                return true; // skip
            }
        }

        // Default handling
        if ( 'product' != $post_type || empty( $facet['source'] ) ) {
            return $return;
        }

        // Ignore product attributes with "Used for variations" ticked
        if ( 0 === strpos( $facet['source'], 'tax/pa_' ) ) {
            if ( 'variable' == $this->get_product_type( $post_id ) ) {
                $attrs = $product->get_attributes();
                $attr_name = str_replace( 'tax/', '', $facet['source'] );
                if ( isset( $attrs[ $attr_name ] ) && 1 === $attrs[ $attr_name ]['is_variation'] ) {
                    return true; // skip
                }
            }
        }

        // Custom woo fields
        if ( 0 === strpos( $facet['source'], 'woo/' ) ) {
            $source = substr( $facet['source'], 4 );

            // Price
            if ( 'price' == $source || 'sale_price' == $source || 'regular_price' == $source ) {
                if ( $product->is_type( 'variable' ) ) {
                    $method_name = "get_variation_$source";
                    $price_min = $product->$method_name( 'min' ); // get_variation_price()
                    $price_max = $product->$method_name( 'max' );
                }
                else {
                    $method_name = "get_$source";
                    $price_min = $price_max = $product->$method_name(); // get_price()
                }

                $defaults['facet_value'] = $price_min;
                $defaults['facet_display_value'] = $price_max;
                FWP()->indexer->index_row( $defaults );
            }

            // Average Rating
            elseif ( 'average_rating' == $source ) {
                $rating = $product->get_average_rating();
                $defaults['facet_value'] = $rating;
                $defaults['facet_display_value'] = $rating;
                FWP()->indexer->index_row( $defaults );
            }

            // Stock Status
            elseif ( 'stock_status' == $source ) {
                $in_stock = $product->is_in_stock();
                $defaults['facet_value'] = (int) $in_stock;
                $defaults['facet_display_value'] = $in_stock ? 'In Stock' : 'Out of Stock';
                FWP()->indexer->index_row( $defaults );
            }

            // On Sale
            elseif ( 'on_sale' == $source ) {
                if ( $product->is_on_sale() ) {
                    $defaults['facet_value'] = 1;
                    $defaults['facet_display_value'] = 'On Sale';
                    FWP()->indexer->index_row( $defaults );
                }
            }

            // Featured
            elseif ( 'featured' == $source ) {
                if ( $product->is_featured() ) {
                    $defaults['facet_value'] = 1;
                    $defaults['facet_display_value'] = 'Featured';
                    FWP()->indexer->index_row( $defaults );
                }
            }

            // Product Type
            elseif ( 'product_type' == $source ) {
                $type = $product->get_type();
                $defaults['facet_value'] = $type;
                $defaults['facet_display_value'] = $type;
                FWP()->indexer->index_row( $defaults );
            }

            return true; // skip
        }

        return $return;
    }


    /**
     * Exclude specific WC custom fields
     * @since 4.2.3
     */
    function exclude_data_sources( $sources ) {
        unset( $sources['custom_fields']['choices']['cf/_product_attributes'] );
        return $sources;
    }


    /**
     * Allow certain hard-coded choices to be translated dynamically
     * instead of stored as translated in the index table
     * @since 3.9.6
     */
    function translate_hardcoded_choices( $label, $params ) {
        $source = $params['facet']['source'];

        if ( 'woo/stock_status' == $source ) {

            // We aren't using a ternary here in case the user
            // assigned a custom stock status label
            if ( 'In Stock' == $label ) {
                $label = __( 'In Stock', 'fwp-front' );
            }
            elseif ( 'Out of Stock' == $label ) {
                $label = __( 'Out of Stock', 'fwp-front' );
            }
        }
        elseif ( 'woo/on_sale' == $source ) {
            $label = __( 'On Sale', 'fwp-front' );
        }
        elseif ( 'woo/featured' == $source ) {
            $label = __( 'Featured', 'fwp-front' );
        }

        return $label;
    }


    /**
     * WooCommerce removes its sort hooks after the main product_query runs
     * We need to preserve the sort for FacetWP to work
     *
     * @since 3.2.8
     */
    function preserve_sort( $clauses, $query ) {

        if ( ! apply_filters( 'facetwp_woocommerce_preserve_sort', true ) ) {
            return $clauses;
        }

        $prefix = FWP()->helper->get_setting( 'prefix' );
        $using_sort = isset( FWP()->facet->http_params['get'][ $prefix . 'sort' ] );

        if ( 'product_query' == $query->get( 'wc_query' ) && true === $query->get( 'facetwp' ) && ! $using_sort ) {
            if ( false === $this->post_clauses ) {
                $this->post_clauses = $clauses;
            }
            else {
                $clauses['join'] = $this->post_clauses['join'];
                $clauses['where'] = $this->post_clauses['where'];
                $clauses['orderby'] = $this->post_clauses['orderby'];

                // Narrow the main query results
                $where_clause = FWP()->facet->where_clause;

                if ( ! empty( $where_clause ) ) {
                    $column = $GLOBALS['wpdb']->posts;
                    $clauses['where'] .= str_replace( 'post_id', "$column.ID", $where_clause );
                }
            }
        }

        return $clauses;
    }


    /**
     * Prevent WooCommerce from redirecting to single result page
     * @since 3.3.7
     */
    function redirect_single_search_result( $bool ) {
        $using_facetwp = ( FWP()->request->is_refresh || ! empty( FWP()->request->url_vars ) );
        return $using_facetwp ? false : $bool;
    }


    /**
     * Prevent WooCommerce sort when a FacetWP sort is active
     * @since 3.6.8
     */
    function default_catalog_orderby( $orderby ) {
        $sort = FWP()->helper->get_setting( 'prefix' ) . 'sort';
        return isset( $_GET[ $sort ] ) ? 'menu_order' : $orderby;
    }


    /**
     * Prevent WooCommerce breadcrumb from showing "Page X" on page 1 and higher when there are facet selections or pre-selected facets
     * @since 4.3.6
     */
    function prevent_wc_breadcrumb_paged( $crumbs, $breadcrumb ) {

        if ( function_exists( 'FWP' ) && !empty( FWP()->request->url_vars )  ) {
            if ( get_query_var( 'paged' ) >= 1 && 'subcategories' !== woocommerce_get_loop_display_mode() ) {
                array_pop( $crumbs );
            }
        }

        return $crumbs;
    }

}


if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    new FacetWP_Integration_WooCommerce();
}
