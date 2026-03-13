<?php

class FacetWP_Facet_Dropdown extends FacetWP_Facet
{

    function __construct() {
        $this->label = __( 'Dropdown', 'fwp' );
        $this->fields = [ 'label_any', 'parent_term', 'modifiers', 'hierarchical', 'ghosts', 'orderby', 'count' ];
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

        $label_any = empty( $facet['label_any'] ) ? __( 'Any', 'fwp-front' ) : $facet['label_any'];
        $label_any = facetwp_i18n( $label_any );

        $output .= '<select class="facetwp-dropdown">';
        $output .= '<option value="">' . esc_attr( $label_any ) . '</option>';

        foreach ( $values as $row ) {
            $selected = in_array( $row['facet_value'], $selected_values ) ? ' selected' : '';
            $disabled = empty( $row['counter'] ) ? ' disabled' : '';
            $indent = $is_hierarchical ? str_repeat( '&nbsp;&nbsp;', (int) $row['depth'] ) : '';

            // Determine whether to show counts
            $label = esc_attr( $row['facet_display_value'] );
            $label = apply_filters( 'facetwp_facet_display_value', $label, [
                'selected' => ( '' !== $selected ),
                'facet' => $facet,
                'row' => $row
            ]);

            $show_counts = apply_filters( 'facetwp_facet_dropdown_show_counts', true, [ 'facet' => $facet ] );

            if ( $show_counts ) {
                $label .= ' (' . $row['counter'] . ')';
            }

            $output .= '<option value="' . esc_attr( $row['facet_value'] ) . '"' . $selected . $disabled . '>' . $indent . $label . '</option>';
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
}
