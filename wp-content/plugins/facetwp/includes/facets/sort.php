<?php

class FacetWP_Facet_Sort extends FacetWP_Facet
{

    public $sort_options = [];


    function __construct() {
        $this->label = __( 'Sort', 'fwp' );
        $this->fields = [ 'sort_default_label', 'sort_options' ];

        add_filter( 'facetwp_filtered_query_args', [ $this, 'apply_sort' ], 1, 2 );
        add_filter( 'facetwp_render_output', [ $this, 'render_sort_feature' ], 1, 2 );
    }


    /**
     * Render the sort facet
     */
    function render( $params ) {
        $facet = $this->parse_sort_facet( $params['facet'] );
        $selected_values = (array) $params['selected_values'];

        $label = facetwp_i18n( $facet['default_label'] );
        $output = '<option value="">' . esc_attr( $label ) . '</option>';

        foreach ( $facet['sort_options'] as $key => $choice ) {
            $label = facetwp_i18n( $choice['label'] );
            $selected = in_array( $key, $selected_values ) ? ' selected' : '';
            $output .= '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_attr( $label ) . '</option>';
        }

        return '<select>' . $output . '</select>';
    }


    /**
     * Sort facets don't narrow results
     */
    function filter_posts( $params ) {
        return 'continue';
    }


    /**
     * Register admin settings
     */
    function register_fields() {
        return [
            'sort_default_label' => [
                'type' => 'alias',
                'items' => [
                    'default_label' => [
                        'label' => __( 'Default label', 'fwp' ),
                        'notes' => 'The sort box placeholder text',
                        'default' => __( 'Sort by', 'fwp' )
                    ]
                ]
            ],
            'sort_options' => [
                'label' => __( 'Sort options', 'fwp' ),
                'notes' => 'Define the choices that appear in the sort box',
                'html' => '<sort-options :facet="facet"></sort-options><input type="hidden" class="facet-sort-options" value="[]" />'
            ]
        ];
    }


    /**
     * Convert a sort facet's sort options into WP_Query arguments
     * @since 4.0.8
     */
    function parse_sort_facet( $facet ) {
        $sort_options = [];

        foreach ( $facet['sort_options'] as $row ) {
            $parsed = FWP()->builder->parse_query_obj([ 'orderby' => $row['orderby'] ]);

            $sort_options[ $row['name'] ] = [
                'label' => $row['label'],
                'query_args' => array_intersect_key( $parsed, [
                    'meta_query' => true,
                    'orderby' => true
                ])
            ];
        }

        $sort_options = apply_filters( 'facetwp_facet_sort_options', $sort_options, [
            'facet' => $facet,
            'template_name' => FWP()->facet->template['name']
        ]);

        $facet['sort_options'] = $sort_options;

        return $facet;
    }


    /**
     * Handle both sort facets and the (old) sort feature
     * @since 4.0.6
     */
    function apply_sort( $query_args, $class ) {

        foreach ( $class->facets as $facet ) {
            if ( 'sort' == $facet['type'] ) {
                $sort_facet = $this->parse_sort_facet( $facet );
                break;
            }
        }

        // Support the (old) sort feature
        $sort_value = 'default';
        $this->sort_options = $this->get_sort_options();

        if ( ! empty( $class->ajax_params['extras']['sort'] ) ) {
            $sort_value = $class->ajax_params['extras']['sort'];

            if ( ! empty( $this->sort_options[ $sort_value ] ) ) {
                $args = $this->sort_options[ $sort_value ]['query_args'];
                $query_args = array_merge( $query_args, $args );
            }
        }

        // Preserve relevancy sort
        $use_relevancy = apply_filters( 'facetwp_use_search_relevancy', true, $class );
        $is_default_sort = ( 'default' == $sort_value && empty( $class->http_params['get']['orderby'] ) );
        if ( $class->is_search && $use_relevancy && $is_default_sort && FWP()->is_filtered ) {
            $query_args['orderby'] = 'post__in';
        }

        // Support the (new) sort facet
        if ( ! empty( $sort_facet['selected_values'] ) ) {
            $chosen = $sort_facet['selected_values'][0];
            $sort_options = $sort_facet['sort_options'];

            if ( isset( $sort_options[ $chosen ] ) ) {
                $qa = $sort_options[ $chosen ]['query_args'];

                if ( isset( $qa['meta_query'] ) ) {
                    $meta_query = $query_args['meta_query'] ?? [];
                    $query_args['meta_query'] = array_merge( $meta_query, $qa['meta_query'] );
                }

                $query_args['orderby'] = $qa['orderby'];
            }
        }

        return $query_args;
    }


    /**
     * Generate choices for the (old) sort feature
     * @since 4.0.6
     */
    function get_sort_options() {

        $options = [
            'default' => [
                'label' => __( 'Sort by', 'fwp-front' ),
                'query_args' => []
            ],
            'title_asc' => [
                'label' => __( 'Title (A-Z)', 'fwp-front' ),
                'query_args' => [
                    'orderby' => 'title',
                    'order' => 'ASC',
                ]
            ],
            'title_desc' => [
                'label' => __( 'Title (Z-A)', 'fwp-front' ),
                'query_args' => [
                    'orderby' => 'title',
                    'order' => 'DESC',
                ]
            ],
            'date_desc' => [
                'label' => __( 'Date (Newest)', 'fwp-front' ),
                'query_args' => [
                    'orderby' => 'date',
                    'order' => 'DESC',
                ]
            ],
            'date_asc' => [
                'label' => __( 'Date (Oldest)', 'fwp-front' ),
                'query_args' => [
                    'orderby' => 'date',
                    'order' => 'ASC',
                ]
            ]
        ];

        return apply_filters( 'facetwp_sort_options', $options, [
            'template_name' => FWP()->facet->template['name'],
        ] );
    }


    /**
     * Render the (old) sort feature
     * @since 4.0.6
     */
    function render_sort_feature( $output, $params ) {
        $has_sort = isset( $params['extras']['sort'] );
        $has_choices = isset( $this->sort_options );

        if ( 0 == $params['soft_refresh'] && $has_sort && $has_choices ) {
            $html = '';

            foreach ( $this->sort_options as $key => $atts ) {
                $html .= '<option value="' . $key . '">' . $atts['label'] . '</option>';
            }

            $html = '<select class="facetwp-sort-select">' . $html . '</select>';

            $output['sort'] = apply_filters( 'facetwp_sort_html', $html, [
                'sort_options' => $this->sort_options,
                'template_name' => FWP()->facet->template['name'],
            ]);
        }

        return $output;
    }
}
