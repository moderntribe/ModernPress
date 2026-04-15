<?php

class FacetWP_Diff
{

    /**
     * Compare "facetwp_settings" with "facetwp_settings_last_index" to determine
     * whether the user needs to rebuild the index
     * @since 3.0.9
     */
    function is_reindex_needed() {
        $s1 = FWP()->helper->load_settings();
        $s2 = FWP()->helper->load_settings( true );

        // Compare settings
        $to_check = [ 'thousands_separator', 'decimal_separator', 'wc_enable_variations', 'wc_index_all' ];

        foreach ( $to_check as $name ) {
            $attr1 = $this->get_attr( $name, $s1['settings'] );
            $attr2 = $this->get_attr( $name, $s2['settings'] );
            if ( $attr1 !== $attr2 ) {
                return true;
            }
        }

        // Get facets, removing non-indexable ones
        $f1 = array_filter( $s1['facets'], [ $this, 'is_indexable' ] );
        $f2 = array_filter( $s2['facets'], [ $this, 'is_indexable' ] );

        // The facet count is different
        if ( count( $f1 ) !== count( $f2 ) ) {
            return true;
        }

        // Sort the facets alphabetically
        usort( $f1, function( $a, $b ) {
            return strcmp( $a['name'], $b['name'] );
        });

        usort( $f2, function( $a, $b ) {
            return strcmp( $a['name'], $b['name'] );
        });

        // Compare facet properties
        $to_check = [ 'name', 'type', 'source', 'source_other', 'parent_term', 'hierarchical', 'modifier_type', 'modifier_values' ];

        foreach ( $f1 as $index => $facet ) {
            foreach ( $to_check as $attr ) {
                $attr1 = $this->get_attr( $attr, $facet );
                $attr2 = $this->get_attr( $attr, $f2[ $index ] );
                if ( $attr1 !== $attr2 ) {
                    return true;
                }
            }
        }

        return false;
    }


    function is_indexable( $facet ) {
        return ! in_array( $facet['type'], [ 'search', 'pager', 'reset', 'sort' ] );
    }


    /**
     * Get an array element
     * @since 3.0.9
     */
    function get_attr( $name, $collection ) {
        return $collection[ $name ] ?? false;
    }
}
