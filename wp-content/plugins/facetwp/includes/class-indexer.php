<?php

class FacetWP_Indexer
{

    /* (boolean) wp_insert_post running? */
    public $is_saving = false;

    /* (boolean) Whether to index a single post */
    public $index_all = false;

    /* (int) Number of posts to index before updating progress */
    public $chunk_size = 10;

    /* (string) Whether a temporary table is active */
    public $table;

    /* (array) Facet properties for the value being indexed */
    public $facet;

    /* (array) Value modifiers set via the admin UI */
    public $modifiers;

    /* (bool) Whether indexing hooks are in use */
    public $is_overridden;


    function __construct() {
        $this->set_table( 'auto' );

        add_action( 'facetwp_indexer_cron', [ $this, 'get_progress' ] );

        $this->run_cron();

        if ( apply_filters( 'facetwp_indexer_is_enabled', true ) ) {
            $this->run_hooks();
        }
    }


    /**
     * Event listeners
     * @since 2.8.4
     */
    function run_hooks() {
        add_action( 'save_post',                [ $this, 'save_post' ] );
        add_action( 'delete_post',              [ $this, 'delete_post' ] );
        add_action( 'edited_term',              [ $this, 'edit_term' ], 10, 3 );
        add_action( 'delete_term',              [ $this, 'delete_term' ], 10, 3 );
        add_action( 'set_object_terms',         [ $this, 'set_object_terms' ] );
        add_filter( 'wp_insert_post_parent',    [ $this, 'is_wp_insert_post' ] );
    }


    /**
     * Cron task
     * @since 2.8.5
     */
    function run_cron() {
        if ( ! wp_next_scheduled( 'facetwp_indexer_cron' ) ) {
            wp_schedule_single_event( time() + 300, 'facetwp_indexer_cron' );
        }
    }


    /**
     * Update the index when posts get saved
     * @since 0.1.0
     */
    function save_post( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( false !== wp_is_post_revision( $post_id ) ) {
            return;
        }

        if ( 'auto-draft' == get_post_status( $post_id ) ) {
            return;
        }
        
        // late check to disable indexing after __construct is already loaded
        if ( !apply_filters( 'facetwp_indexer_is_enabled', true ) ) {
            return;
        }

        $this->index( $post_id );
        $this->is_saving = false;
    }


    /**
     * Update the index when posts get deleted
     * @since 0.6.0
     */
    function delete_post( $post_id ) {
        global $wpdb;

        $wpdb->query( "DELETE FROM {$this->table} WHERE post_id = $post_id" );
    }


    /**
     * Update the index when terms get saved
     * @since 0.6.0
     */
    function edit_term( $term_id, $tt_id, $taxonomy ) {
        global $wpdb;

        $term = get_term( $term_id, $taxonomy );
        $slug = FWP()->helper->safe_value( $term->slug );
        $matches = FWP()->helper->get_facets_by( 'source', "tax/$taxonomy" );

        if ( ! empty( $matches ) ) {
            $facet_names = wp_list_pluck( $matches, 'name' );
            $facet_names = implode( "','", esc_sql( $facet_names ) );

            $wpdb->query( $wpdb->prepare( "
                UPDATE {$this->table}
                SET facet_value = %s, facet_display_value = %s
                WHERE facet_name IN ('$facet_names') AND term_id = %d",
                $slug, $term->name, $term_id
            ) );
        }
    }


    /**
     * Update the index when terms get deleted
     * @since 0.6.0
     */
    function delete_term( $term_id, $tt_id, $taxonomy ) {
        global $wpdb;

        $matches = FWP()->helper->get_facets_by( 'source', "tax/$taxonomy" );

        if ( ! empty( $matches ) ) {
            $facet_names = wp_list_pluck( $matches, 'name' );
            $facet_names = implode( "','", esc_sql( $facet_names ) );

            $wpdb->query( "
                DELETE FROM {$this->table}
                WHERE facet_name IN ('$facet_names') AND term_id = $term_id"
            );
        }
    }


    /**
     * We're hijacking wp_insert_post_parent
     * Prevent our set_object_terms() hook from firing within wp_insert_post
     * @since 2.2.2
     */
    function is_wp_insert_post( $post_parent ) {
        $this->is_saving = true;
        return $post_parent;
    }


    /**
     * Support for manual taxonomy associations
     * @since 0.8.0
     */
    function set_object_terms( $object_id ) {
        // late check to disable indexing after __construct is already loaded
        if ( ! $this->is_saving && apply_filters( 'facetwp_indexer_is_enabled', true )  ) {
            $this->index( $object_id );
        }
    }


    /**
     * Rebuild the facet index
     * @param mixed $post_id The post ID (set to FALSE to re-index everything)
     */
    function index( $post_id = false ) {
        global $wpdb;

        $this->index_all = ( false === $post_id );

        // Index everything
        if ( $this->index_all ) {

            // Store the pre-index settings (see FacetWP_Diff)
            update_option( 'facetwp_settings_last_index', get_option( 'facetwp_settings' ), 'no' );

            // PHP sessions are blocking, so close if active
            if ( PHP_SESSION_ACTIVE === session_status() ) {
                session_write_close();
            }

            // Bypass the PHP timeout
            ini_set( 'max_execution_time', 0 );

            // Prevent multiple indexing processes
            $touch = (int) $this->get_transient( 'touch' );

            if ( 0 < $touch ) {
                // Run only if the indexer is inactive or stalled
                if ( ( time() - $touch ) < 60 ) {
                    exit;
                }
            }
            else {
                // Create temp table
                $this->manage_temp_table( 'create' );
            }
        }
        // Index a single post
        elseif ( is_int( $post_id ) ) {

            // Clear table values
            $wpdb->query( "DELETE FROM {$this->table} WHERE post_id = $post_id" );
        }
        // Exit
        else {
            return;
        }

        // Resume indexing?
        $offset = (int) ( $_POST['facetwp_offset'] ?? 0 );
        $attempt = (int) ( $_POST['facetwp_retries'] ?? 0 );

        if ( 0 < $offset ) {
            $post_ids = json_decode( get_option( 'facetwp_indexing' ), true );
        }
        else {
            $post_ids = $this->get_post_ids_to_index( $post_id );

            // Store post IDs
            if ( $this->index_all ) {
                update_option( 'facetwp_indexing', json_encode( $post_ids ) );
            }
        }

        // Count total posts
        $num_total = count( $post_ids );

        // Get all facet sources
        $facets = FWP()->helper->get_facets();

        // Populate an array of facet value modifiers
        $this->load_value_modifiers( $facets );

        foreach ( $post_ids as $counter => $post_id ) {

            // Advance until we reach the offset
            if ( $counter < $offset ) {
                continue;
            }

            // Update the progress bar
            if ( $this->index_all ) {
                if ( 0 == ( $counter % $this->chunk_size ) ) {
                    $num_retries = (int) $this->get_transient( 'retries' );

                    // Exit if newer retries exist
                    if ( $attempt < $num_retries ) {
                        exit;
                    }

                    // Exit if the indexer was cancelled
                    wp_cache_delete( 'facetwp_indexing_cancelled', 'options' );

                    if ( 'yes' === get_option( 'facetwp_indexing_cancelled', 'no' ) ) {
                        update_option( 'facetwp_indexing_data', '' );
                        update_option( 'facetwp_indexing', '' );
                        $this->manage_temp_table( 'delete' );
                        exit;
                    }

                    $transients = [
                        'num_indexed'   => $counter,
                        'num_total'     => $num_total,
                        'retries'       => $attempt,
                        'touch'         => time(),
                    ];
                    update_option( 'facetwp_indexing_data', json_encode( $transients ) );
                }
            }

            // If the indexer stalled, start from the last valid chunk
            if ( 0 < $offset && ( $counter - $offset < $this->chunk_size ) ) {
                $wpdb->query( "DELETE FROM {$this->table} WHERE post_id = $post_id" );
            }

            $this->index_post( $post_id, $facets );
        }

        // Indexing complete
        if ( $this->index_all ) {
            update_option( 'facetwp_last_indexed', time(), 'no' );
            update_option( 'facetwp_indexing_data', '', 'no' );
            update_option( 'facetwp_indexing', '', 'no' );

            $this->manage_temp_table( 'replace' );
            $this->manage_temp_table( 'delete' );
        }

        do_action( 'facetwp_indexer_complete' );
    }


    /**
     * Get the array of indexer query args
     * @since 4.1.8
     */
    function get_query_args( $post_id = false ) {
        $post_types = get_post_types( [
            'exclude_from_search' => false
        ] );

        $args = [
            'post_type'         => $post_types,
            'post_status'       => 'publish',
            'posts_per_page'    => -1,
            'fields'            => 'ids',
            'orderby'           => 'ID',
            'cache_results'     => false,
            'no_found_rows'     => true,
            'ignore_custom_sort' => true,
            'suppress_filters'	=> true
        ];

        if ( is_int( $post_id ) ) {
            $args['p'] = $post_id;
            $args['posts_per_page'] = 1;
        }

        return apply_filters( 'facetwp_indexer_query_args', $args );
    }


    /**
     * Get an array of post IDs to index
     * @since 3.6.8
     */
    function get_post_ids_to_index( $post_id = false ) {
        $args = $this->get_query_args( $post_id );
        $query = new WP_Query( $args );
        return (array) $query->posts;
    }


    /**
     * Index an individual post
     * @since 3.6.8
     */
    function index_post( $post_id, $facets ) {

        // Force WPML to change the language
        do_action( 'facetwp_indexer_post', [ 'post_id' => $post_id ] );

        // Loop through all facets
        foreach ( $facets as $facet ) {

            // Do not index search facets
            if ( 'search' == $facet['type'] ) {
                continue;
            }

            $this->facet = $facet;
            $source = $facet['source'] ?? '';

            // Set default index_row() params
            $defaults = [
                'post_id'               => $post_id,
                'facet_name'            => $facet['name'],
                'facet_source'          => $source,
                'facet_value'           => '',
                'facet_display_value'   => '',
                'term_id'               => 0,
                'parent_id'             => 0,
                'depth'                 => 0,
                'variation_id'          => 0,
            ];

            $defaults = apply_filters( 'facetwp_indexer_post_facet_defaults', $defaults, [
                'facet' => $facet
            ] );

            // Set flag for custom handling
            $this->is_overridden = true;

            // Bypass default indexing
            $bypass = apply_filters( 'facetwp_indexer_post_facet', false, [
                'defaults'  => $defaults,
                'facet'     => $facet
            ] );

            if ( $bypass ) {
                continue;
            }

            $this->is_overridden = false;

            // Get rows to insert
            $rows = $this->get_row_data( $defaults );

            foreach ( $rows as $row ) {
                $this->index_row( $row );
            }
        }
    }


    /**
     * Get data for a table row
     * @since 2.1.1
     */
    function get_row_data( $defaults ) {
        $output = [];

        $facet = $this->facet;
        $post_id = $defaults['post_id'];
        $source = $defaults['facet_source'];

        if ( 'tax/' == substr( $source, 0, 4 ) ) {
            $used_terms = [];
            $taxonomy = substr( $source, 4 );
            $term_objects = wp_get_object_terms( $post_id, $taxonomy );
            if ( is_wp_error( $term_objects ) ) {
                return $output;
            }

            // Store the term depths
            $hierarchy = FWP()->helper->get_term_depths( $taxonomy );

            // Only index child terms
            $children = false;
            if ( ! empty( $facet['parent_term'] ) ) {
                $children = get_term_children( $facet['parent_term'], $taxonomy );
            }

            foreach ( $term_objects as $term ) {

                // If "parent_term" is set, only index children
                if ( false !== $children && ! in_array( $term->term_id, $children ) ) {
                    continue;
                }

                // Prevent duplicate terms
                if ( isset( $used_terms[ $term->term_id ] ) ) {
                    continue;
                }
                $used_terms[ $term->term_id ] = true;

                // Handle hierarchical taxonomies
                $term_info = $hierarchy[ $term->term_id ];
                $depth = $term_info['depth'];

                // Adjust depth if parent_term is set
                if ( ! empty( $facet['parent_term'] ) ) {
                    if ( isset( $hierarchy[ $facet['parent_term'] ] ) ) {
                        $anchor = (int) $hierarchy[ $facet['parent_term'] ]['depth'] + 1;
                        $depth = ( $depth - $anchor );
                    }
                }

                $params = $defaults;
                $params['facet_value'] = $term->slug;
                $params['facet_display_value'] = $term->name;
                $params['term_id'] = $term->term_id;
                $params['parent_id'] = $term_info['parent_id'];
                $params['depth'] = $depth;
                $output[] = $params;

                // Automatically index implicit parents
                if ( 'hierarchy' == $facet['type'] || FWP()->helper->facet_is( $facet, 'hierarchical', 'yes' ) ) {
                    while ( $depth > 0 ) {
                        $term_id = $term_info['parent_id'];
                        $term_info = $hierarchy[ $term_id ];
                        $depth = $depth - 1;

                        if ( ! isset( $used_terms[ $term_id ] ) ) {
                            $used_terms[ $term_id ] = true;

                            $params = $defaults;
                            $params['facet_value'] = $term_info['slug'];
                            $params['facet_display_value'] = $term_info['name'];
                            $params['term_id'] = $term_id;
                            $params['parent_id'] = $term_info['parent_id'];
                            $params['depth'] = $depth;
                            $output[] = $params;
                        }
                    }
                }
            }
        }
        elseif ( 'cf/' == substr( $source, 0, 3 ) ) {
            $source_noprefix = substr( $source, 3 );
            $values = get_post_meta( $post_id, $source_noprefix, false );
            foreach ( $values as $value ) {
                $params = $defaults;
                $params['facet_value'] = $value;
                $params['facet_display_value'] = $value;
                $output[] = $params;
            }
        }
        elseif ( 'post' == substr( $source, 0, 4 ) ) {
            $post = get_post( $post_id );
            $value = $post->{$source};
            $display_value = $value;
            if ( 'post_author' == $source ) {
                $user = get_user_by( 'id', $value );
                $display_value = ( $user instanceof WP_User ) ? $user->display_name : $value;
            }
            elseif ( 'post_type' == $source ) {
                $post_type = get_post_type_object( $value );
                if ( isset( $post_type->labels->name ) ) {
                    $display_value = $post_type->labels->name;
                }
            }

            $params = $defaults;
            $params['facet_value'] = $value;
            $params['facet_display_value'] = $display_value;
            $output[] = $params;
        }

        return apply_filters( 'facetwp_indexer_row_data', $output, [
            'defaults'  => $defaults,
            'facet'     => $this->facet
        ] );
    }


    /**
     * Index a facet value
     * @since 0.6.0
     */
    function index_row( $params ) {

        // Allow for custom indexing
        $params = apply_filters( 'facetwp_index_row', $params, $this );

        // Allow hooks to bypass the row insertion
        if ( is_array( $params ) ) {
            $this->insert( $params );
        }
    }


    /**
     * Save a facet value to DB
     * This can be trigged by "facetwp_index_row" to handle multiple values
     * @since 1.2.5
     */
    function insert( $params ) {
        global $wpdb;

        $value = $params['facet_value'];
        $display_value = $params['facet_display_value'];

        // Only accept scalar values
        if ( '' === $value || ! is_scalar( $value ) ) {
            return;
        }

        // Apply UI-based modifiers
        if ( isset( $this->modifiers[ $params['facet_name'] ] ) ) {
            $mod = $this->modifiers[ $params['facet_name' ] ];
            $is_match = in_array( $display_value, $mod['values'] );

            if ( ( 'exclude' == $mod['type'] && $is_match ) || ( 'include' == $mod['type'] && ! $is_match ) ) {
                return;
            }
        }

        $wpdb->query( $wpdb->prepare( "INSERT INTO {$this->table}
            (post_id, facet_name, facet_value, facet_display_value, term_id, parent_id, depth, variation_id) VALUES (%d, %s, %s, %s, %d, %d, %d, %d)",
            $params['post_id'],
            $params['facet_name'],
            FWP()->helper->safe_value( $value ),
            $display_value,
            $params['term_id'],
            $params['parent_id'],
            $params['depth'],
            $params['variation_id']
        ) );
    }


    /**
     * Get the indexing completion percentage
     * @return mixed The decimal percentage, or -1
     * @since 0.1.0
     */
    function get_progress() {
        $return = -1;
        $num_indexed = (int) $this->get_transient( 'num_indexed' );
        $num_total = (int) $this->get_transient( 'num_total' );
        $retries = (int) $this->get_transient( 'retries' );
        $touch = (int) $this->get_transient( 'touch' );

        if ( 0 < $num_total ) {

            // Resume a stalled indexer
            if ( 60 < ( time() - $touch ) ) {
                $post_data = [
                    'blocking'  => false,
                    'timeout'   => 0.02,
                    'body'      => [
                        'action'    => 'facetwp_resume_index',
                        'facetwp_offset'    => $num_indexed,
                        'facetwp_retries'   => $retries + 1,
                        'touch'     => $touch
                    ]
                ];
                wp_remote_post( admin_url( 'admin-ajax.php' ), $post_data );
            }

            // Calculate the percent completion
            if ( $num_indexed != $num_total ) {
                $return = round( 100 * ( $num_indexed / $num_total ), 2 );
            }
        }

        return $return;
    }


    /**
     * Get indexer transient variables
     * @since 1.7.8
     */
    function get_transient( $name = false ) {
        $transients = get_option( 'facetwp_indexing_data' );

        if ( ! empty( $transients ) ) {
            $transients = json_decode( $transients, true );
            if ( $name ) {
                return $transients[ $name ] ?? false;
            }

            return $transients;
        }

        return false;
    }


    /**
     * Set either the index or temp table
     * @param string $table 'auto', 'index', or 'temp'
     * @since 4.1.4
     */
    function set_table( $table = 'auto' ) {
        global $wpdb;

        if ( 'auto' == $table ) {
            $table = ( '' == get_option( 'facetwp_indexing', '' ) ) ? 'index' : 'temp';
        }

        $this->table = $wpdb->prefix . 'facetwp_' . $table;
    }


    /**
     * Index table management
     * @since 3.5
     */
    function manage_temp_table( $action = 'create' ) {
        global $wpdb;

        $table = $wpdb->prefix . 'facetwp_index';
        $temp_table = $wpdb->prefix . 'facetwp_temp';

        if ( 'create' == $action ) {
            $wpdb->query( "CREATE TABLE $temp_table LIKE $table" );
            $this->set_table( 'temp' );
        }
        elseif ( 'replace' == $action ) {
            $wpdb->query( "TRUNCATE TABLE $table" );
            $wpdb->query( "INSERT INTO $table SELECT * FROM $temp_table" );
        }
        elseif ( 'delete' == $action ) {
            $wpdb->query( "DROP TABLE IF EXISTS $temp_table" );
            $this->set_table( 'index' );
        }
    }


    /**
     * Populate an array of facet value modifiers (defined in the admin UI)
     * @since 3.5.6
     */
    function load_value_modifiers( $facets ) {
        $output = [];

        foreach ( $facets as $facet ) {
            $name = $facet['name'];
            $type = empty( $facet['modifier_type'] ) ? 'off' : $facet['modifier_type'];

            if ( 'include' == $type || 'exclude' == $type ) {
                $temp = preg_split( '/\r\n|\r|\n/', trim( $facet['modifier_values'] ) );
                $values = [];

                // Compare using both original and encoded values
                foreach ( $temp as $val ) {
                    $val = trim( $val );
                    if ( empty( $val ) ) {
                        continue;
                    }
                    $val_encoded = htmlentities( $val );
                    $val_decoded = html_entity_decode( $val );
                    $values[ $val ] = true;
                    $values[ $val_encoded ] = true;
                    $values[ $val_decoded ] = true;
                }

                if ( ! empty( $values) ) {
                    $output[ $name ] = [ 'type' => $type, 'values' => array_keys( $values ) ];
                }
            }
        }

        $this->modifiers = $output;
    }
}
