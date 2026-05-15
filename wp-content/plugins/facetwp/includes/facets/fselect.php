<?php

class FacetWP_Facet_fSelect extends FacetWP_Facet
{

    function __construct() {
        $this->label = __( 'fSelect', 'fwp' );
        $this->fields = [ 'label_any', 'parent_term', 'modifiers', 'hierarchical', 'multiple',
            'ghosts', 'operator', 'orderby', 'count' ];
    }


    /**
     * Load the available choices
     */
    function load_values( $params ) {
        return FWP()->helper->facet_types['checkboxes']->load_values( $params );
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {

        $output = '';
        $facet = $params['facet'];
        $values = (array) $params['values'];
        $selected_values = (array) $params['selected_values'];
        $is_hierarchical = FWP()->helper->facet_is( $facet, 'hierarchical', 'yes' );

        if ( $is_hierarchical ) {
            $values = FWP()->helper->sort_taxonomy_values( $params['values'], $facet['orderby'] );
        }

        $multiple = FWP()->helper->facet_is( $facet, 'multiple', 'yes' ) ? ' multiple="multiple"' : '';
        $label_any = empty( $facet['label_any'] ) ? __( 'Any', 'fwp-front' ) : $facet['label_any'];
        $label_any = facetwp_i18n( $label_any );

        $output .= '<select class="facetwp-dropdown"' . $multiple . '>';
        $output .= '<option value="">' . esc_html( $label_any ) . '</option>';

        foreach ( $values as $row ) {
            $selected = in_array( $row['facet_value'], $selected_values, true ) ? ' selected' : '';
            $disabled = ( 0 == $row['counter'] && '' == $selected ) ? ' disabled' : '';

            $label = esc_html( $row['facet_display_value'] );
            $label = apply_filters( 'facetwp_facet_display_value', $label, [
                'selected' => ( '' !== $selected ),
                'facet' => $facet,
                'row' => $row
            ]);

            $show_counts = apply_filters( 'facetwp_facet_dropdown_show_counts', true, [ 'facet' => $facet ] );
            $counter = $show_counts ? $row['counter'] : '';
            $depth = $is_hierarchical ? $row['depth'] : 0;

            $output .= '<option value="' . esc_attr( $row['facet_value'] ) . '" data-counter="' . $counter . '" class="d' . $depth . '"' . $selected . $disabled . '>' . $label . '</option>';
        }

        $output .= '</select>';
        return $output;
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        return FWP()->helper->facet_types['checkboxes']->filter_posts( $params );
    }


    /**
     * (Front-end) Attach settings to the AJAX response
     */
    function settings_js( $params ) {
        $facet = $params['facet'];

        $label_any = empty( $facet['label_any'] ) ? __( 'Any', 'fwp-front' ) : $facet['label_any'];
        $label_any = facetwp_i18n( $label_any );

        return [
            'placeholder'       => $label_any,
            'overflowText'      => __( '{n} selected', 'fwp-front' ),
            'searchText'        => __( 'Search', 'fwp-front' ),
            'noResultsText'     => __( 'No results found', 'fwp-front' ),
            'operator'          => $facet['operator']
        ];
    }


    /**
     * Output any front-end scripts
     */
    function front_scripts() {
        FWP()->display->assets['fSelect.css'] = FACETWP_URL . '/assets/vendor/fSelect/fSelect.css';
        FWP()->display->assets['fSelect.js'] = FACETWP_URL . '/assets/vendor/fSelect/fSelect.js';
    }
}
