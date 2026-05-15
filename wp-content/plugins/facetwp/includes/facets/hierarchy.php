<?php

class FacetWP_Facet_Hierarchy extends FacetWP_Facet
{

    function __construct() {
        $this->label = __( 'Hierarchy', 'fwp' );
        $this->fields = [ 'label_any', 'modifiers', 'orderby', 'count', 'soft_limit' ];
    }


    /**
     * Load the available choices
     */
    function load_values( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $from_clause = $wpdb->prefix . 'facetwp_index f';
        $where_clause = $params['where_clause'];

        $selected_values = (array) $params['selected_values'];
        $facet_parent_id = 0;
        $output = [];

        $label_any = empty( $facet['label_any'] ) ? __( 'Any', 'fwp-front' ) : $facet['label_any'];
        $label_any = facetwp_i18n( $label_any );

        // Orderby
        $orderby = $this->get_orderby( $facet );

        // Determine the parent_id and depth
        if ( ! empty( $selected_values[0] ) ) {

            // Get term ID from slug
            $sql = "
            SELECT t.term_id
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id AND tt.taxonomy = %s
            WHERE t.slug = %s
            LIMIT 1";

            $value = $selected_values[0];
            $taxonomy = str_replace( 'tax/', '', $facet['source'] );
            $facet_parent_id = (int) $wpdb->get_var( $wpdb->prepare( $sql, $taxonomy, $value ) );

            // Invalid term
            if ( $facet_parent_id < 1 ) {
                return [];
            }

            // Create term lookup array
            $depths = FWP()->helper->get_term_depths( $taxonomy );
            $max_depth = (int) $depths[ $facet_parent_id ]['depth'];
            $last_parent_id = $facet_parent_id;

            // Loop backwards
            for ( $i = 0; $i <= $max_depth; $i++ ) {
                $output[] = [
                    'facet_value'           => $depths[ $last_parent_id ]['slug'],
                    'facet_display_value'   => $depths[ $last_parent_id ]['name'],
                    'depth'                 => $depths[ $last_parent_id ]['depth'] + 1,
                    'counter'               => 1, // FWP.settings.num_choices
                ];

                $last_parent_id = (int) $depths[ $last_parent_id ]['parent_id'];
            }

            $output[] = [
                'facet_value'           => '',
                'facet_display_value'   => $label_any,
                'depth'                 => 0,
                'counter'               => 1,
            ];

            // Reverse it
            $output = array_reverse( $output );
        }

        $limit = $this->get_limit( $facet );

        // Update the WHERE clause
        $where_clause .= " AND parent_id = '$facet_parent_id'";

        $orderby = apply_filters( 'facetwp_facet_orderby', $orderby, $facet );
        $from_clause = apply_filters( 'facetwp_facet_from', $from_clause, $facet );
        $where_clause = apply_filters( 'facetwp_facet_where', $where_clause, $facet );

        $sql = "
        SELECT f.facet_value, f.facet_display_value, COUNT(DISTINCT f.post_id) AS counter
        FROM $from_clause
        WHERE f.facet_name = '{$facet['name']}' $where_clause
        GROUP BY f.facet_value
        ORDER BY $orderby
        LIMIT $limit";

        $results = $wpdb->get_results( $sql, ARRAY_A );
        $new_depth = empty( $output ) ? 0 : $output[ count( $output ) - 1 ]['depth'] + 1;

        foreach ( $results as $result ) {
            $result['depth'] = $new_depth;
            $result['is_choice'] = true;
            $output[] = $result;
        }

        return $output;
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {
        $facet = $params['facet'];
        $values = (array) $params['values'];
        $selected_values = (array) $params['selected_values'];

        $output = '';
        $soft_limit = empty( $facet['soft_limit'] ) ? 0 : (int) $facet['soft_limit'];

        $num = 0;
        if ( ! empty( $values ) ) {
            foreach ( $values as $row ) {
                $last_depth = $last_depth ?? $row['depth'];
                $selected = ( ! empty( $selected_values ) && $row['facet_value'] == $selected_values[0] );

                $label = esc_html( $row['facet_display_value'] );
                $label = apply_filters( 'facetwp_facet_display_value', $label, [
                    'selected' => $selected,
                    'facet' => $facet,
                    'row' => $row
                ]);

                if ( $row['depth'] > $last_depth ) {
                    $output .= '<div class="facetwp-depth">';
                }

                if ( 0 < $soft_limit && $num == $soft_limit ) {
                    $output .= '<div class="facetwp-overflow facetwp-hidden">';
                }

                if ( ! $selected ) {
                    if ( isset( $row['is_choice'] ) ) {
                        $label .= ' <span class="facetwp-counter">(' . $row['counter'] . ')</span>';
                    }
                    else {
                        $arrow = apply_filters( 'facetwp_facet_hierarchy_arrow', '&#8249; ' );
                        $label = $arrow . $label;
                    }
                }

                $output .= '<div class="facetwp-link' . ( $selected ? ' checked' : '' ) . '" data-value="' . esc_attr( $row['facet_value'] ) . '">' . $label . '</div>';

                if ( isset( $row['is_choice'] ) ) {
                    $num++;
                }

                $last_depth = $row['depth'];
            }

            if ( 0 < $soft_limit && $soft_limit < $num ) {
                $output .= '</div>';
                $output .= '<a class="facetwp-toggle">' . facetwp_i18n( __( 'See {num} more', 'fwp-front' ) ) . '</a>';
                $output .= '<a class="facetwp-toggle facetwp-hidden">' . facetwp_i18n( __( 'See less', 'fwp-front' ) ) . '</a>';
            }

            for ( $i = 0; $i <= $last_depth; $i++ ) {
                $output .= '</div>';
            }
        }

        return $output;
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        return FWP()->helper->facet_types['checkboxes']->filter_posts( $params );
    }
}
