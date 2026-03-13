<?php

class FacetWP_Facet_Proximity_Core extends FacetWP_Facet
{

    /* (array) Associative array containing post_id => distance */
    public $distance = [];

    /* (array) Associative array containing post_id => [lat, lng] */
    public $post_latlng = [];

    function __construct() {
        $this->label = __( 'Proximity', 'fwp' );
        $this->fields = [ 'longitude', 'unit', 'radius_ui', 'radius_options', 'radius_min', 'radius_max', 'radius_default', 'placeholder' ];

        add_filter( 'facetwp_index_row', [ $this, 'index_latlng' ], 1, 2 );
        add_filter( 'facetwp_sort_options', [ $this, 'sort_options' ], 1, 2 );
        add_filter( 'facetwp_filtered_post_ids', [ $this, 'sort_by_distance' ], 10, 2 );
        add_filter( 'facetwp_render_output', [ $this, 'add_places_version' ], 10, 2 );
    }

    /**
     * Get places version from setting, default to legacy version - places-service
     * Also can be set with filter
     * add_filter( 'facetwp_proximity_places_version', function($version) { return 'place-class'; } );
     * @since 4.4
     */
    function get_places_version() {           
        $places_version = FWP()->helper->get_setting( 'places_version', 'places-service' );        
        return apply_filters( 'facetwp_proximity_places_version', $places_version );
    }    
    
    /**
     * Add a places version as FWP.settings.places
     * @since 4.4
     */
    function add_places_version( $output, $params ) {        
        $output['settings']['places'] = $this->get_places_version();
        return $output;
    }

    /**
     * Generate the facet HTML
     */
    function render( $params ) {

        $output = '';
        $facet = $params['facet'];
        $value = $params['selected_values'];
        $unit = empty( $facet['unit'] ) ? 'mi' : $facet['unit'];
        $unit_display = 'km' == $facet['unit'] ? __( 'km', 'fwp-front' ) : ( 'mi' == $facet['unit'] ? __( 'mi', 'fwp-front' ) : $facet['unit'] );
        $placeholder = empty( $facet['placeholder'] ) ? __( 'Enter location', 'fwp-front' ) : $facet['placeholder'];
        $placeholder = facetwp_i18n( $placeholder );

        $lat = empty( $value[0] ) ? '' : $value[0];
        $lng = empty( $value[1] ) ? '' : $value[1];
        $chosen_radius = empty( $value[2] ) ? '' : (float) $value[2];
        $location_name = empty( $value[3] ) ? '' : urldecode( $value[3] );

        $radius_options = [ 10, 25, 50, 100, 250 ];

        // Grab the radius UI
        $radius_ui = empty( $facet['radius_ui'] ) ? 'dropdown' : $facet['radius_ui'];

        // Grab radius options from the UI
        if ( ! empty( $facet['radius_options'] ) ) {
            $radius_options = explode( ',', preg_replace( '/\s+/', '', $facet['radius_options'] ) );
        }

        // Grab default radius from the UI
        if ( empty( $chosen_radius ) && ! empty( $facet['radius_default'] ) ) {
            $chosen_radius = (float) $facet['radius_default'];
        }

        // Support dynamic radius
        if ( ! empty( $chosen_radius ) && 0 < $chosen_radius ) {
            if ( ! in_array( $chosen_radius, $radius_options ) ) {
                $radius_options[] = $chosen_radius;
            }
        }

        $radius_options = apply_filters( 'facetwp_proximity_radius_options', $radius_options );

        ob_start();
?>

        <span class="facetwp-input-wrap">
            <i class="facetwp-icon locate-me"></i>
            <input type="text" class="facetwp-location" value="<?php echo esc_attr( $location_name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" autocomplete="off" />
            <div class="location-results facetwp-hidden"></div>
        </span>

        <?php if ( 'dropdown' == $radius_ui ) : ?>

        <select class="facetwp-radius facetwp-radius-dropdown">
            <?php foreach ( $radius_options as $radius ) : ?>
            <?php $selected = ( $chosen_radius == $radius ) ? ' selected' : ''; ?>
            <option value="<?php echo $radius; ?>"<?php echo $selected; ?>><?php echo $radius . ' ' . facetwp_i18n( $unit_display ); ?></option>
            <?php endforeach; ?>
        </select>

        <?php elseif ( 'slider' == $radius_ui ) : ?>

        <div class="facetwp-radius-wrap">
            <input class="facetwp-radius facetwp-radius-slider" type="range"
                min="<?php echo $facet['radius_min']; ?>"
                max="<?php echo $facet['radius_max']; ?>"
                value="<?php echo $chosen_radius; ?>"
            />
            <div class="facetwp-radius-label">
                <span class="facetwp-radius-dist"><?php echo $chosen_radius; ?></span>
                <span class="facetwp-radius-unit"><?php echo facetwp_i18n( $unit_display ); ?></span>
            </div>
        </div>

        <?php elseif ( 'none' == $radius_ui ) : ?>

        <input class="facetwp-radius facetwp-hidden" value="<?php echo $chosen_radius; ?>" />

        <?php endif; ?>

        <input type="hidden" class="facetwp-lat" value="<?php echo esc_attr( $lat ); ?>" />
        <input type="hidden" class="facetwp-lng" value="<?php echo esc_attr( $lng ); ?>" />
<?php
        return ob_get_clean();
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $selected_values = $params['selected_values'];
        $unit = empty( $facet['unit'] ) ? 'mi' : $facet['unit'];
        $earth_radius = ( 'mi' == $unit ) ? 3959 : 6371;

        if ( empty( $selected_values ) || empty( $selected_values[0] ) ) {
            return 'continue';
        }

        $lat1 = (float) $selected_values[0];
        $lng1 = (float) $selected_values[1];
        $radius = (float) $selected_values[2];
        $rad = M_PI / 180;

        $sql = "
        SELECT DISTINCT post_id, facet_value AS `lat`, facet_display_value AS `lng`
        FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '{$facet['name']}'";

        $results = $wpdb->get_results( $sql );

        foreach ( $results as $row ) {
            $lat2 = (float) $row->lat;
            $lng2 = (float) $row->lng;

            if ( ( $lat1 == $lat2 ) && ( $lng1 == $lng2 ) ) {
                $dist = 0;
            }
            else {
                $calc = sin( $lat1 * $rad ) * sin( $lat2 * $rad ) +
                        cos( $lat1 * $rad ) * cos( $lat2 * $rad ) *
                        cos( $lng2 * $rad - $lng1 * $rad );

                // acos() must be between -1 and 1
                $dist = acos( max( -1, min( 1, $calc ) ) ) * $earth_radius;
            }

            if ( $dist <= $radius ) {
                $existing = $this->distance[ $row->post_id ] ?? -1;

                if ( -1 == $existing || $dist < $existing ) {
                    $this->distance[ $row->post_id ] = $dist;

                    if ( apply_filters( 'facetwp_proximity_store_latlng', false ) ) {
                        $this->post_latlng[ $row->post_id ] = [ $lat2, $lng2 ];
                    }
                }
            }
        }

        asort( $this->distance, SORT_NUMERIC );

        return array_keys( $this->distance );
    }


    /**
     * Output front-end scripts
     */
    function front_scripts() {
        if ( apply_filters( 'facetwp_proximity_load_js', true ) ) { // back compatibility for filter

            if ( version_compare( FACETWP_MAP_FACET_VERSION, '2.0', '<' ) || 'places-service' == $this->get_places_version() ) {

                // hard-coded
                $api_key = defined( 'GMAPS_API_KEY' ) ? GMAPS_API_KEY : '';

                // admin ui
                $tmp_key = FWP()->helper->get_setting( 'gmaps_api_key' );
                $api_key = empty( $tmp_key ) ? $api_key : $tmp_key;

                // hook
                $api_key = apply_filters( 'facetwp_gmaps_api_key', $api_key );

                FWP()->display->assets['gmaps'] = '//maps.googleapis.com/maps/api/js?libraries=places&key=' . trim( esc_attr( $api_key ) ) . '&callback=Function.prototype';

            } else {

                add_filter( 'facetwp_load_gmaps', '__return_true', 5 );

            }
        }

        // Pass extra options into Places Autocomplete
        $options = apply_filters( 'facetwp_proximity_autocomplete_options', [] );

        if ( 'place-class' == $this->get_places_version() ) {

            // Backward compatibility with documented legacy Places API options that don't exist in Places (New) Autocomplete
            if ( isset( $options['componentRestrictions'] ) ) {
                if ( isset( $options['componentRestrictions']['country'] ) ) {
                    $options['includedRegionCodes'] = $options['componentRestrictions']['country'];
                }
                unset( $options['componentRestrictions'] );
            }
            if ( isset( $options['types'] ) ) {
                $options['includedPrimaryTypes'] = $options['types'];
                unset( $options['types'] );
            }
            if ( isset( $options['bounds'] ) ) {
                $options['locationBias'] = $options['bounds'];
                unset( $options['bounds'] );
            }

        }


        FWP()->display->json['proximity']['autocomplete_options'] = $options;
        FWP()->display->json['proximity']['clearText'] = __( 'Clear location', 'fwp-front' );
        FWP()->display->json['proximity']['queryDelay'] = 250;
        FWP()->display->json['proximity']['minLength'] = 3;
    }


    function register_fields() {
        return [
            'longitude' => [
                'type' => 'alias',
                'items' => [
                    'source_other' => [
                        'label' => __( 'Longitude', 'fwp' ),
                        'notes' => '(Optional) use a separate longitude field',
                        'html' => '<data-sources :facet="facet" setting-name="source_other"></data-sources>'
                    ]
                ]
            ],
            'unit' => [
                'type' => 'select',
                'label' => __( 'Unit of measurement', 'fwp' ),
                'choices' => [
                    'mi' => __( 'Miles', 'fwp' ),
                    'km' => __( 'Kilometers', 'fwp' )
                ]
            ],
            'radius_ui' => [
                'type' => 'select',
                'label' => __( 'Radius UI', 'fwp' ),
                'choices' => [
                    'dropdown' => __( 'Dropdown', 'fwp' ),
                    'slider' => __( 'Slider', 'fwp' ),
                    'none' => __( 'None', 'fwp' )
                ]
            ],
            'radius_options' => [
                'label' => __( 'Radius options', 'fwp' ),
                'notes' => 'A comma-separated list of radius choices',
                'default' => '10, 25, 50, 100, 250',
                'show' => "facet.radius_ui == 'dropdown'"
            ],
            'radius_min' => [
                'label' => __( 'Range (min)', 'fwp' ),
                'default' => 1,
                'show' => "facet.radius_ui == 'slider'"
            ],
            'radius_max' => [
                'label' => __( 'Range (max)', 'fwp' ),
                'default' => 50,
                'show' => "facet.radius_ui == 'slider'"
            ],
            'radius_default' => [
                'label' => __( 'Default radius', 'fwp' ),
                'default' => 25
            ]
        ];
    }


    /**
     * Index the coordinates
     * We expect a comma-separated "latitude, longitude"
     */
    function index_latlng( $params, $class ) {

        $facet = FWP()->helper->get_facet_by_name( $params['facet_name'] );

        if ( false !== $facet && 'proximity' == $facet['type'] ) {
            $latlng = $params['facet_value'];

            // Only handle "lat, lng" strings
            if ( is_string( $latlng ) ) {
                $latlng = preg_replace( '/[^0-9.,-]/', '', $latlng );

                if ( ! empty( $facet['source_other'] ) ) {
                    $other_params = $params;
                    $other_params['facet_source'] = $facet['source_other'];
                    $rows = $class->get_row_data( $other_params );

                    if ( false === strpos( $latlng, ',' ) && ! empty( $rows ) ) {
                        $lng = $rows[0]['facet_display_value'];
                        $lng = preg_replace( '/[^0-9.,-]/', '', $lng );
                        $latlng .= ',' . $lng;
                    }
                }

                if ( preg_match( "/^([\d.-]+),([\d.-]+)$/", $latlng ) ) {
                    $latlng = explode( ',', $latlng );
                    $params['facet_value'] = $latlng[0];
                    $params['facet_display_value'] = $latlng[1];
                }

                /** make sure lat and lng are valid floats **/
                $params['facet_value'] = $params['facet_value'] == (float)$params['facet_value'] ? (float)$params['facet_value'] : '';
                $params['facet_display_value'] = $params['facet_display_value'] == (float)$params['facet_display_value'] ? (float)$params['facet_display_value'] : '';
    
                /** check for valid range of lat and lng */
                if  ( '' == $params['facet_value'] || '' == $params['facet_display_value'] || 90 < abs( $params['facet_value'] ) || 180 < abs( $params['facet_display_value'] ) ) {
                    $params['facet_value'] = ''; // don't index
                }
            }
        }

        return $params;
    }


    /**
     * Add "Distance" to the sort box
     */
    function sort_options( $options, $params ) {

        if ( FWP()->helper->facet_setting_exists( 'type', 'proximity' ) ) {
            $options['distance'] = [
                'label' => __( 'Distance', 'fwp-front' ),
                'query_args' => [
                    'orderby' => 'post__in',
                    'order' => 'ASC',
                ],
            ];
        }

        return $options;
    }


    /**
     * Sort the final (filtered) post IDs by distance
     */
    function sort_by_distance( $post_ids, $class ) {

        $distance = FWP()->helper->facet_types['proximity']->distance;

        if ( ! empty( $distance ) ) {
            $ordered_posts = array_keys( $distance );
            $filtered_posts = array_flip( $post_ids );
            $intersected_ids = [];

            foreach ( $ordered_posts as $p ) {
                if ( isset( $filtered_posts[ $p ] ) ) {
                    $intersected_ids[] = $p;
                }
            }

            $post_ids = $intersected_ids;
        }

        return $post_ids;
    }
}


/**
 * Get a post's distance
 */
function facetwp_get_distance( $post_id = false ) {
    global $post;

    // Get the post ID
    $post_id = ( false === $post_id ) ? $post->ID : $post_id;

    // Get the proximity class
    $facet_type = FWP()->helper->facet_types['proximity'];

    // Get the distance
    $distance = $facet_type->distance[ $post_id ] ?? -1;

    if ( -1 < $distance ) {
        return apply_filters( 'facetwp_proximity_distance_output', $distance );
    }

    return false;
}
