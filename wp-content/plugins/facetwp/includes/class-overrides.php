<?php

class FacetWP_Overrides
{

    public $raw;


    function __construct() {
        add_filter( 'facetwp_index_row', [ $this, 'index_row' ], 5, 2 );
        add_filter( 'facetwp_index_row', [ $this, 'format_numbers' ], 15, 2 );
        add_filter( 'facetwp_is_main_query', [ $this, 'ignore_post_types' ], 10, 2 );
        add_filter( 'facetwp_indexer_is_enabled', [ $this, 'disable_indexer' ], 5 );
    }


    /**
     * Indexer modifications
     */
    function index_row( $params, $class ) {
        if ( $class->is_overridden ) {
            return $params;
        }

        $facet = FWP()->helper->get_facet_by_name( $params['facet_name'] );

        // Store raw numbers to format later
        if ( in_array( $facet['type'], [ 'number_range', 'slider' ] ) ) {
            $this->raw = [
                'value' => $params['facet_value'],
                'label' => $params['facet_display_value']
            ];
        }

        // Support "Other data source" values
        if ( ! empty( $facet['source_other'] ) ) {
            $other_params = $params;
            $other_params['facet_source'] = $facet['source_other'];
            $rows = $class->get_row_data( $other_params );
            $params['facet_display_value'] = apply_filters( 'facetwp_index_source_other_value', $rows[0]['facet_display_value'] ?? $params['facet_display_value'], $params );
        }

        return $params;
    }


    /**
     * Make sure that numbers are properly formatted
     */
    function format_numbers( $params, $class ) {

        if ( empty( $this->raw ) ) {
            return $params;
        }

        $value = $params['facet_value'];
        $label = $params['facet_display_value'];

        // Only format if un-altered
        if ( $this->raw['value'] === $value && $this->raw['label'] === $label ) {
            $params['facet_value'] = FWP()->helper->format_number( $this->raw['value'] );
            $params['facet_display_value'] = FWP()->helper->format_number( $this->raw['label'] );
        }

        $this->raw = null;

        return $params;
    }


    /**
     * Ignore certain post types
     */
    function ignore_post_types( $is_main_query, $query ) {
        $blacklist = [
            'acf-field',
            'acf-field-group',
            'advanced_ads',
            'carts',
            'cookielawinfo',
            'edd_wish_list',
            'ms_relationship',
            'nav_menu_item',
            'wc_user_membership',
            'wp_block',
            'wp_global_styles',
            'wp_navigation',
            'wp_template',
            'wp_template_part'
        ];
        $post_type = $query->get( 'post_type' );

        if ( is_string( $post_type ) && in_array( $post_type, $blacklist ) ) {
            $is_main_query = false;
        }

        // Ignore the "WP GDPR Compliance" plugin
        if ( '[wpgdprc_access_request_form]' == $query->get( 's' ) ) {
            $is_main_query = false;
        }

        return $is_main_query;
    }
    
    /**
    * Disable indexer
    */
   function disable_indexer($enabled) {

       if ('no' == FWP()->helper->get_setting('enable_indexer', 'yes')) {
           return false; // enable indexer setting
       }

       return $enabled;
   }
}
