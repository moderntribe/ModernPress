<?php

class FacetWP_Facet_Number_Range extends FacetWP_Facet
{

    function __construct() {
        $this->label = __( 'Number Range', 'fwp' );
        $this->fields = [ 'source_other', 'compare_type', 'number_fields' ];
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {

        $output = '';
        $value = $params['selected_values'];
        $value = empty( $value ) ? [ '', '', ] : $value;
        $fields = empty( $params['facet']['fields'] ) ? 'both' : $params['facet']['fields'];

        if ( 'exact' == $fields ) {
            $output .= '<input type="text" class="facetwp-number facetwp-number-min" value="' . esc_attr( $value[0] ) . '" placeholder="' . __( 'Number', 'fwp-front' ) . '" />';
        }
        if ( 'both' == $fields || 'min' == $fields ) {
            $output .= '<input type="text" class="facetwp-number facetwp-number-min" value="' . esc_attr( $value[0] ) . '" placeholder="' . __( 'Min', 'fwp-front' ) . '" />';
        }
        if ( 'both' == $fields || 'max' == $fields ) {
            $output .= '<input type="text" class="facetwp-number facetwp-number-max" value="' . esc_attr( $value[1] ) . '" placeholder="' . __( 'Max', 'fwp-front' ) . '" />';
        }

        $output .= '<input type="button" class="facetwp-submit" value="' . __( 'Go', 'fwp-front' ) . '" />';

        return $output;
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $values = $params['selected_values'];
        $where = '';

        $min = ( '' == $values[0] ) ? false : $values[0];
        $max = ( '' == $values[1] ) ? false : $values[1];

        $fields = $facet['fields'] ?? 'both';
        $compare_type = empty( $facet['compare_type'] ) ? 'basic' : $facet['compare_type'];
        $is_dual = ! empty( $facet['source_other'] );

        if ( $is_dual && 'basic' != $compare_type ) {
            if ( 'exact' == $fields ) {
                $max = $min;
            }

            $min = ( false !== $min ) ? $min : -999999999999;
            $max = ( false !== $max ) ? $max : 999999999999;

            /**
             * Enclose compare
             * The post's range must surround the user-defined range
             */
            if ( 'enclose' == $compare_type ) {
                $where .= " AND (facet_value + 0) <= '$min'";
                $where .= " AND (facet_display_value + 0) >= '$max'";
            }

            /**
             * Intersect compare
             * @link http://stackoverflow.com/a/325964
             */
            if ( 'intersect' == $compare_type ) {
                $where .= " AND (facet_value + 0) <= '$max'";
                $where .= " AND (facet_display_value + 0) >= '$min'";
            }
        }

        /**
         * Basic compare
         * The user-defined range must surround the post's range
         */
        else {
            if ( 'exact' == $fields ) {
                $max = $min;
            }
            if ( false !== $min ) {
                $where .= " AND (facet_value + 0) >= '$min'";
            }
            if ( false !== $max ) {
                $where .= " AND (facet_display_value + 0) <= '$max'";
            }
        }

        $sql = "
        SELECT DISTINCT post_id FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '{$facet['name']}' $where";
        return facetwp_sql( $sql, $facet );
    }


    function register_fields() {
        return [
            'number_fields' => [
                'type' => 'alias',
                'items' => [
                    'fields' => [
                        'type' => 'select',
                        'label' => __( 'Fields to show', 'fwp' ),
                        'choices' => [
                            'both' => __( 'Min + Max', 'fwp' ),
                            'exact' => __( 'Exact', 'fwp' ),
                            'min' => __( 'Min', 'fwp' ),
                            'max' => __( 'Max', 'fwp' )
                        ]
                    ]
                ]
            ]
        ];
    }


    /**
     * (Front-end) Attach settings to the AJAX response
     */
    function settings_js( $params ) {
        $facet = $params['facet'];
        $fields = empty( $facet['fields'] ) ? 'both' : $facet['fields'];

        return [
            'fields' => $fields
        ];
    }
}
