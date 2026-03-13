<?php

class FacetWP_Renderer
{

    /* (array) Data for the current facets */
    public $facets;

    /* (array) Data for the current template */
    public $template;

    /* (array) WP_Query arguments */
    public $query_args;

    /* (array) WP_Query args when "facetwp_template_use_archive" is enabled */
    public $archive_args;

    /* (array) Data used to build the pager */
    public $pager_args;

    /* (string) MySQL WHERE clause passed to each facet */
    public $where_clause = '';

    /* (array) AJAX parameters passed in */
    public $ajax_params;

    /* (array) HTTP parameters from the original page (URI, GET) */
    public $http_params;

    /* (boolean) Is search active? */
    public $is_search = false;

    /* (boolean) Are we preloading? */
    public $is_preload = false;

    /* (array) Cache preloaded facet values */
    public $preloaded_values;

    /* (array) The final WP_Query object */
    public $query;

    /* (array) Convenience var */
    public $facet_types;


    function __construct() {
        $this->facet_types = FWP()->helper->facet_types;
    }


    /**
     * Generate the facet output
     * @param array $params An array of arrays (see the FacetWP->refresh() method)
     * @return array
     */
    function render( $params ) {

        $output = [
            'facets'        => [],
            'template'      => '',
            'settings'      => [],
        ];

        // Hook params
        $params = apply_filters( 'facetwp_render_params', $params );

        // First ajax refresh?
        $first_load = (bool) $params['first_load'];
        $is_bfcache = (bool) $params['is_bfcache'];

        // Initial pageload?
        $this->is_preload = isset( $params['is_preload'] );

        // Set the AJAX and HTTP params
        $this->ajax_params = $params;
        $this->http_params = $params['http_params'];

        // Validate facets
        $this->facets = [];
        foreach ( $params['facets'] as $f ) {
            $name = $f['facet_name'];
            $facet = FWP()->helper->get_facet_by_name( $name );
            if ( $facet ) {

                // Default to "OR" mode
                $facet['operator'] = $facet['operator'] ?? 'or';

                // Fix operator for non-multiselect
                $ui_type = empty( $facet['ui_type'] ) ? $facet['type'] : $facet['ui_type'];
                if ( 'fselect' == $ui_type ) {
                    $multi = $facet['multiple'] ?? 'no';
                    $facet['operator'] = ( 'no' == $multi ) ? 'or' : $facet['operator'];
                } else if ( in_array( $ui_type, [ 'radio', 'dropdown' ] ) ) {
                    $facet['operator'] = 'or';
                }

                // Support the "facetwp_preload_url_vars" hook
                if ( $first_load && empty( $f['selected_values'] ) && ! empty( $this->http_params['url_vars'][ $name ] ) ) {
                    $f['selected_values'] = $this->http_params['url_vars'][ $name ];
                }

                // Support commas within search / autocomplete facets
                if ( 'search' == $facet['type'] || 'autocomplete' == $facet['type'] ) {
                    $f['selected_values'] = implode( ',', (array) $f['selected_values'] );
                }

                $facet['selected_values'] = FWP()->helper->sanitize( $f['selected_values'] );

                $this->facets[ $name ] = $facet;
            }
        }

        // Get the template from $helper->settings
        if ( 'wp' == $params['template'] ) {
            $this->template = [ 'name' => 'wp' ];
            $query_args = FWP()->request->query_vars ?? [];
        }
        else {
            $this->template = FWP()->helper->get_template_by_name( $params['template'] );
            $query_args = $this->get_query_args();
        }

        // Detect search string
        if ( ! empty( $query_args['s'] ) ) {
            $this->is_search = true;
        }

        // Run the query once (prevent duplicate queries when preloading)
        if ( empty( $this->query_args ) ) {

            // Support "post__in"
            if ( empty( $query_args['post__in'] ) ) {
                $query_args['post__in'] = [];
            }

            // Get the template "query" field
            $query_args = apply_filters( 'facetwp_query_args', $query_args, $this );

            // Pagination
            $query_args['paged'] = empty( $params['paged'] ) ? 1 : (int) $params['paged'];

            // Preserve SQL_CALC_FOUND_ROWS
            unset( $query_args['no_found_rows'] );

            // Narrow posts based on facet selections
            $post_ids = $this->get_filtered_post_ids( $query_args );

            // Update the SQL query
            if ( ! empty( $post_ids ) ) {
                if ( FWP()->is_filtered || FWP()->is_modified ) {
                    $query_args['post__in'] = $post_ids;
                }

                $this->where_clause = ' AND post_id IN (' . implode( ',', $post_ids ) . ')';
            }

            // Set the default limit
            if ( empty( $query_args['posts_per_page'] ) ) {
                $query_args['posts_per_page'] = (int) get_option( 'posts_per_page' );
            }

            // Adhere to the "per page" box
            $per_page = isset( $params['extras']['per_page'] ) ? $params['extras']['per_page'] : '';
            if ( ! empty( $per_page ) && 'default' != $per_page ) {
                $query_args['posts_per_page'] = (int) $per_page;
            }

            $this->query_args = apply_filters( 'facetwp_filtered_query_args', $query_args, $this );

            // Run the WP_Query
            $this->query = new WP_Query( $this->query_args );
        }

        // Debug
        if ( 'on' == FWP()->helper->get_setting( 'debug_mode', 'off' ) ) {
            $output['settings']['debug'] = $this->get_debug_info();
        } else {
            $output['settings']['debug'] = "Enable debug mode in [Settings > FacetWP > Settings]";
        }

        // Generate the template HTML
        // For performance gains, skip the template on pageload
        if ( 'wp' != $this->template['name'] ) {
            if ( ! $first_load || $is_bfcache || apply_filters( 'facetwp_template_force_load', false ) ) {
                $output['template'] = $this->get_template_html();
            }
        }

        // Don't render these facets
        $frozen_facets = $params['frozen_facets'];

        // Calculate pager args
        $pager_args = [
            'page'          => (int) $this->query_args['paged'],
            'per_page'      => (int) $this->query_args['posts_per_page'],
            'total_rows'    => (int) $this->query->found_posts,
            'total_rows_unfiltered' => (int) count( FWP()->unfiltered_post_ids ),
            'total_pages'   => 1,
        ];

        if ( 0 < $pager_args['per_page'] ) {
            $pager_args['total_pages'] = ceil( $pager_args['total_rows'] / $pager_args['per_page'] );
        }

        $pager_args = apply_filters( 'facetwp_pager_args', $pager_args, $this );

        $this->pager_args = $pager_args;

        // Stick the pager args into the JSON response
        $output['settings']['pager'] = $pager_args;

        // Display the pagination HTML
        if ( isset( $params['extras']['pager'] ) ) {
            $output['pager'] = $this->paginate( $pager_args );
        }

        // Display the "per page" HTML
        if ( isset( $params['extras']['per_page'] ) ) {
            $output['per_page'] = $this->get_per_page_box();
        }

        // Display the counts HTML
        if ( isset( $params['extras']['counts'] ) ) {
            $output['counts'] = $this->get_result_count( $pager_args );
        }

        // Not paging
        if ( 0 == $params['soft_refresh'] ) {
            $output['settings']['num_choices'] = [];
        }

        // Get facet data
        foreach ( $this->facets as $facet_name => $the_facet ) {
            $facet_type = $the_facet['type'];
            $ui_type = empty( $the_facet['ui_type'] ) ? $facet_type : $the_facet['ui_type'];

            // Invalid facet type
            if ( ! isset( $this->facet_types[ $facet_type ] ) ) {
                continue;
            }

            // Skip facets when paging
            if ( 0 < $params['soft_refresh'] && 'pager' != $facet_type ) {
                continue;
            }

            // Get facet labels
            if ( 0 == $params['soft_refresh'] ) {
                $output['settings']['labels'][ $facet_name ] = facetwp_i18n( $the_facet['label'] );
            }

            // Load all facets on back / forward button press (first_load = true)
            if ( ! $first_load ) {

                // Skip frozen facets
                if ( isset( $frozen_facets[ $facet_name ] ) ) {
                    continue;
                }
            }

            $args = [
                'facet' => $the_facet,
                'where_clause' => $this->where_clause,
                'selected_values' => $the_facet['selected_values'],
            ];

            // Load facet values if needed
            if ( method_exists( $this->facet_types[ $facet_type ], 'load_values' ) ) {

                // Grab preloaded values if available
                if ( isset( $this->preloaded_values[ $facet_name ] ) ) {
                    $args['values'] = $this->preloaded_values[ $facet_name ];
                }
                else {
                    $args['values'] = $this->facet_types[ $facet_type ]->load_values( $args );

                    if ( $this->is_preload ) {
                        $this->preloaded_values[ $facet_name ] = $args['values'];
                    }
                }
            }

            // Filter the render args
            $args = apply_filters( 'facetwp_facet_render_args', $args );

            // Return the number of available choices
            if ( isset( $args['values'] ) ) {
                $num_choices = 0;
                $is_ghost = FWP()->helper->facet_is( $the_facet, 'ghosts', 'yes' );

                foreach ( $args['values'] as $choice ) {
                    if ( isset( $choice['counter'] ) && ( 0 < $choice['counter'] || $is_ghost ) ) {
                        $num_choices++;
                    }
                }

                $output['settings']['num_choices'][ $facet_name ] = $num_choices;
            }

            // Generate the facet HTML
            $html = $this->facet_types[ $ui_type ]->render( $args );
            $output['facets'][ $facet_name ] = apply_filters( 'facetwp_facet_html', $html, $args );

            // Return any JS settings
            if ( method_exists( $this->facet_types[ $ui_type ], 'settings_js' ) ) {
                $output['settings'][ $facet_name ] = $this->facet_types[ $ui_type ]->settings_js( $args );
            }

            // Grab num_choices for slider facets
            if ( 'slider' == $the_facet['type'] ) {
                $min = $output['settings'][ $facet_name ]['range']['min'];
                $max = $output['settings'][ $facet_name ]['range']['max'];
                $output['settings']['num_choices'][ $facet_name ] = ( $min == $max ) ? 0 : 1;
            }
        }

        return apply_filters( 'facetwp_render_output', $output, $params );
    }


    /**
     * Get WP_Query arguments by executing the template "query" field
     * @return null
     */
    function get_query_args() {

        $defaults = [];

        // Allow templates to piggyback archives
        if ( apply_filters( 'facetwp_template_use_archive', false ) ) {
            $main_query = $GLOBALS['wp_the_query'];

            // Initial pageload
            if ( $main_query->is_archive || $main_query->is_search ) {
                if ( $main_query->is_category ) {
                    $defaults['cat'] = $main_query->get( 'cat' );
                }
                elseif ( $main_query->is_tag ) {
                    $defaults['tag_id'] = $main_query->get( 'tag_id' );
                }
                elseif ( $main_query->is_tax ) {
                    $defaults['taxonomy'] = $main_query->get( 'taxonomy' );
                    $defaults['term'] = $main_query->get( 'term' );
                }
                elseif ( $main_query->is_search ) {
                    $defaults['s'] = $main_query->get( 's' );
                }

                $this->archive_args = $defaults;
            }
            // Subsequent ajax requests
            elseif ( ! empty( $this->http_params['archive_args'] ) ) {
                foreach ( $this->http_params['archive_args'] as $key => $val ) {
                    if ( in_array( $key, [ 'cat', 'tag_id', 'taxonomy', 'term', 's' ] ) ) {
                        $defaults[ $key ] = $val;
                    }
                }
            }
        }

        // Use the query builder
        if ( isset( $this->template['modes'] ) && 'visual' == $this->template['modes']['query'] ) {
            $query_args = FWP()->builder->parse_query_obj( $this->template['query_obj'] );
        }
        else {

            // remove UTF-8 non-breaking spaces
            $query_args = preg_replace( "/\xC2\xA0/", ' ', $this->template['query'] );
            $query_args = (array) eval( '?>' . $query_args );
        }

        // Merge the two arrays
        return array_merge( $defaults, $query_args );
    }


    /**
     * Get ALL post IDs for the matching query
     * @return array An array of post IDs
     */
    function get_filtered_post_ids( $query_args = [] ) {

        if ( empty( $query_args ) ) {
            $query_args = $this->query_args;
        }

        // Only get relevant post IDs
        $args = array_merge( $query_args, [
            'paged' => 1,
            'posts_per_page' => -1,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'cache_results' => false,
            'no_found_rows' => true,
            'nopaging' => true, // prevent "offset" issues
            'facetwp' => false,
            'fields' => 'ids',
        ] );

        $query = new WP_Query( $args );

        // Allow hooks to modify the default post IDs
        $post_ids = apply_filters( 'facetwp_pre_filtered_post_ids', $query->posts, $this );

        // Store the original post IDs (before facet filtering is applied)
        FWP()->unfiltered_post_ids = $post_ids;

        foreach ( $this->facets as $facet_name => $the_facet ) {
            $facet_type = $the_facet['type'];

            // Stop looping
            if ( empty( $post_ids ) ) {
                break;
            }

            $matches = [];
            $selected_values = $the_facet['selected_values'];

            if ( empty( $selected_values ) ) {
                continue;
            }

            // Handle each facet
            if ( isset( $this->facet_types[ $facet_type ] ) ) {

                $hook_params = [
                    'facet' => $the_facet,
                    'selected_values' => $selected_values,
                ];

                // Hook to support custom filter_posts() handler
                $matches = apply_filters( 'facetwp_facet_filter_posts', false, $hook_params );

                if ( false === $matches ) {
                    $matches = $this->facet_types[ $facet_type ]->filter_posts( $hook_params );
                }
            }

            // Skip this facet
            if ( 'continue' == $matches ) {
                continue;
            }

            // Force array
            $matches = (array) $matches;

            // Store post IDs per facet (needed for "OR" mode)
            FWP()->or_values[ $facet_name ] = $matches;

            if ( 'search' == $facet_type ) {
                $this->is_search = true;
            }

            // For search facets, loop through $matches to set order
            // For other facets, loop through $post_ids to preserve the existing order
            $needles = ( 'search' == $facet_type ) ? $matches : $post_ids;
            $haystack = ( 'search' == $facet_type ) ? $post_ids : $matches;
            $haystack = array_flip( $haystack );
            $intersected_ids = [];

            foreach ( $needles as $post_id ) {
                if ( isset( $haystack[ $post_id ] ) ) {
                    $intersected_ids[] = $post_id;
                }
            }

            $post_ids = $intersected_ids;
        }

        $post_ids = apply_filters( 'facetwp_filtered_post_ids', array_values( $post_ids ), $this );

        // Store the final post IDs (after facet filtering has been applied)
        FWP()->filtered_post_ids = $post_ids;

        // Have any facets applied changes?
        FWP()->is_filtered = ( FWP()->unfiltered_post_ids !== $post_ids );

        // Have any hooks modified the unfiltered post IDs?
        FWP()->is_modified = ( FWP()->unfiltered_post_ids !== $query->posts );

        // Return a zero array if no matches
        return empty( $post_ids ) ? [ 0 ] : $post_ids;
    }


    /**
     * Run the template display code
     * @return string (HTML)
     */
    function get_template_html() {
        global $post, $wp_query;

        $output = apply_filters( 'facetwp_template_html', false, $this );

        if ( false === $output ) {
            ob_start();

            // Preserve globals
            $temp_post = is_object( $post ) ? clone $post : $post;
            $temp_wp_query = is_object( $wp_query ) ? clone $wp_query : $wp_query;

            $query = $this->query;
            $wp_query = $query; // Make $query->blah() optional

            if ( isset( $this->template['modes'] ) && 'visual' == $this->template['modes']['display'] ) {
                echo FWP()->builder->render_layout( $this->template['layout'] );
            }
            else {

                // Remove UTF-8 non-breaking spaces
                $display_code = $this->template['template'];
                $display_code = preg_replace( "/\xC2\xA0/", ' ', $display_code );
                eval( '?>' . $display_code );
            }

            // Reset globals
            $post = $temp_post;
            $wp_query = $temp_wp_query;

            // Store buffered output
            $output = ob_get_clean();
        }

        $output = preg_replace( "/\xC2\xA0/", ' ', $output );
        return $output;
    }


    /**
     * Result count (1-10 of 234)
     * @param array $params An array with "page", "per_page", and "total_rows"
     * @return string
     */
    function get_result_count( $params = [] ) {
        $text_of = __( 'of', 'fwp-front' );

        $page = (int) $params['page'];
        $per_page = (int) $params['per_page'];
        $total_rows = (int) $params['total_rows'];

        if ( $per_page < $total_rows ) {
            $lower = ( 1 + ( ( $page - 1 ) * $per_page ) );
            $upper = ( $page * $per_page );
            $upper = ( $total_rows < $upper ) ? $total_rows : $upper;
            $output = "$lower-$upper $text_of $total_rows";
        }
        else {
            $lower = ( 0 < $total_rows ) ? 1 : 0;
            $upper = $total_rows;
            $output = $total_rows;
        }

        return apply_filters( 'facetwp_result_count', $output, [
            'lower' => $lower,
            'upper' => $upper,
            'total' => $total_rows,
        ] );
    }


    /**
     * Pagination
     * @param array $params An array with "page", "per_page", and "total_rows"
     * @return string
     */
    function paginate( $params = [] ) {
        $pager_class = FWP()->helper->facet_types['pager'];
        $pager_class->pager_args = $params;

        $output = $pager_class->render_numbers([
            'inner_size' => 2,
            'dots_label' => '…',
            'prev_label' => '&lt;&lt;',
            'next_label' => '&gt;&gt;',
        ]);

        return apply_filters( 'facetwp_pager_html', $output, $params );
    }


    /**
     * "Per Page" dropdown box
     * @return string
     */
    function get_per_page_box() {
        $pager_class = FWP()->helper->facet_types['pager'];
        $pager_class->pager_args = $this->pager_args;

        $options = apply_filters( 'facetwp_per_page_options', [ 10, 25, 50, 100 ] );

        $output = $pager_class->render_per_page([
            'default_label' => __( 'Per page', 'fwp-front' ),
            'per_page_options' => implode( ',', $options )
        ]);

        return apply_filters( 'facetwp_per_page_html', $output, [
            'options' => $options
        ] );
    }


    /**
     * Get debug info for the browser console
     * @since 3.5.7
     */
    function get_debug_info() {
        $last_indexed = get_option( 'facetwp_last_indexed' );
        $last_indexed = $last_indexed ? human_time_diff( $last_indexed ) : 'never';

        $debug = [
            'query_args'    => $this->query_args,
            'sql'           => $this->query->request,
            'facets'        => $this->facets,
            'template'      => $this->template,
            'settings'      => FWP()->helper->settings['settings'],
            'last_indexed'  => $last_indexed,
            'row_counts'    => FWP()->helper->get_row_counts(),
            'hooks_used'    => $this->get_hooks_used(),
            'post_types'    => FWP()->helper->get_indexable_types()
        ];

        // Reduce debug payload
        if ( ! empty( $this->query_args['post__in'] ) ) {
            $debug['query_args']['post__in_count'] = count( $this->query_args['post__in'] );
            $debug['query_args']['post__in'] = array_slice( $this->query_args['post__in'], 0, 10 );

            $debug['sql'] = preg_replace_callback( '/posts.ID IN \((.*?)\)/s', function( $matches ) {
                $count = substr_count( $matches[1], ',' ) + 1;
                return ( $count <= 10 ) ? $matches[0] : "posts.ID IN (<$count IDs>)";
            }, $debug['sql'] );
        }

        return $debug;
    }


    /**
     * Display the location of relevant hooks (for Debug Mode)
     * @since 3.5.7
     */
    function get_hooks_used() {
        $relevant_hooks = [];

        $match_tags = apply_filters( 'facetwp_debug_hooks', [
            'pre_get_posts',
            'posts_results'
        ]);

        foreach ( $GLOBALS['wp_filter'] as $tag => $hook_data ) {
            if ( 0 === strpos( $tag, 'facetwp' ) || in_array( $tag, $match_tags ) ) {
                foreach ( $hook_data->callbacks as $callbacks ) {
                    foreach ( $callbacks as $cb ) {
                        if ( is_string( $cb['function'] ) && false !== strpos( $cb['function'], '::' ) ) {
                            $cb['function'] = explode( '::', $cb['function'] );
                        }

                        if ( is_array( $cb['function'] ) ) {
                            $class = is_object( $cb['function'][0] ) ? get_class( $cb['function'][0] ) : $cb['function'][0];
                            $ref = new ReflectionMethod( $class, $cb['function'][1] );
                        }
                        elseif ( is_object( $cb['function'] ) ) {
                            if ( is_a( $cb['function'], 'Closure' ) ) {
                                $ref = new ReflectionFunction( $cb['function'] );
                            }
                            else {
                                $class = get_class( $cb['function'] );
                                $ref = new ReflectionMethod( $class, '__invoke' );
                            }
                        }
                        else {
                            $ref = new ReflectionFunction( $cb['function'] );
                        }

                        $filename = str_replace( ABSPATH, '', $ref->getFileName() );

                        // ignore built-in hooks
                        if ( false === strpos( $filename, 'plugins/facetwp' ) ) {
                            if ( false !== strpos( $filename, 'wp-content' ) || false !== strpos( $tag, 'facetwp' ) ) {
                                $relevant_hooks[ $tag ][] = $filename . ':' . $ref->getStartLine();
                            }
                        }
                    }
                }
            }
        }

        return $relevant_hooks;
    }
}
