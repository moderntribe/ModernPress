<?php

class FacetWP_Facet_Reset extends FacetWP_Facet
{

    function __construct() {
        $this->label = __( 'Reset', 'fwp' );
        $this->fields = [ 'reset_ui', 'reset_text', 'reset_mode', 'reset_facets', 'auto_hide' ];
    }


    function render( $params ) {
        $facet = $params['facet'];
        $reset_ui = $facet['reset_ui'];
        $reset_text = empty( $facet['reset_text'] ) ? __( 'Reset', 'fwp-front' ) : $facet['reset_text'];
        $reset_text = facetwp_i18n( $reset_text );

        $classes = [ 'facetwp-reset' ];
        $attrs = '';

        if ( ! FWP()->helper->facet_is( $facet, 'reset_mode', 'off' ) ) {
            if ( ! empty( $facet['reset_facets'] ) ) {
                $vals = implode( ',', $facet['reset_facets'] );
                $attrs = ' data-mode="{mode}" data-values="{vals}"';
                $attrs = str_replace( '{mode}', $facet['reset_mode'], $attrs );
                $attrs = str_replace( '{vals}', esc_attr( $vals ), $attrs );
            }
        }

        if ( FWP()->helper->facet_is( $facet, 'auto_hide', 'yes' ) ) {
            $classes[] = 'facetwp-hide-empty';
        }

        if ( 'button' == $reset_ui ) {
            $output = '<button class="{classes}"{attrs}>{label}</button>';
        }
        else {
            $output = '<a class="{classes}" href="javascript:;"{attrs}>{label}</a>';
        }

        $output = str_replace( '{classes}', implode( ' ', $classes ), $output );
        $output = str_replace( '{label}', esc_attr( $reset_text ), $output );
        $output = str_replace( '{attrs}', $attrs, $output );
        return $output;
    }


    function filter_posts( $params ) {
        return 'continue';
    }


    function register_fields() {
        return [
            'reset_ui' => [
                'type' => 'select',
                'label' => __( 'Reset UI', 'fwp' ),
                'choices' => [
                    'button' => __( 'Button', 'fwp' ),
                    'link' => __( 'Link', 'fwp' )
                ]
            ],
            'reset_mode' => [
                'type' => 'select',
                'label' => __( 'Include / exclude', 'fwp' ),
                'notes' => 'Include or exclude certain facets?',
                'choices' => [
                    'off' => __( 'Reset everything', 'fwp' ),
                    'include' => __( 'Reset only these facets', 'fwp' ),
                    'exclude' => __( 'Reset all except these facets', 'fwp' )
                ]
            ],
            'reset_facets' => [
                'label' => '',
                'html' => '<facet-names :facet="facet" setting="reset_facets"></facet-names>',
                'show' => "facet.reset_mode != 'off'"
            ],
            'auto_hide' => [
                'type' => 'toggle',
                'label' => __( 'Auto-hide', 'fwp' ),
                'notes' => 'Hide when no facets have selected values'
            ]
        ];
    }
}
