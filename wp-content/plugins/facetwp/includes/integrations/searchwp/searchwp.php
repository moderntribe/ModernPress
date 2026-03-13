<?php

class FacetWP_Integration_SearchWP
{

    public $keywords;
    public $swp_query;
    public $first_run = true;


    function __construct() {
        add_filter( 'facetwp_is_main_query', [ $this, 'is_main_query' ], 10, 2 );
        add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ], 1000, 2 );
        add_filter( 'posts_pre_query', [ $this, 'posts_pre_query' ], 10, 2 );
        add_filter( 'posts_results', [ $this, 'posts_results' ], 10, 2 );
        add_filter( 'facetwp_facet_filter_posts', [ $this, 'search_facet' ], 10, 2 );
        add_filter( 'facetwp_facet_search_engines', [ $this, 'search_engines' ] );
        add_filter( 'searchwp\native\short_circuit', [ $this, 'short_circuit' ], 100, 2 );
    }


    /**
     * Run and cache the \SWP_Query
     */
    function is_main_query( $is_main_query, $query ) {
        if ( $is_main_query && $query->is_main_query() && $query->is_search() ) {
            $args = stripslashes_deep( $this->get_valid_args( $query ) );
            $this->keywords = $args['s'];
            $this->swp_query = $this->run_query( $args );
            $query->set( 'using_searchwp', true );
            $query->set( 'searchwp', false );
        }

        return $is_main_query;
    }


    /**
     * Whitelist supported SWP_Query arguments
     *
     * @link https://searchwp.com/documentation/classes/swp_query/#arguments
     */
    function get_valid_args( $query ) {
        $output = [];

        $valid = [
            's', 'engine', 'post__in', 'post__not_in', 'post_type', 'post_status',
            'tax_query', 'meta_query', 'date_query', 'order', 'orderby'
        ];

        foreach ( $valid as $arg ) {
            $val = $query->get( $arg );
            if ( ! empty( $val ) || 's' == $arg ) {
                $output[ $arg ] = $val;
            }
        }

        return $output;
    }


    /**
     * Modify FacetWP's render() query to use SearchWP's results while bypassing
     * WP core search. We're using this additional query to support custom query
     * modifications, such as for FacetWP's sort box.
     *
     * The hook priority (1000) is important because this needs to run after
     * FacetWP_Request->update_query_vars()
     */
    function pre_get_posts( $query ) {
        if ( true === $query->get( 'using_searchwp' ) ) {
            if ( true === $query->get( 'facetwp' ) && ! $this->first_run ) {
                $query->set( 's', '' );

                $post_ids = FWP()->filtered_post_ids;
                $post_ids = empty( $post_ids ) ? [ 0 ] : $post_ids;
                $query->set( 'post__in', $post_ids );

                if ( '' === $query->get( 'post_type' ) ) {
                    $query->set( 'post_type', 'any' );
                    $query->set( 'post_status', 'any' );
                }

                if ( '' === $query->get( 'orderby' ) ) {
                    $query->set( 'orderby', 'post__in' );
                }
            }
        }
    }


    /**
     * If [facetwp => false] then it's the get_filtered_post_ids() query. Return
     * the raw SearchWP results to prevent the additional query.
     *
     * If [facetwp => true] and [first_run => true] then it's the main WP query. Return
     * a non-null value to kill the query, since we don't use the results.
     *
     * If [facetwp => true] and [first_run => false] then it's the FacetWP render() query.
     */
    function posts_pre_query( $posts, $query ) {
        if ( true === $query->get( 'using_searchwp' ) ) {
            if ( true === $query->get( 'facetwp' ) ) {
                $query->set( 's', $this->keywords );

                // kill the main WP query
                if ( $this->first_run ) {
                    $this->first_run = false;

                    $page = max( $query->get( 'paged' ), 1 );
                    $per_page = (int) $query->get( 'posts_per_page', get_option( 'posts_per_page' ) );
                    $query->found_posts = count( FWP()->filtered_post_ids );
                    $query->max_num_pages = ( 0 < $per_page ) ? ceil( $query->found_posts / $per_page ) : 0;

                    return [];
                }
            }
            else {
                return $this->swp_query->posts;
            }
        }

        return $posts;
    }


    /**
     * Apply highlighting if available
     */
    function posts_results( $posts, $query ) {
        if ( true === $query->get( 'using_searchwp' ) ) {
            if ( true === $query->get( 'facetwp' ) && ! $this->first_run ) {

                // SearchWP 4.1+
                if ( isset( $this->swp_query->query ) ) {
                    foreach ( $posts as $index => $post ) {
                        $source = \SearchWP\Utils::get_post_type_source_name( $post->post_type );
                        $entry = new \SearchWP\Entry( $source, $post->ID, false );
                        $posts[ $index ] = $entry->native( $this->swp_query->query );
                    }
                }
            }
        }

        return $posts;
    }


    /**
     * For search facets, run \SWP_Query and return matching post IDs
     */
    function search_facet( $return, $params ) {
        $facet = $params['facet'];
        $selected_values = $params['selected_values'];
        $selected_values = is_array( $selected_values ) ? $selected_values[0] : $selected_values;
        $engine = $facet['search_engine'] ?? '';

        if ( 'search' == $facet['type'] && 0 === strpos( $engine, 'swp_' ) ) {
            $return = [];

            if ( empty( $selected_values ) ) {
                $return = 'continue';
            }
            elseif ( ! empty( FWP()->unfiltered_post_ids ) ) {

                if ( 'no' == $facet['enable_relevance'] ) {
                    add_filter( 'facetwp_use_search_relevancy', '__return_false' );
                }
                
                $swp_query = $this->run_query([
                    's' => $selected_values,
                    'engine' => substr( $engine, 4 ),
                    'post__in' => FWP()->unfiltered_post_ids
                ]);
                $return = $swp_query->posts;
            }
        }

        return $return;
    }


    /**
     * Run a search and return the \SWP_Query object
     */
    function run_query( $args ) {
        $overrides = [ 'posts_per_page' => 200, 'fields' => 'ids', 'facetwp' => true ];
        $args = array_merge( $args, $overrides );
        return new \SWP_Query( $args );
    }


    /**
     * Add engines to the search facet
     */
    function search_engines( $engines ) {

        if ( version_compare( SEARCHWP_VERSION, '4.0', '>=' ) ) {
            $settings = get_option( SEARCHWP_PREFIX . 'engines' );

            foreach ( $settings as $key => $info ) {
                $engines[ 'swp_' . $key ] = 'SearchWP - ' . $info['label'];
            }
        }
        else {
            $settings = get_option( SEARCHWP_PREFIX . 'settings' );

            foreach ( $settings['engines'] as $key => $info ) {
                $label = $info['searchwp_engine_label'] ?? __( 'Default', 'fwp' );
                $engines[ 'swp_' . $key ] = 'SearchWP - ' . $label;
            }
        }

        return $engines;
    }


    /**
     * Short-circuit SearchWP when "s" is empty and a search facet is in use
     * @since 4.2.6
     */
    function short_circuit( $bool, $query ) {
        if ( $query->is_search() && '' == $query->get( 's' ) ) {
            $facets = FWP()->facet->facets ?? FWP()->ajax->url_vars;
            foreach ( $facets AS $name => $value ) {
                if ( FWP()->helper->facet_is( $name, 'type', 'search' ) ) {
                    return true;
                }
            }
        }

        return $bool;
    }
}


if ( defined( 'SEARCHWP_VERSION' ) ) {
    new FacetWP_Integration_SearchWP();
}
