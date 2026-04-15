<?php

class FacetWP_Facet_Slider extends FacetWP_Facet
{

    function __construct() {
        $this->label = __( 'Slider', 'fwp' );
        $this->fields = [ 'source_other', 'compare_type', 'prefix', 'suffix',
            'reset_text', 'slider_format', 'step' ];

        add_filter( 'facetwp_render_output', [ $this, 'maybe_prevent_facet_html' ], 10, 2 );
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {
        $facet = $params['facet'];
        $reset_text = __( 'Reset', 'fwp-front' );

        if ( ! empty( $facet['reset_text'] ) ) {
            $reset_text = facetwp_i18n( $facet['reset_text'] );
        }

        $output = '<div class="facetwp-slider-wrap">';
        $output .= '<div class="facetwp-slider"></div>';
        $output .= '</div>';
        $output .= '<span class="facetwp-slider-label"></span>';
        $output .= '<div><input type="button" class="facetwp-slider-reset" value="' . esc_attr( $reset_text ) . '" /></div>';
        return $output;
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        return FWP()->helper->facet_types['number_range']->filter_posts( $params );
    }


    /**
     * (Front-end) Attach settings to the AJAX response
     */
    function settings_js( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $where_clause = $this->get_where_clause( $facet );
        $selected_values = $params['selected_values'];

        // Set default slider values
        $defaults = [
            'format' => '',
            'prefix' => '',
            'suffix' => '',
            'step' => 1,
        ];
        $facet = array_merge( $defaults, $facet );

        $sql = "
        SELECT MIN(facet_value + 0) AS `min`, MAX(facet_display_value + 0) AS `max` FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '{$facet['name']}' AND facet_display_value != '' $where_clause";
        $row = $wpdb->get_row( $sql );

        $range_min = (float) $row->min;
        $range_max = (float) $row->max;

        $selected_min = (float) ( $selected_values[0] ?? $range_min );
        $selected_max = (float) ( $selected_values[1] ?? $range_max );

        return [
            'range' => [ // outer (bar)
                'min' => min( $range_min, $selected_min ),
                'max' => max( $range_max, $selected_max )
            ],
            'decimal_separator' => FWP()->helper->get_setting( 'decimal_separator' ),
            'thousands_separator' => FWP()->helper->get_setting( 'thousands_separator' ),
            'start' => [ $selected_min, $selected_max ], // inner (handles) 
            'format' => $facet['format'],
            'prefix' => facetwp_i18n( $facet['prefix'] ),
            'suffix' => facetwp_i18n( $facet['suffix'] ),
            'step' => $facet['step'],
            'handle_attributes' => [
                [ 'aria-label' => facetwp_i18n('lower') ],
                [ 'aria-label' => facetwp_i18n('upper') ]
            ]
        ];
    }


    /**
     * Prevent the slider HTML from refreshing when active
     * @since 3.8.11
     */
    function maybe_prevent_facet_html( $output, $params ) {
        if ( ! empty( $output['facets'] && 0 === $params['first_load' ] ) ) {
            foreach ( FWP()->facet->facets as $name => $facet ) {
                if ( 'slider' == $facet['type'] && ! empty( $facet['selected_values'] ) ) {
                    unset( $output['facets'][ $name ] );
                }
            }
        }
        return $output;
    }


    /**
     * Output any front-end scripts
     */
    function front_scripts() {
        FWP()->display->assets['nouislider.css'] = FACETWP_URL . '/assets/vendor/noUiSlider/nouislider.css';
        FWP()->display->assets['nouislider.js'] = FACETWP_URL . '/assets/vendor/noUiSlider/nouislider.min.js';
        FWP()->display->assets['nummy.js'] = FACETWP_URL . '/assets/vendor/nummy/nummy.min.js';
    }


    function register_fields() {
        $thousands = FWP()->helper->get_setting( 'thousands_separator' );
        $decimal = FWP()->helper->get_setting( 'decimal_separator' );
        $choices = [];

        if ( '' != $thousands ) {
            $choices['0,0'] = "5{$thousands}280";
            $choices['0,0.0'] = "5{$thousands}280{$decimal}4";
            $choices['0,0.00'] = "5{$thousands}280{$decimal}42";
        }

        $choices['0'] = '5280';
        $choices['0.0'] = "5280{$decimal}4";
        $choices['0.00'] = "5280{$decimal}42";
        $choices['0a'] = '5k';
        $choices['0.0a'] = "5{$decimal}3k";
        $choices['0.00a'] = "5{$decimal}28k";

        return [
            'prefix' => [
                'label' => __( 'Prefix', 'fwp' ),
                'notes' => 'Text that appears before each slider value',
            ],
            'suffix' => [
                'label' => __( 'Suffix', 'fwp' ),
                'notes' => 'Text that appears after each slider value',
            ],
            'slider_format' => [
                'type' => 'alias',
                'items' => [
                    'format' => [
                        'type' => 'select',
                        'label' => __( 'Format', 'fwp' ),
                        'notes' => 'If the number separators are wrong, change the [Separators] setting in the Settings tab, then save and reload the page',
                        'choices' => $choices
                    ]
                ]
            ],
            'step' => [
                'label' => __( 'Step', 'fwp' ),
                'notes' => 'The amount of increase between intervals',
                'default' => 1
            ]
        ];
    }
}
