<?php

class FacetWP_Display
{

    /* (array) Facet types being used on the page */
    public $active_types = [];

    /* (array) Facets being used on the page */
    public $active_facets = [];

    /* (array) Extra features used on the page */
    public $active_extras = [];

    /* (array) Saved shortcode attributes */
    public $shortcode_atts = [];

    /* (boolean) Whether to enable FacetWP for the current page */
    public $load_assets = false;

    /* (array) Scripts and stylesheets to enqueue */
    public $assets = [];

    /* (array) Data to pass to front-end JS */
    public $json = [];


    function __construct() {
        add_filter( 'widget_text', 'do_shortcode' );
        add_action( 'loop_start', [ $this, 'add_template_tag' ] );
        add_action( 'loop_no_results', [ $this, 'add_template_tag' ] );
        add_action( 'wp_footer', [ $this, 'front_scripts' ], 25 );
        add_shortcode( 'facetwp', [ $this, 'shortcode' ] );
        add_filter( 'facetwp_gmaps_params', [ $this, 'gmaps_params' ], 5 );
    }


    /**
     * Detect the loop container if the "facetwp-template" class is missing
     */
    function add_template_tag( $wp_query ) {
        if ( true === $wp_query->get( 'facetwp' ) && did_action( 'wp_head' ) ) {
            echo "<!--fwp-loop-->\n";
        }
    }


    /**
     * Set default values for atts
     *
     * Old: [facetwp template="foo" static]
     * New: [facetwp template="foo" static="true"]
     */
    function normalize_atts( $atts ) {
        foreach ( $atts as $key => $val ) {
            if ( is_int( $key ) ) {
                $atts[ $val ] = true;
                unset( $atts[ $key ] );
            }
        }
        return $atts;
    }


    /**
     * Register shortcodes
     */
    function shortcode( $atts ) {
        $atts = $this->normalize_atts( $atts );
        $this->shortcode_atts[] = $atts;

        $output = '';
        if ( isset( $atts['facet'] ) ) {
            $facet = FWP()->helper->get_facet_by_name( $atts['facet'] );

            if ( $facet ) {
                $ui = empty( $facet['ui_type'] ) ? $facet['type'] : $facet['ui_type'];
                $ui_attr = empty( $facet['ui_type'] ) ? '' : ' data-ui="' . $ui . '"';
                $output = '<div class="facetwp-facet facetwp-facet-' . $facet['name'] . ' facetwp-type-' . $ui . '" data-name="' . $facet['name'] . '" data-type="' . $facet['type'] . '"' . $ui_attr . '></div>';

                // Build list of active facet types
                $this->active_types[ $facet['type'] ] = $facet['type'];
                $this->active_facets[ $facet['name'] ] = $facet['name'];
                $this->load_assets = true;
            }
        }
        elseif ( isset( $atts['template'] ) ) {
            $template = FWP()->helper->get_template_by_name( $atts['template'] );

            if ( $template ) {
                $class_name = 'facetwp-template';

                // Static template
                if ( isset( $atts['static'] ) ) {
                    $renderer = new FacetWP_Renderer();
                    $renderer->template = $template;
                    $renderer->query_args = $renderer->get_query_args();
                    $renderer->query = new WP_Query( $renderer->query_args );
                    $html = $renderer->get_template_html();
                    $class_name .= '-static';
                }
                // Preload template (search engine visible)
                else {
                    global $wp_query;

                    $temp_query = $wp_query;
                    $args = FWP()->request->process_preload_data( $template['name'] );
                    $preload_data = FWP()->facet->render( $args );
                    $html = $preload_data['template'];
                    $wp_query = $temp_query;

                    $this->load_assets = true;
                }

                $output = '<div class="{class}" data-name="{name}">{html}</div>';
                $output = str_replace( '{class}', $class_name, $output );
                $output = str_replace( '{name}', $atts['template'], $output );
                $output = str_replace( '{html}', $html, $output );
            }
        }
        elseif ( isset( $atts['sort'] ) ) {
            $this->active_extras['sort'] = true;
            $output = '<div class="facetwp-sort"></div>';
        }
        elseif ( isset( $atts['selections'] ) ) {
            $output = '<div class="facetwp-selections"></div>';
        }
        elseif ( isset( $atts['counts'] ) ) {
            $this->active_extras['counts'] = true;
            $output = '<div class="facetwp-counts"></div>';
        }
        elseif ( isset( $atts['pager'] ) ) {
            $this->active_extras['pager'] = true;
            $output = '<div class="facetwp-pager"></div>';
        }
        elseif ( isset( $atts['per_page'] ) ) {
            $this->active_extras['per_page'] = true;
            $output = '<div class="facetwp-per-page"></div>';
        }

        $output = apply_filters( 'facetwp_shortcode_html', $output, $atts );

        return $output;
    }

    /**
     * get google api key from GMAPS_API_KEY, facetwp_gmaps_api_key filter, or gmaps_api_key setting
     * @since 4.4
     * */
    function get_gmaps_api_key() {

        // hard-coded
        $api_key = defined( 'GMAPS_API_KEY' ) ? GMAPS_API_KEY : '';

        // admin ui
        $tmp_key = FWP()->helper->get_setting( 'gmaps_api_key' );
        $api_key = empty( $tmp_key ) ? $api_key : $tmp_key;

        // API key hook
        return apply_filters( 'facetwp_gmaps_api_key', $api_key );

    }

    /**
     * backwards compatibility for params added in facetwp_gmaps_url filter
     * @since 4.4
     */
    function gmaps_params( $params ) {
        if ( has_filter( 'facetwp_gmaps_url' ) ) {
            $gmaps_url = apply_filters( 'facetwp_gmaps_url', '//maps.googleapis.com/maps/api/js?libraries=places' ); // old url
            parse_str( parse_url( $gmaps_url, PHP_URL_QUERY ), $query_params ); // get array of params
            $query_params = array_diff_key( $query_params, [ 'libraries' => '',  'key' => '', 'callback' => '' ] ); // remove unneeded params
            $params = array_merge( $params, $query_params );
        }
        return $params;
    }


    /**
     * Output facet scripts
     */
    function front_scripts() {

        // Not enqueued - front.js needs to load before front_scripts()
        if ( apply_filters( 'facetwp_load_assets', $this->load_assets ) ) {

            // Load CSS?
            if ( apply_filters( 'facetwp_load_css', true ) ) {
                $this->assets['front.css'] = FACETWP_URL . '/assets/css/front.css';
            }

            // Load required JS
            $this->assets['front.js'] = FACETWP_URL . '/assets/js/dist/front.min.js';

            // Backwards compat?
            if ( apply_filters( 'facetwp_load_deprecated', false ) ) {
                $this->assets['front-deprecated.js'] = FACETWP_URL . '/assets/js/src/deprecated.js';
            }

            // Load a11y?
            $a11y = FWP()->helper->get_setting( 'load_a11y', 'no' );
            $a11y_hook = apply_filters( 'facetwp_load_a11y', false );

            if ( 'yes' == $a11y || $a11y_hook ) {
                $this->assets['accessibility.js'] = FACETWP_URL . '/assets/js/src/accessibility.js';
                $this->json['a11y'] = [
                    'label_page'        => __( 'Go to page', 'fwp-front' ),
                    'label_page_next'   => __( 'Go to next page', 'fwp-front' ),
                    'label_page_prev'   => __( 'Go to previous page', 'fwp-front' )
                ];
            }

            // Pass GET and URI params
            $http_params = [
                'get' => $_GET,
                'uri' => FWP()->helper->get_uri(),
                'url_vars' => FWP()->request->url_vars,
            ];

            // See FWP()->facet->get_query_args()
            if ( ! empty( FWP()->facet->archive_args ) ) {
                $http_params['archive_args'] = FWP()->facet->archive_args;
            }

            // Populate the FWP_JSON object
            $this->json['prefix'] = FWP()->helper->get_setting( 'prefix' );
            $this->json['no_results_text'] = __( 'No results found', 'fwp-front' );
            $this->json['ajaxurl'] = get_rest_url() . 'facetwp/v1/refresh';
            $this->json['nonce'] = wp_create_nonce( 'wp_rest' );

            if ( apply_filters( 'facetwp_use_preloader', true ) ) {
                $overrides = FWP()->request->process_preload_overrides([
                    'facets' => $this->active_facets,
                    'extras' => $this->active_extras,
                ]);
                $args = FWP()->request->process_preload_data( false, $overrides );
                $this->json['preload_data'] = FWP()->facet->render( $args );
            }

            ob_start();

            foreach ( $this->active_types as $type ) {
                $facet_class = FWP()->helper->facet_types[ $type ];
                if ( method_exists( $facet_class, 'front_scripts' ) ) {
                    $facet_class->front_scripts();
                }
            }

            $inline_scripts = ob_get_clean();

            if ( apply_filters( 'facetwp_load_gmaps', false ) ) {

                // remove non-async gmaps
                add_filter( 'facetwp_assets', function( $assets ) {
                    unset( $assets['gmaps'] );
                    return $assets;
                });                  

                $params = [
                    'key'   => $this->get_gmaps_api_key(),
                    'v'     => 'quarterly',
                ];
                $params_string = '';
                foreach ( apply_filters( 'facetwp_gmaps_params', $params ) AS $param => $val ) {
                    $params_string .=  $param . ': "' . esc_attr($val) . '",';
                }
?>                
<script>
  (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. FacetWP's instance is ignored."):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
    <?php echo $params_string; ?>
  });
</script>
<?php }

            $assets = apply_filters( 'facetwp_assets', $this->assets );

            foreach ( $assets as $slug => $data ) {
                $data = (array) $data;
                $is_css = ( 'css' == substr( $slug, -3 ) );
                $version = empty( $data[1] ) ? FACETWP_VERSION : $data[1];
                $url = $data[0];

                if ( false !== strpos( $url, 'facetwp' ) ) {
                    $prefix = ( false !== strpos( $url, '?' ) ) ? '&' : '?';
                    $url .= $prefix . 'ver=' . $version;
                }

                $html = $is_css ? '<link href="{url}" rel="stylesheet">' : '<script src="{url}"></script>';
                $html = apply_filters( 'facetwp_asset_html', $html, $url );
                echo str_replace( '{url}', $url, $html ) . "\n";                
            }

            echo $inline_scripts;

            do_action( 'facetwp_scripts' );
?>
<script>
window.FWP_JSON = <?php echo json_encode( $this->json ); ?>;
window.FWP_HTTP = <?php echo json_encode( FWP()->helper->escape( $http_params ) ); ?>;
</script>
<?php
        }
    }
}
