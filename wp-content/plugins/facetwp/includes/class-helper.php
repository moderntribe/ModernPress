<?php

final class FacetWP_Helper
{

    /* (array) The facetwp_settings option (after hooks) */
    public $settings;

    /* (array) Associative array of facet objects */
    public $facet_types;

    /* (array) Cached data sources */
    public $data_sources;

    /* (array) Cached terms */
    public $term_cache;


    function __construct() {
        $this->facet_types = $this->get_facet_types();
        $this->settings = $this->load_settings();
    }


    /**
     * Parse the URL hostname
     */
    function get_http_host() {
        return parse_url( get_option( 'home' ), PHP_URL_HOST );
    }


    /**
     * Get the current page URI
     */
    function get_uri() {
        if ( isset( FWP()->facet->http_params ) ) {
            return FWP()->facet->http_params['uri'];
        }

        $uri = parse_url( $_SERVER['REQUEST_URI'] );
        return isset( $uri['path'] ) ? trim( $uri['path'], '/' ) : '';
    }


    /**
     * Get available facet types     * 
     * @since 4.4 Added Map type facet
     */
    function get_facet_types() {
        if ( ! empty( $this->facet_types ) ) {
            return $this->facet_types;
        }

        include( FACETWP_DIR . '/includes/facets/base.php' );

        $types = [
            'checkboxes'    => 'Facetwp_Facet_Checkboxes',
            'dropdown'      => 'Facetwp_Facet_Dropdown',
            'radio'         => 'Facetwp_Facet_Radio_Core',
            'fselect'       => 'Facetwp_Facet_fSelect',
            'hierarchy'     => 'Facetwp_Facet_Hierarchy',
            'slider'        => 'Facetwp_Facet_Slider',
            'search'        => 'Facetwp_Facet_Search',
            'autocomplete'  => 'Facetwp_Facet_Autocomplete',
            'date_range'    => 'Facetwp_Facet_Date_Range',
            'number_range'  => 'Facetwp_Facet_Number_Range',
            'rating'        => 'FacetWP_Facet_Rating',
            'proximity'     => 'Facetwp_Facet_Proximity_Core',
            'map'           => 'Facetwp_Facet_Map',
            'pager'         => 'FacetWP_Facet_Pager',
            'reset'         => 'FacetWP_Facet_Reset',
            'sort'          => 'FacetWP_Facet_Sort'
        ];

        $facet_types = [];

        foreach ( $types as $slug => $class_name ) {
            include( FACETWP_DIR . "/includes/facets/$slug.php" );
            $facet_types[ $slug ] = new $class_name();
        }

        return apply_filters( 'facetwp_facet_types', $facet_types );
    }


    /**
     * Get settings and allow for developer hooks
     */
    function load_settings( $last_index = false ) {
        $name = $last_index ? 'facetwp_settings_last_index' : 'facetwp_settings';
        $option = get_option( $name );

        $defaults = [
            'facets' => [],
            'templates' => [],
            'settings' => [
                'thousands_separator' => ',',
                'decimal_separator' => '.',
                'prefix' => '_',
                'load_jquery' => 'no'
            ]
        ];

        $settings = ( false !== $option ) ? json_decode( $option, true ) : [];
        $settings = array_merge( $defaults, $settings );
        $settings['settings'] = array_merge( $defaults['settings'], $settings['settings'] );

        // Store DB-based facet & template names
        $db_names = [];

        foreach ( $settings['facets'] as $facet ) {
            $db_names[ 'facet-' . $facet['name'] ] = true;
        }

        foreach ( $settings['templates'] as $template ) {
            $db_names[ 'template-' . $template['name'] ] = true;
        }

        // Programmatically registered
        $facets = apply_filters( 'facetwp_facets', $settings['facets'] );
        $templates = apply_filters( 'facetwp_templates', $settings['templates'] );

        $tmp_facets = [];
        $tmp_templates = [];

        // Merge DB + code-based facets
        foreach ( $facets as $facet ) {
            $name = $facet['name'];
            $is_db_based = isset( $db_names[ "facet-$name" ] );

            if ( ! $is_db_based ) {
                $facet['_code'] = true;
            }

            if ( ! $is_db_based || empty( $tmp_facets[ $name ] ) ) {

                // Valid facet type?
                if ( in_array( $facet['type'], array_keys( $this->facet_types ) ) ) {
                    $defaults = $this->facet_types[ $facet['type'] ]->field_defaults ?? [];
                    $facet = array_merge( $defaults, $facet );
                    $tmp_facets[ $name ] = $facet;
                }
            }
        }

        // Merge DB + code-based templates
        foreach ( $templates as $template ) {
            $name = $template['name'];
            $is_db_based = isset( $db_names[ "template-$name" ] );

            if ( ! $is_db_based ) {
                $template['_code'] = true;
            }

            if ( ! $is_db_based || empty( $tmp_templates[ $name ] ) ) {
                $tmp_templates[ $name ] = $template;
            }
        }

        // Convert back to numerical arrays
        $settings['facets'] = array_values( $tmp_facets );
        $settings['templates'] = array_values( $tmp_templates );

        // Filtered settings
        return $settings;
    }


    /**
     * Get a general setting value
     *
     * @param string $name The setting name
     * @param mixed $default The default value
     * @since 1.9
     */
    function get_setting( $name, $default = '' ) {
        return $this->settings['settings'][ $name ] ?? $default;
    }


    /**
     * Get an array of all facets
     * @return array
     */
    function get_facets() {
        return $this->settings['facets'];
    }


    /**
     * Get an array of all templates
     * @return array
     */
    function get_templates() {
        return $this->settings['templates'];
    }


    /**
     * Get all properties for a single facet
     * @param string $facet_name
     * @return mixed An array of facet info, or false
     */
    function get_facet_by_name( $facet_name ) {
        foreach ( $this->get_facets() as $facet ) {
            if ( $facet_name == $facet['name'] ) {
                return $facet;
            }
        }

        return false;
    }


    /**
     * Get all properties for a single template
     *
     * @param string $template_name
     * @return mixed An array of template info, or false
     */
    function get_template_by_name( $template_name ) {
        foreach ( $this->get_templates() as $template ) {
            if ( $template_name == $template['name'] ) {
                return $template;
            }
        }

        return false;
    }


    /**
     * Fetch facets using one of its settings
     * @param string $setting_name
     * @param mixed $setting_value
     * @return array
     */
    function get_facets_by( $setting, $value ) {
        $matches = [];

        foreach ( $this->get_facets() as $facet ) {
            if ( isset( $facet[ $setting ] ) && $value === $facet[ $setting ] ) {
                $matches[] = $facet;
            }
        }

        return $matches;
    }


    /**
     * Fetch facets by data source type (prefix, ie tax, cf, acf ...)
     * @param string $value
     * @return array
     * @since 4.4
     */
    function get_facets_by_datasource_type( $value ) {
        $matches = [];

        foreach ( $this->get_facets() as $facet ) {
            if ( isset( $facet[ 'source' ] ) && false !== strpos( $facet['source'], $value ) ) {
                $matches[] = $facet;
            }
        }

        return $matches;
    }


    /**
     * Get terms across all languages (thanks, WPML)
     * @since 3.8.5
     */
    function get_terms( $taxonomy ) {
        global $wpdb;

        $sql = "
        SELECT t.term_id, t.name, t.slug, tt.parent FROM {$wpdb->term_taxonomy} tt
        INNER JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
        WHERE tt.taxonomy = %s";

        return $wpdb->get_results( $wpdb->prepare( $sql, $taxonomy ) );
    }


    /**
     * Get an array of term information, including depth
     * @param string $taxonomy The taxonomy name
     * @return array Term information
     * @since 0.9.0
     */
    function get_term_depths( $taxonomy ) {

        if ( isset( $this->term_cache[ $taxonomy ] ) ) {
            return $this->term_cache[ $taxonomy ];
        }

        $output = [];
        $parents = [];

        $terms = $this->get_terms( $taxonomy );

        // Get term parents
        foreach ( $terms as $term ) {
            $parents[ $term->term_id ] = $term->parent;
        }

        // Build the term array
        foreach ( $terms as $term ) {
            $output[ $term->term_id ] = [
                'term_id'       => $term->term_id,
                'name'          => $term->name,
                'slug'          => $term->slug,
                'parent_id'     => $term->parent,
                'depth'         => 0
            ];

            $current_parent = $term->parent;
            while ( 0 < (int) $current_parent ) {
                $current_parent = $parents[ $current_parent ];
                $output[ $term->term_id ]['depth']++;

                // Prevent an infinite loop
                if ( 50 < $output[ $term->term_id ]['depth'] ) {
                    break;
                }
            }
        }

        $this->term_cache[ $taxonomy ] = $output;

        return $output;
    }


    /**
     * Finish sorting the facet values
     * The results are already sorted by depth and (name OR count), we just need
     * to move the children directly below their parents
     */
    function sort_taxonomy_values( $values = [], $orderby = 'count' ) {
        $final = [];
        $cache = [];

        // Create an "order" sort value based on the top-level items
        foreach ( $values as $key => $val ) {
            if ( 0 == $val['depth'] ) {
                $val['order'] = $key;
                $cache[ $val['term_id'] ] = $key;
                $final[] = $val;
            }
            elseif ( isset( $cache[ $val['parent_id'] ] ) ) { // skip orphans
                $val['order'] = $cache[ $val['parent_id'] ] . ".$key"; // dot-separated hierarchy string
                $cache[ $val['term_id'] ] = $val['order'];
                $final[] = $val;
            }
        }

        // Sort the array based on the new "order" element
        // Since this is a dot-separated hierarchy string, use version_compare
        usort( $final, function( $a, $b ) {
            return version_compare( $a['order'], $b['order'] );
        });

        return $final;
    }


    /**
     * Sanitize SQL data
     * @return mixed The sanitized value(s)
     * @since 3.0.7
     */
    function sanitize( $input ) {
        global $wpdb;

        if ( is_array( $input ) ) {
            $output = [];

            foreach ( $input as $key => $val ) {
                $output[ $key ] = $this->sanitize( $val );
            }
        }
        else {
            // Use mysqli_real_escape_string if WP 6.4+ or $wpdb->use_mysqli is true
            // The $wpdb->use_mysqli property was removed in WP 6.4
            // Since the mysqli driver is now required
            if ( ! isset( $wpdb->use_mysqli ) || ( $wpdb->dbh && $wpdb->use_mysqli ) ) {
                $output = mysqli_real_escape_string( $wpdb->dbh, $input );
            }
            else {
                $output = addslashes( $input );
            }
        }

        return $output;
    }


    /**
     * Escape output data
     * @return mixed the escaped value(s)
     * @since 4.2.0
     */
    function escape( $input ) {
        if ( is_array( $input ) ) {
            $output = [];

            foreach ( $input as $key => $val ) {
                $output[ $key ] = $this->escape( $val );
            }
        }
        else {
            $output = htmlspecialchars( $input );
        }

        return $output;
    }


    /**
     * Does an active facet with the specified setting exist?
     * @return boolean
     * @since 1.4.0
     */
    function facet_setting_exists( $setting_name, $setting_value ) {
        foreach ( FWP()->facet->facets as $f ) {
            if ( isset( $f[ $setting_name ] ) && $f[ $setting_name ] == $setting_value ) {
                return true;
            }
        }

        return false;
    }


    /**
     * Does this facet have a setting with the specified value?
     * @return boolean
     * @since 2.3.4
     */
    function facet_is( $facet, $setting_name, $setting_value ) {
        if ( is_string( $facet ) ) {
            $facet = $this->get_facet_by_name( $facet );
        }

        if ( isset( $facet[ $setting_name ] ) && $facet[ $setting_name ] == $setting_value ) {
            return true;
        }

        return false;
    }


    /**
     * Hash a facet value if needed
     * @return string
     * @since 2.1
     */
    function safe_value( $value ) {
        $value = remove_accents( $value );

        if ( preg_match( '/[^a-z0-9_.\- ]/i', $value ) ) {
            if ( ! preg_match( '/^\d{4}-(0[1-9]|1[012])-([012]\d|3[01])/', $value ) ) {
                $value = md5( $value );
            }
        }

        $value = str_replace( ' ', '-', strtolower( $value ) );
        $value = preg_replace( '/[-]{2,}/', '-', $value );
        $value = ( 50 < strlen( $value ) ) ? substr( $value, 0, 50 ) : $value;
        return $value;
    }


    /**
     * Properly format numbers, taking separators into account
     * @return number
     * @since 2.7.5
     */
    function format_number( $num ) {
        $sep_decimal = $this->get_setting( 'decimal_separator' );
        $sep_thousands = $this->get_setting( 'thousands_separator' );

        $num = str_replace( $sep_thousands, '', $num );
        $num = ( ',' == $sep_decimal ) ? str_replace( ',', '.', $num ) : $num;
        $num = preg_replace( '/[^0-9-.]/', '', $num );

        return $num;
    }


    /**
     * Get facet data sources
     * @return array
     * @since 2.2.1
     */
    function get_data_sources( $context = 'default' ) {
        global $wpdb;

        // Cached?
        if ( ! empty( $this->data_sources ) ) {
            $sources = $this->data_sources;
        }
        else {

            // Get excluded meta keys
            $excluded_fields = apply_filters( 'facetwp_excluded_custom_fields', [
                '_edit_last',
                '_edit_lock',
            ] );

            // Get taxonomies
            $taxonomies = get_taxonomies( [], 'object' );

            // Get custom fields
            $meta_keys = $wpdb->get_col( "SELECT DISTINCT meta_key FROM {$wpdb->postmeta} ORDER BY meta_key" );
            $custom_fields = array_diff( $meta_keys, $excluded_fields );

            $sources = [
                'posts' => [
                    'label' => __( 'Posts', 'fwp' ),
                    'choices' => [
                        'post_type'         => __( 'Post Type', 'fwp' ),
                        'post_date'         => __( 'Post Date', 'fwp' ),
                        'post_modified'     => __( 'Post Modified', 'fwp' ),
                        'post_title'        => __( 'Post Title', 'fwp' ),
                        'post_author'       => __( 'Post Author', 'fwp' )
                    ],
                    'weight' => 10
                ],
                'taxonomies' => [
                    'label' => __( 'Taxonomies', 'fwp' ),
                    'choices' => [],
                    'weight' => 20
                ],
                'custom_fields' => [
                    'label' => __( 'Custom Fields', 'fwp' ),
                    'choices' => [],
                    'weight' => 30
                ]
            ];

            foreach ( $taxonomies as $tax ) {
                $sources['taxonomies']['choices'][ 'tax/' . $tax->name ] = $tax->labels->name . ' (' . $tax->name . ')';
            }

            foreach ( $custom_fields as $cf ) {
                if ( 0 !== strpos( $cf, '_oembed_' ) ) {
                    $sources['custom_fields']['choices'][ 'cf/' . $cf ] = $cf;
                }
            }

            $this->data_sources = $sources;
        }

        $sources = apply_filters( 'facetwp_facet_sources', $sources, $context );

        uasort( $sources, [ $this, 'sort_by_weight' ] );

        return $sources;
    }


    /**
     * Sort facetwp_facet_sources by weight
     * @since 2.7.5
     */
    function sort_by_weight( $a, $b ) {
        $a['weight'] = $a['weight'] ?? 10;
        $b['weight'] = $b['weight'] ?? 10;

        if ( $a['weight'] == $b['weight'] ) {
            return 0;
        }

        return ( $a['weight'] < $b['weight'] ) ? -1 : 1;
    }


    /**
     * Get row counts for all facets
     * @since 3.3.4
     */
    function get_row_counts() {
        global $wpdb;

        $output = [];
        $results = $wpdb->get_results( "SELECT facet_name, COUNT(*) AS row_count FROM {$wpdb->prefix}facetwp_index GROUP BY facet_name" );

        foreach ( $results as $result ) {
            $output[ $result->facet_name ] = (int) $result->row_count;
        }

        return $output;
    }

    /**
     * Get indexable post types
     */
    function get_indexable_types() {

        $args = FWP()->indexer->get_query_args();
        $types = (array) ( $args['post_type'] ?? 'post' );
        $statuses = (array) ( $args['post_status'] ?? 'publish' );

        if ( ! in_array( 'inherit', $statuses ) && in_array( 'attachment', $types ) ) {
            $types = array_values( array_diff( $types, [ 'attachment' ] ) );
        }

        sort( $types );

        return $types;

    }

    /**
     * Grab the license key
     * @since 3.0.3
     */
    function get_license_key() {
        $license_key = defined( 'FACETWP_LICENSE_KEY' ) ? FACETWP_LICENSE_KEY : get_option( 'facetwp_license' );
        $license_key = apply_filters( 'facetwp_license_key', $license_key );
        return sanitize_key( trim( $license_key ) );
    }


    /**
     * Determine whether the license is active
     * @since 3.3.0
     */
    function is_license_active() {
        return ( 'success' == $this->get_license_meta( 'status' ) );
    }


    /**
     * Get a license meta value
     * Possible keys: status, message, expiration, payment_id, price_id
     * @since 3.5.3
     */
    function get_license_meta( $key = 'status' ) {
        $activation = get_option( 'facetwp_activation' );

        if ( ! empty( $activation ) ) {
            $data = json_decode( $activation, true );

            if ( isset( $data[ $key ] ) ) {
                return $data[ $key ];
            }
        }

        return false;
    }
}
