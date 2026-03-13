<?php

class FacetWP_API_Fetch
{

    function __construct() {
        add_action( 'rest_api_init', [ $this, 'register' ] );
    }


    // PHP < 5.3
    function register() {
        register_rest_route( 'facetwp/v1', '/fetch', [
            'methods' => 'POST',
            'callback' => [ $this, 'callback' ],
            'permission_callback' => [ $this, 'permission_callback' ]
        ] );
    }


    // PHP < 5.3
    function callback( $request ) {
        $data = $request->get_param( 'data' );

        if ( ! $request->is_json_content_type()) {
            $data = empty( $data ) ? [] : json_decode( $data, true );
        }

        return $this->process_request( $data );
    }


    // PHP < 5.3
    function permission_callback( $request ) {
        return apply_filters( 'facetwp_api_can_access', false, $request );
    }


    function process_request( $params = [] ) {
        global $wpdb;

        $defaults = [
            'facets' => [
                // 'category' => [ 'acf' ]
            ],
            'query_args' => [
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 10,
                'paged' => 1,
            ],
            'settings' => [
                'first_load' => true
            ]
        ];

        $params = array_merge( $defaults, $params );
        $facet_types = FWP()->helper->facet_types;
        $valid_facets = [];
        $facets = [];

        // Validate input
        $page = (int) ( $params['query_args']['paged'] ?? 1 );
        $per_page = (int) ( $params['query_args']['posts_per_page'] ?? 10 );

        $page = max( $page, 1 );
        $per_page = ( 0 === $per_page ) ? 10 : $per_page;
        $per_page = ( -1 > $per_page ) ? absint( $per_page ) : $per_page;

        $params['query_args']['paged'] = $page;
        $params['query_args']['posts_per_page'] = $per_page;

        // Generate FWP()->facet->facets
        // Required by FWP()->helper->facet_setting_exists()
        foreach ( $params['facets'] as $facet_name => $facet_value ) {
            $facet = FWP()->helper->get_facet_by_name( $facet_name );
            if ( false !== $facet ) {
                $facet['selected_values'] = (array) $facet_value;
                $valid_facets[ $facet_name ] = $facet;
                FWP()->facet->facets[ $facet_name ] = $facet;
            }
        }

        // Get bucket of post IDs
        $query_args = $params['query_args'];
        FWP()->facet->query_args = $query_args;
        $post_ids = FWP()->facet->get_filtered_post_ids( $query_args );

        // SQL WHERE used by facets
        $where_clause = ' AND post_id IN (' . implode( ',', $post_ids ) . ')';

        // Check if empty
        if ( 0 === $post_ids[0] && 1 === count( $post_ids ) ) {
            $post_ids = [];
        }

        // get_where_clause() needs "found_posts" (keep this BELOW the empty check)
        FWP()->facet->query = (object) [ 'found_posts' => count( $post_ids ) ];

        // Get valid facets and their values
        foreach ( $valid_facets as $facet_name => $facet ) {
            $args = [
                'facet' => $facet,
                'where_clause' => $where_clause,
                'selected_values' => $facet['selected_values'],
            ];

            $facet_data = [
                'name'          => $facet['name'],
                'label'         => $facet['label'],
                'type'          => $facet['type'],
                'selected'      => $facet['selected_values'],
            ];

            // Load facet choices if available
            if ( method_exists( $facet_types[ $facet['type'] ], 'load_values' ) ) {
                $choices = $facet_types[ $facet['type'] ]->load_values( $args );
                foreach ( $choices as $key => $choice ) {
                    $row = [
                        'value'     => $choice['facet_value'],
                        'label'     => $choice['facet_display_value'],
                        'depth'     => (int) $choice['depth'],
                        'count'     => (int) $choice['counter'],
                    ];

                    if ( isset( $choice['term_id'] ) ) {
                        $row['term_id'] = (int) $choice['term_id'];
                    }

                    if ( isset( $choice['parent_id'] ) ) {
                        $row['parent_id'] = (int) $choice['parent_id'];
                    }

                    $choices[ $key ] = $row;
                }

                $facet_data['choices'] = $choices;
            }

            // Load facet settings if available
            if ( method_exists( $facet_types[ $facet['type'] ], 'settings_js' ) ) {
                $facet_data['settings'] = $facet_types[ $facet['type'] ]->settings_js( $args );
            }

            $facets[ $facet_name ] = $facet_data;
        }

        $total_rows = count( $post_ids );

        // Paginate?
        if ( 0 < $per_page ) {
            $total_pages = ceil( $total_rows / $per_page );

            if ( $page > $total_pages ) {
                $post_ids = [];
            }
            else {
                $offset = ( $per_page * ( $page - 1 ) );
                $post_ids = array_slice( $post_ids, $offset, $per_page );
            }
        }
        else {
            $total_pages = ( 0 < $total_rows ) ? 1 : 0;
        }

        // Generate the output
        $output = [
            'results' => $post_ids,
            'facets' => $facets,
            'pager' => [
                'page' => $page,
                'per_page' => $per_page,
                'total_rows' => $total_rows,
                'total_pages' => $total_pages,
            ]
        ];

        return apply_filters( 'facetwp_api_output', $output );
    }
}
