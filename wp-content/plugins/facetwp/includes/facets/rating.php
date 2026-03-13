<?php

class FacetWP_Facet_Rating extends FacetWP_Facet
{

    function __construct() {
        $this->label = __( 'Star Rating', 'fwp' );
        $this->fields = [ 'ratings_icon', 'ghost_ratings', 'color', 'color_selected', 'color_undo', 'color_ghosts' ];
    }


    /**
     * Load the available choices
     */
    function load_values( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $from_clause = $wpdb->prefix . 'facetwp_index f';

        // Facet in "OR" mode
        $where_clause = $this->get_where_clause( $facet );

        $output = [
            1 => [ 'counter' => 0 ],
            2 => [ 'counter' => 0 ],
            3 => [ 'counter' => 0 ],
            4 => [ 'counter' => 0 ],
            5 => [ 'counter' => 0 ]
        ];

        $sql = "
        SELECT COUNT(*) AS `count`, FLOOR(f.facet_value) AS `rating`
        FROM $from_clause
        WHERE f.facet_name = '{$facet['name']}' AND FLOOR(f.facet_value) >= 1 $where_clause
        GROUP BY rating";

        $results = $wpdb->get_results( $sql );

        foreach ( $results as $result ) {
            $output[ $result->rating ]['counter'] = $result->count;
        }

        $total = 0;

        // The lower rating should include higher rating counts
        for ( $i = 5; $i > 0; $i-- ) {
            $output[ $i ]['counter'] += $total;
            $total = $output[ $i ]['counter'];
        }

        return $output;
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {

        $output = '';
        $facet = $params['facet'];
        $values = (array) $params['values'];
        $selected_values = (array) $params['selected_values'];
        $show_ghosts = FWP()->helper->facet_is( $facet, 'ghost_ratings', 'yes' );
        $ratings_icon = ( isset( $facet[ 'ratings_icon' ] ) && '' != $facet[ 'ratings_icon' ] ) ? $facet[ 'ratings_icon' ] : '&#9733;';

        $num_stars = 0;
        foreach ( $values as $val ) {
            if ( 0 < $val['counter'] ) {
                $num_stars++;
            }
        }

        $num_stars = $show_ghosts ? 5 : $num_stars;

        if ( 0 < $num_stars ) {
            $output .= '<span class="facetwp-stars">';

            for ( $i = $num_stars; $i >= 1; $i-- ) {
                $class = in_array( $i, $selected_values ) ? ' selected' : '';
                $is_disabled = ! ( 0 < $values[ $i ]['counter'] ) ? true : false;
                $class = $is_disabled ? $class . ' disabled' : $class;
                $output .= '<span class="facetwp-star' . $class . '" data-value="' . $i . '" data-counter="' . $values[ $i ]['counter'] . '">' . apply_filters( 'facetwp_ratings_icon', $ratings_icon, $is_disabled, $facet ) . '</span>';
            }

            $output .= '</span>';
            $output .= ' <span class="facetwp-star-label"></span>';
            $output .= ' <span class="facetwp-counter"></span>';
        }

        return $output;
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $selected_values = $params['selected_values'];
        $selected_values = is_array( $selected_values ) ? $selected_values[0] : $selected_values;

        $sql = "
        SELECT DISTINCT post_id FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '{$facet['name']}' AND facet_value >= '$selected_values'";
        return $wpdb->get_col( $sql );
    }


    function register_fields() {

        return [
            'ratings_icon' => [
                'type' => 'select',
                'label' => __( 'Rating icon', 'fwp' ),
                'notes' => 'Select icon for ratings.',
                'choices' => [
                    '&#9733;' => __( 'Stars', 'fwp' ) . '&nbsp;&#9733&#9733&#9733&#9733&#9733',
                    '&#9734;' => __( 'Star Outlines', 'fwp' ) . '&nbsp;&#9734&#9734&#9734&#9734&#9734',
                    '&#9829;' => __( 'Hearts', 'fwp' ) . '&nbsp;&#9829&#9829&#9829&#9829&#9829',
                ]
            ],
            'ghost_ratings' => [
                'type' => 'toggle',
                'label' => __( 'Show ghost ratings', 'fwp' ),
                'notes' => 'Always show 5 icons even when there are no matches.'
            ],
            'color' => [
                'type' => 'color-picker',
                'label' => __( 'Color', 'fwp' ),
                'notes' => 'Set icon color.',
                'html' => '<color-picker :facet="facet" setting-name="color" default-color="#cccccc"></color-picker>',
                'default' => '#cccccc'
            ],
            'color_selected' => [
                'type' => 'color-picker',
                'label' => __( 'Selected color', 'fwp' ),
                'notes' => 'Set icon hover and selected color.',
                'html' => '<color-picker :facet="facet" setting-name="color_selected" default-color="#000000"></color-picker>',
            ],
            'color_undo' => [
                'type' => 'color-picker',
                'label' => __( 'Undo color', 'fwp' ),
                'notes' => 'Set icon undo color.',
                'html' => '<color-picker :facet="facet" setting-name="color_undo" default-color="#ff0000"></color-picker>',
            ],
            'color_ghosts' => [
                'type' => 'color-picker',
                'label' => __( 'Ghost color', 'fwp' ),
                'notes' => 'Set icon ghost color.',
                'html' => '<color-picker :facet="facet" setting-name="color_ghosts" default-color="#eeeeee"></color-picker>',
                'show' => "facet.ghost_ratings != 'no'"
            ]
        ];
    }


    /**
     * Output front-end scripts
     */
    function front_scripts() {
        FWP()->display->json['rating']['& up'] = facetwp_i18n( __( '& up', 'fwp-front' ) );
        FWP()->display->json['rating']['Undo'] = facetwp_i18n( __( 'Undo', 'fwp-front' ) );

        $facets = FWP()->helper->get_facets_by( 'type', 'rating' );

        $styles = '';

        foreach ( $facets AS $facet ) {

            $color = ( isset( $facet[ 'color' ] ) ) ? $facet[ 'color' ] : '#cccccc';
            $selected = ( isset( $facet[ 'color_selected' ] ) ) ? $facet[ 'color_selected' ] : '#000000';
            $undo = ( isset( $facet[ 'color_undo' ] ) ) ? $facet[ 'color_undo' ] : '#ff0000';
            $ghosts = ( isset( $facet[ 'color_ghosts' ] ) ) ? $facet[ 'color_ghosts' ] : '#eeeeee';

            $styles .= '
                .facetwp-facet-' . $facet['name'] . ' .facetwp-star { color: ' . esc_attr( $color ) . ' }
                .facetwp-facet-' . $facet['name'] . ' .facetwp-star:not(.disabled):hover, .facetwp-star:not(.disabled):hover ~ .facetwp-star, .facetwp-star.selected, .facetwp-star.selected ~ .facetwp-star  { color: ' . esc_attr( $selected ) . '; }
                .facetwp-facet-' . $facet['name'] . ' .facetwp-star.selected:hover, .facetwp-star.selected:hover ~ .facetwp-star { color: ' . esc_attr( $undo ) . '; }
                .facetwp-facet-' . $facet['name'] . ' .facetwp-star.disabled, .facetwp-facet-' . $facet['name'] . ' .facetwp-star.disabled:hover { color: ' . esc_attr( $ghosts ) . '; }
            ';
        }

        if ( !empty( $styles ) ) {
            echo '<style>' . $styles . '</style>';
        }
    }
}
