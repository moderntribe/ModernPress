<?php

if ( ! defined( 'FACETWP_MAP_FACET_VERSION' ) ) {
	define( 'FACETWP_MAP_FACET_VERSION', '2.0' );
}

// if map addon is active
if ( version_compare( FACETWP_MAP_FACET_VERSION, '2.0', '<' ) ) {
    return;
}

class FacetWP_Facet_Map extends FacetWP_Facet
{

    public $label;
    public $map_facet;
    public $proximity_facet;
    public $proximity_coords;
    public $field_defaults;


    function __construct() {

        $this->label = __( 'Map', 'fwp' );
        $this->fields = [ 'longitude', 'mapid', 'map_design', 'btn_label', 'reset_label', 'cluster', 'limit', 'map_width','map_height', 'min_zoom', 'max_zoom', 'default_lat', 'default_lng', 'default_zoom', 'ajax_markers', 'marker_content' ];
        $this->field_defaults = [
            'mapid' => 'DEMO_MAP_ID'
        ];

        add_filter( 'facetwp_index_row', [ $this, 'index_latlng' ], 1, 2 );
        add_filter( 'facetwp_render_output', [ $this, 'add_marker_data' ], 10, 2 );

        // ajax load of marker content
        add_action( 'facetwp_init', function() {
            if ( isset( $_POST['action'] ) && 'facetwp_map_marker_content' == $_POST['action'] ) {
                $post_id = (int) $_POST['post_id'];
                $facet_name = $_POST['facet_name'];

                echo $this->get_marker_content( $post_id, $facet_name );
                wp_die();
            }
        });
    }

    /**
     * Generate the facet HTML
     */
    function render( $params ) {

        $width = $params['facet']['map_width'];
        $width = empty( $width ) ? 600 : $width;
        $width = is_numeric( $width ) ? $width . 'px' : $width;

        $height = $params['facet']['map_height'];
        $height = empty( $height ) ? 300 : $height;
        $height = is_numeric( $height ) ? $height . 'px' : $height;

        $class = '';
        $btn_label = ( isset( $params['facet']['btn_label'] ) && '' != $params['facet']['btn_label'] ) ? $params['facet']['btn_label'] :  __( 'Enable map filtering', 'facetwp-map-facet' );

        if ( $this->is_map_filtering_enabled() ) {
            $class = ' enabled';
            $btn_label = ( isset( $params['facet']['reset_label'] ) && '' != $params['facet']['reset_label'] ) ? $params['facet']['reset_label'] : __( 'Reset', 'facetwp-map-facet' ) ;
        }

        $output = '<div id="facetwp-map" style="width:' . $width . '; height:' . $height . '"></div>';
        $output .= '<div><button class="facetwp-map-filtering' . $class . '">' . esc_html( facetwp_i18n( $btn_label ) ) . '</button></div>';
        return $output;
    }


    /**
     * Is filtering turned on for the map?
     * @return bool
     */
    function is_map_filtering_enabled() {
        foreach ( FWP()->facet->facets as $facet ) {
            if ( 'map' == $facet['type'] && ! empty( $facet['selected_values'] ) ) {
                return true;
            }
        }

        return false;
    }


    /**
     * Is a proximity facet in use? If so, return a lat/lng array
     * @return mixed array of coordinates, or FALSE
     */
    function is_proximity_in_use() {
        foreach ( FWP()->facet->facets as $facet ) {
            if ( 'proximity' == $facet['type'] && ! empty( $facet['selected_values'] ) ) {
                $this->proximity_facet = $facet;

                return [
                    'lat'       => (float) $facet['selected_values'][0],
                    'lng'       => (float) $facet['selected_values'][1],
                    'radius'    => (int) $facet['selected_values'][2]
                ];
            }
        }

        return false;
    }


    function add_marker_data( $output, $params ) {
        if ( ! $this->is_map_active() ) {
            return $output;
        }

        // Exit if paging and limit = "all"
        if ( 0 < (bool) FWP()->facet->ajax_params['soft_refresh'] ) {
            if ( 'all' == FWP()->helper->facet_is( $this->map_facet, 'limit', 'all' ) ) {
                $output['settings']['map'] = '';
                return $output;
            }
        }

        $settings = [
            'locations' => []
        ];

        $settings['config'] = [
            'default_lat'   => (float) $this->map_facet['default_lat'],
            'default_lng'   => (float) $this->map_facet['default_lng'],
            'default_zoom'  => (int) $this->map_facet['default_zoom'],
            'spiderfy'      => [
                'markersWontMove'   => true,
                'markersWontHide'   => true,
                'basicFormatEvents' => true,
                'keepSpiderfied'    => true
            ]
        ];

        if ( 'yes' == $this->map_facet['cluster'] ) {
            $settings['config']['cluster'] = [
                'zoomOnClick' => true,
                'maxZoom' => 15,
                'minimumClusterSize' => 2
            ];
        }

        $settings['init'] = [
            'mapId' => ( '' != ( $this->map_facet['mapid'] ?? '' ) ) ? $this->map_facet['mapid'] : 'DEMO_MAP_ID',
            'gestureHandling' => 'auto',
            'zoom' => (int) $this->map_facet['default_zoom'] ?: 5,
            'minZoom' => (int) $this->map_facet['min_zoom'] ?: 1,
            'maxZoom' => (int) $this->map_facet['max_zoom'] ?: 20,
            'center' => [
                'lat' => (float) $this->map_facet['default_lat'],
                'lng' => (float) $this->map_facet['default_lng'],
            ],
        ];

        $settings = apply_filters( 'facetwp_map_init_args', $settings );

        // Get the proximity facet's coordinates (if available)
        $this->proximity_coords = $this->is_proximity_in_use();

        if ( false !== $this->proximity_coords ) {
            $marker_args = [
                'title' => __( 'Your location', 'facetwp-map-facet' ),
                'position' => $this->proximity_coords,
                'pinOptions' => [ 'background' => '#FFD700', 'borderColor' => '#DAA520', 'scale' => 0.85, 'glyphColor' => '#DAA520'],
                'infoWindowContent' => '<p>'.__( 'Your location', 'facetwp-map-facet' ).'</p>',
            ];

            $marker_args = apply_filters( 'facetwp_map_proximity_marker_args', $marker_args );

            if ( ! empty( $marker_args ) ) {
                $settings['locations'][] = $marker_args;
            }
        }

        // get all post IDs
        if ( isset( $this->map_facet['limit'] ) && 'all' == $this->map_facet['limit'] ) {
            $post_ids = isset( FWP()->filtered_post_ids ) ?
                FWP()->filtered_post_ids :
                FWP()->facet->query_args['post__in'];
        }
        // get paginated post IDs
        else {
            $post_ids = (array) wp_list_pluck( FWP()->facet->query->posts, 'ID' );
        }

        // remove duplicates
        $post_ids = array_unique( $post_ids );

        $all_coords = $this->get_coordinates( $post_ids, $this->map_facet );

        foreach ( $post_ids as $post_id ) {
            if ( isset( $all_coords[ $post_id ] ) ) {
                foreach ( $all_coords[ $post_id ] as $coords ) {
                    $args = [
                        'position' => $coords,
                        'post_id' => $post_id,
                    ];

                    if ( 'yes' !== $this->map_facet['ajax_markers'] ) {
                        $args['infoWindowContent'] = $this->get_marker_content( $post_id );
                    }

                    $args = apply_filters( 'facetwp_map_marker_args', $args, $post_id );

                    // back compat for legacy content arg
                    // change content to infoWindowContent (if it exists*)
                    // because content is now the pin arg in markers
                    // *for ajax markers, infoWindowContent should not be set to ''
                    if ( isset( $args['content'] ) ) $args['infoWindowContent'] = $args['content'];

                    if ( false !== $args ) {
                        $settings['locations'][] = $args;
                    }
                }
            }
        }

        $output['settings']['map'] = $settings;

        return $output;
    }


    /**
     * Grab all coordinates from the index table
     */
    function get_coordinates( $post_ids, $facet ) {
        global $wpdb;

        $output = [];

        if ( ! empty( $post_ids ) ) {
            $post_ids = implode( ',', $post_ids );

            $sql = "
            SELECT post_id, facet_value AS lat, facet_display_value AS lng
            FROM {$wpdb->prefix}facetwp_index
            WHERE facet_name = '{$facet['name']}' AND post_id IN ($post_ids)";

            $result = $wpdb->get_results( $sql );

            foreach ( $result as $row ) {
                $output[ $row->post_id ][] = [
                    'lat' => (float) $row->lat,
                    'lng' => (float) $row->lng,
                ];
            }

            // Support ACF repeaters
            if ( false !== $this->proximity_coords) {
                foreach ( $output as $post_id => $coords ) {
                    if ( 1 < count( $coords ) ) {
                        $output[ $post_id ] = [];

                        foreach ( $coords as $latlng ) {
                            if ( $this->is_within_bounds( $latlng ) ) {
                                $output[ $post_id ][] = $latlng;
                            }
                        }
                    }
                }
            }
        }

        return $output;
    }


    /**
     * Is the current point within the proximity bounds?
     */
    function is_within_bounds( $latlng ) {
        $lat1 = $latlng['lat'];
        $lng1 = $latlng['lng'];
        $lat2 = $this->proximity_coords['lat'];
        $lng2 = $this->proximity_coords['lng'];

        $radius = $this->proximity_coords['radius'];
        $unit = $this->proximity_facet['unit'];

        if ( $lat1 == $lat2 && $lng1 == $lng2 ) {
            return true;
        }

        $dist = sin( deg2rad( $lat1 ) ) * sin( deg2rad( $lat2 ) ) + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * cos( deg2rad( $lng1 - $lng2 ) );
        $dist = min( max( $dist, -1 ), 1 ); // force value between -1 and 1
        $dist = rad2deg( acos( $dist ) );

        $miles = $dist * 60 * 1.1515;
        $needle = ( 'km' == $unit ) ? $miles * 1.609344 : $miles;
        return $needle <= $radius;
    }


    /**
     * Is this page using a map facet?
     */
    function is_map_active() {
        foreach ( FWP()->facet->facets as $name => $facet ) {
            if ( 'map' == $facet['type'] ) {
                $this->map_facet = $facet; // save the facet
                return true;
            }
        }

        return false;
    }


    /**
     * Get marker content (pulled via ajax)
     */
    function get_marker_content( $post_id, $facet_name = false ) {
        if ( false !== $facet_name ) {
            $facet = FWP()->helper->get_facet_by_name( $facet_name );
            $content = $facet['marker_content'];
        }
        else {
            $content = $this->map_facet['marker_content'];
        }

        if ( empty( $content ) ) {
            return '';
        }

        global $post;

        ob_start();

        // Set the main $post object
        $post = get_post( $post_id );

        setup_postdata( $post );

        // Remove UTF-8 non-breaking spaces
        $html = preg_replace( "/\xC2\xA0/", ' ', $content );

        eval( '?>' . $html );

        // Reset globals
        wp_reset_postdata();

        // Store buffered output
        return ob_get_clean();
    }


    /**
     * Filter the query based on the map bounds
     */
    function filter_posts( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $selected_values = (array) $params['selected_values'];

        $swlat = (float) $selected_values[0];
        $swlng = (float) $selected_values[1];
        $nelat = (float) $selected_values[2];
        $nelng = (float) $selected_values[3];

        // @url https://stackoverflow.com/a/35944747
        if ( $swlng < $nelng ) {
            $compare_lng = "facet_display_value BETWEEN $swlng AND $nelng";
        }
        else {
            $compare_lng = "facet_display_value BETWEEN $swlng AND 180 OR ";
            $compare_lng .= "facet_display_value BETWEEN -180 AND $nelng";
        }

        $sql = "
        SELECT DISTINCT post_id FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '{$facet['name']}' AND
        facet_value BETWEEN $swlat AND $nelat AND ($compare_lng)";

        return $wpdb->get_col( $sql );
    }


    /**
     * Output any front-end scripts
     */
    function front_scripts() {
        
        add_filter( 'facetwp_load_gmaps', '__return_true', 4 );

        FWP()->display->assets['oms'] = [ FACETWP_URL . '/assets/js/src/oms.min.js', FACETWP_MAP_FACET_VERSION ];
        
        if ( 'yes' == $this->map_facet['cluster'] ) {
            FWP()->display->assets['markerclusterer'] = [ 'https://unpkg.com/@googlemaps/markerclusterer/dist/index.min.js', FACETWP_MAP_FACET_VERSION ];
        }
		
        FWP()->display->assets['facetwp-map'] = [ FACETWP_URL . '/assets/js/src/map.js', FACETWP_MAP_FACET_VERSION ];

        $btn_label = ( isset( $this->map_facet['btn_label'] ) && '' != $this->map_facet['btn_label'] ) ? $this->map_facet['btn_label'] : __( 'Enable map filtering', 'fwp-front' ) ;
        FWP()->display->json['map']['filterText'] = facetwp_i18n( $btn_label );

        $reset_label = ( isset( $this->map_facet['reset_label'] ) && '' != $this->map_facet['reset_label'] ) ? $this->map_facet['reset_label'] : __( 'Reset', 'fwp-front');
        FWP()->display->json['map']['resetText'] = facetwp_i18n( $reset_label );
        FWP()->display->json['map']['facet_name'] = $this->map_facet['name'];
        FWP()->display->json['map']['ajaxurl'] = admin_url( 'admin-ajax.php' );
    }


    function register_fields() {
        return [
            'longitude' => [
                'type' => 'alias',
                'items' => [
                    'source_other' => [
                        'label' => __( 'Longitude', 'fwp' ),
                        'notes' => __( '(Optional) use a separate longitude field.', 'fwp' ),
                        'html' => '<data-sources :facet="facet" setting-name="source_other"></data-sources>'
                    ]
                ]
            ],
            'mapid' => [
                'label' => __( 'Map ID', 'fwp' ),
                'placeholder' => 'DEMO_MAP_ID',
                'notes' => __( 'In the Google Cloud Console\'s Map Management section, <a target="_blank" href="https://facetwp.com/help-center/facets/facet-types/map/#using-a-google-map-id">create a Map ID</a> and enter it here. <a target="_blank" href="https://facetwp.com/help-center/facets/facet-types/map/#choose-a-map-rendering-type">Choose a map rendering type</a> (raster or vector) in the Map ID settings. To change your map styling, <a target="_blank" href="https://facetwp.com/help-center/facets/facet-types/map/#use-a-custom-map-style">create a Map Style</a> in the Map Styles section and attach it to the Map ID.', 'fwp' )
            ],
            'map_design' => [
                'label' => __( 'Map design', 'fwp' ),
                'notes' => __( 'In the Google Cloud Console\'s Map Management section, <a target="_blank" href="https://facetwp.com/help-center/facets/facet-types/map/#using-a-google-map-id">create a Map ID</a> and enter it in the above Map ID setting. Then <a target="_blank" href="https://facetwp.com/help-center/facets/facet-types/map/#use-a-custom-map-style">create a Map Style</a> in the Map Styles section and attach it to the Map ID.', 'fwp' ),
                'html' => 'This setting has been replaced with<br />
                    <a target="_blank" href="https://facetwp.com/help-center/facets/facet-types/map/#use-a-custom-map-style">cloud-based map styling</a>.
                    <input type="hidden" v-model="facet.map_design">'
            ],
            'btn_label' => [
                'label' => __( 'Enable filtering button label', 'fwp' ),
                'placeholder' => facetwp_i18n( __( 'Enable map filtering', 'fwp' ) )
            ],
            'reset_label' => [
                'label' => __( 'Reset button label', 'fwp', 'fwp' ),
                'placeholder' => facetwp_i18n( __( 'Reset', 'fwp' ) )
            ],
            'cluster' => [
                'type' => 'toggle',
                'label' => __( 'Marker clustering', 'fwp' ),
                'notes' => __( 'Group markers into clusters?', 'fwp' )
            ],
            'limit' => [
                'type' => 'select',
                'label' => __( 'Marker limit', 'fwp' ),
                'choices' => [
                    'all' => __( 'Show all results', 'fwp' ),
                    'paged' => __( 'Show current page results', 'fwp' )
                ]
            ],
            'map_width' => [ 
                'type' => 'text',    
                'html' => '<input type="text" class="facet-map-width" v-model="facet.map_width" placeholder="'. __( "Width", "fwp" ) . '" style="width:96px" /><input type="text" class="facet-map-height" v-model="facet.map_height" placeholder="'. __( "Height", "fwp" ) . '" style="width:96px" />',
                'label' => __( 'Map width / height', 'fwp' ),
                'notes' => __( 'Set width and height of the map. Without units, px is assumed. Use other CSS units if needed, e.g. 100% for responsive full width of the parent container. Don\'t use 100% for the height if the map\'s container does not have a fixed height, else the map will have no height and will not show.', 'fwp' )
            ],
            'map_height' => [
                'show' => '0==1'
            ],
            'min_zoom' => [ 
                'type' => 'text',   
                'html' => '<input type="text" class="facet-min-zoom" v-model="facet.min_zoom"  placeholder="'. __( "Min", "fwp" ) . '" style="width:96px" />
                <input type="text" class="facet-max-zoom" v-model="facet.max_zoom"  placeholder="'. __( "Max", "fwp" ) . '" style="width:96px" />',
                'label' => __( 'Zoom min / max', 'fwp' ),
                'notes' => __( 'Set zoom bounds (between 1 and 20).', 'fwp' )
            ],
            'max_zoom' => [
                'show' => '0==1'
            ],
            'default_lat' => [ 
                'type' => 'text',    
                'html' => '<input type="text" class="facet-default-lat" v-model="facet.default_lat"  placeholder="'. __( "Latitude", "fwp" ) . '" style="width:96px" />
                <input type="text" class="facet-default-lng" v-model="facet.default_lng"  placeholder="'. __( "Longitude", "fwp" ) . '" style="width:96px" />                
                <input type="text" class="facet-default-zoom" v-model="facet.default_zoom"  placeholder="'. __( "Zoom (1-20)", "fwp" ) . '" style="width:96px" />',
                'label' => __( 'Fallback lat / lng / zoom', 'fwp' ),
                'notes' => __( 'Center the map here, and set a custom zoom level, if there are no results.', 'fwp' )
            ],
            'default_lng' => [
                'show' => '0==1'
            ],
            'default_zoom' => [
                'show' => '0==1'
            ],
            'ajax_markers' => [
                'type' => 'toggle',
                'label' => __( 'Info window ajax loading', 'fwp' ),
                'notes' => __( 'Dynamically load marker info window content on click of markers, which could improve load times for pages with many markers.', 'fwp' )
            ],
            'marker_content' => [
                'type' => 'textarea',
                'label' => __( 'Info window content', 'fwp' ),
                'html' => '
                    <textarea class="facet-marker-content" id="marker-content-editor" v-model="facet.marker_content"></textarea>
                    <p class="note">' . __( 'To search your code, click in the editor, then use Ctrl+F (Windows/Linux) or Cmd+F (Mac).', 'fwp' ) . '</p>
                '
            ],
        ];
    }


    /**
     * Index the coordinates
     * We expect a comma-separated "latitude, longitude"
     */
    function index_latlng( $params, $class ) {

        $facet = FWP()->helper->get_facet_by_name( $params['facet_name'] );

        if ( false !== $facet && 'map' == $facet['type'] ) {
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
}
