<?php

class FacetWP_Facet_Pager extends FacetWP_Facet
{

    public $pager_args;


    function __construct() {
        $this->label = __( 'Pager', 'fwp' );
        $this->fields = [ 'pager_type', 'inner_size', 'dots_label', 'prev_label', 'next_label',
            'count_text_plural', 'count_text_singular', 'count_text_none', 'scroll_target', 'scroll_offset',
            'load_more_text', 'loading_text', 'default_label', 'per_page_options'
        ];
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {
        $facet = $params['facet'];
        $pager_type = $facet['pager_type'];
        $this->pager_args = FWP()->facet->pager_args;

        $method = 'render_' . $pager_type;
        if ( method_exists( $this, $method ) ) {
            return $this->$method( $facet );
        }
    }


    function render_numbers( $facet ) {
        $inner_size = (int) $facet['inner_size'];
        $dots_label = facetwp_i18n( $facet['dots_label'] );
        $prev_label = facetwp_i18n( $facet['prev_label'] );
        $next_label = facetwp_i18n( $facet['next_label'] );

        $output = '';
        $page = (int) $this->pager_args['page'];
        $total_pages = (int) $this->pager_args['total_pages'];
        $inner_first = max( $page - $inner_size, 2 );
        $inner_last = min( $page + $inner_size, $total_pages - 1 );

        if ( 1 < $total_pages ) {

            // Prev button
            if ( 1 < $page && '' != $prev_label ) {
                $output .= $this->render_page( $page - 1, $prev_label, 'prev' );
            }

            // First page
            $output .= $this->render_page( 1, false, 'first' );

            // Dots
            if ( 2 < $inner_first && '' != $dots_label ) {
                $output .= $this->render_page( '', $dots_label, 'dots' );
            }

            for ( $i = $inner_first; $i <= $inner_last; $i++ ) {
                $output .= $this->render_page( $i );
            }

            // Dots
            if ( $inner_last < $total_pages - 1 && '' != $dots_label ) {
                $output .= $this->render_page( '', $dots_label, 'dots' );
            }

            // Last page
            $output .= $this->render_page( $total_pages, false, 'last' );

            // Next button
            if ( $page < $total_pages && '' != $next_label ) {
                $output .= $this->render_page( $page + 1, $next_label, 'next' );
            }
        }

        return '<div class="facetwp-pager">' . $output . '</div>';
    }


    function render_page( $page, $label = false, $extra_class = false ) {
        $label = ( false === $label ) ? $page : $label;
        $class = 'facetwp-page';

        if ( ! empty( $extra_class ) ) {
            $class .= ' ' . $extra_class;
        }

        if ( $page == $this->pager_args['page'] ) {
            $class .= ' active';
        }

        $data = empty( $page ) ? '' : ' data-page="' . $page . '"';
        $html = '<a class="' . $class . '"' . $data . '>' . $label . '</a>';

        return apply_filters( 'facetwp_facet_pager_link', $html, [
            'page' => $page,
            'label' => $label,
            'extra_class' => $extra_class
        ]);
    }


    function render_counts( $facet ) {
        $text_singular = facetwp_i18n( $facet['count_text_singular'] );
        $text_plural = facetwp_i18n( $facet['count_text_plural'] );
        $text_none = facetwp_i18n( $facet['count_text_none'] );

        $page = $this->pager_args['page'];
        $per_page = $this->pager_args['per_page'];
        $total_rows = $this->pager_args['total_rows'];
        $total_rows_unfiltered = $this->pager_args['total_rows_unfiltered'];
        $total_pages = $this->pager_args['total_pages'];

        if ( -1 == $per_page ) {
            $per_page = $total_rows;
        }

        if ( 1 < $total_rows ) {
            $lower = ( 1 + ( ( $page - 1 ) * $per_page ) );
            $upper = ( $page * $per_page );
            $upper = ( $total_rows < $upper ) ? $total_rows : $upper;

            // If a load_more pager is in use, force $lower = 1
            if ( FWP()->helper->facet_setting_exists( 'pager_type', 'load_more' ) ) {
                $lower = 1;
            }

            $output = $text_plural;
            $output = str_replace( '[lower]', $lower, $output );
            $output = str_replace( '[upper]', $upper, $output );
            $output = str_replace( '[total]', $total_rows, $output );
            $output = str_replace( '[total_unfiltered]', $total_rows_unfiltered, $output );
            $output = str_replace( '[page]', $page, $output );
            $output = str_replace( '[per_page]', $per_page, $output );
            $output = str_replace( '[total_pages]', $total_pages, $output );
        }
        else {
            $output = ( 0 < $total_rows ) ? $text_singular : $text_none;
        }

        return $output;
    }


    function render_load_more( $facet ) {
        $text = facetwp_i18n( $facet['load_more_text'] );
        $loading_text = facetwp_i18n( $facet['loading_text'] );

        return '<button class="facetwp-load-more" data-loading="' . esc_attr( $loading_text ) . '">' . esc_attr( $text ) . '</button>';
    }


    function render_per_page( $facet ) {
        $default = facetwp_i18n( $facet['default_label'] );
        $options = explode( ',', $facet['per_page_options'] );
        $options = array_map( 'trim', $options );
        $output = '';

        if ( ! empty( $default ) ) {
            $output .= '<option value="">' . esc_attr( $default ) . '</option>';
        }

        $per_page = $this->pager_args['per_page'];
        $var_exists = isset( FWP()->request->url_vars['per_page'] );

        foreach ( $options as $option ) {
            $val = $label = $option;

            // Support "All" option
            if ( ! ctype_digit( $val ) ) {
                $val = -1;
                $label = facetwp_i18n( $label );
            }

            $selected = ( $var_exists && $val == $per_page ) ? ' selected' : '';
            $output .= '<option value="' . $val . '"' . $selected . '>' . esc_attr( $label ) . '</option>';
        }

        return '<select class="facetwp-per-page-select">' . $output . '</select>';
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        return 'continue';
    }


    /**
     * (Front-end) Attach settings to the AJAX response
     */
    function settings_js( $params ) {
        $facet = $params['facet'];
        $settings[ 'pager_type' ] =  $facet['pager_type'];

        if ( 'numbers' == $facet['pager_type'] ) {
            $settings[ 'scroll_target' ] =  $facet['scroll_target'] ?? '';
            $settings[ 'scroll_offset' ] =  (int) ( $facet['scroll_offset'] ?? '' );
        }

        return $settings;
    }


    function register_fields() {
        return [
            'pager_type' => [
                'type' => 'select',
                'label' => __( 'Pager type', 'fwp' ),
                'choices' => [
                    'numbers' => __( 'Page numbers', 'fwp' ),
                    'counts' => __( 'Result counts', 'fwp' ),
                    'load_more' => __( 'Load more', 'fwp' ),
                    'per_page' => __( 'Per page', 'fwp' )
                ]
            ],
            'inner_size' => [
                'label' => __( 'Inner size', 'fwp' ),
                'notes' => 'Number of pages to show on each side of the current page',
                'default' => 2,
                'show' => "facet.pager_type == 'numbers'"
            ],
            'dots_label' => [
                'label' => __( 'Dots label', 'fwp' ),
                'notes' => 'The filler between the inner and outer pages',
                'default' => '…',
                'show' => "facet.pager_type == 'numbers'"
            ],
            'prev_label' => [
                'label' => __( 'Prev button label', 'fwp' ),
                'notes' => 'Leave blank to hide',
                'default' => '« Prev',
                'show' => "facet.pager_type == 'numbers'"
            ],
            'next_label' => [
                'label' => __( 'Next button label', 'fwp' ),
                'notes' => 'Leave blank to hide',
                'default' => 'Next »',
                'show' => "facet.pager_type == 'numbers'"
            ],
            'scroll_target' => [
                'label' => __( 'Scroll target', 'fwp' ),
                'notes' => 'Add a class or ID to target for scroll up on paging.  Use "body" for top of page or ".facetwp-template" for top of results.  Leave blank for no scrolling.',
                'default' => '',
                'placeholder' => 'e.g. .facetwp-template',
                'show' => "facet.pager_type == 'numbers'"
            ],
            'scroll_offset' => [
                'label' => __( 'Scroll offset', 'fwp' ),
                'notes' => 'Number of px to modify scroll target position, for example 100 will be 100px below target, -100 will be 100px above target',
                'default' => '',
                'show' => "facet.scroll_target != '' && facet.pager_type == 'numbers'"
            ],
            'count_text_plural' => [
                'label' => __( 'Count text (plural)', 'fwp' ),
                'notes' => 'Available tags: [lower], [upper], [total], [page], [per_page], [total_pages]',
                'default' => '[lower] - [upper] of [total] results',
                'show' => "facet.pager_type == 'counts'"
            ],
            'count_text_singular' => [
                'label' => __( 'Count text (singular)', 'fwp' ),
                'default' => '1 result',
                'show' => "facet.pager_type == 'counts'"
            ],
            'count_text_none' => [
                'label' => __( 'Count text (no results)', 'fwp' ),
                'default' => 'No results',
                'show' => "facet.pager_type == 'counts'"
            ],
            'load_more_text' => [
                'label' => __( 'Load more text', 'fwp' ),
                'default' => 'Load more',
                'show' => "facet.pager_type == 'load_more'"
            ],
            'loading_text' => [
                'label' => __( 'Loading text', 'fwp' ),
                'default' => 'Loading...',
                'show' => "facet.pager_type == 'load_more'"
            ],
            'default_label' => [
                'label' => __( 'Default label', 'fwp' ),
                'default' => 'Per page',
                'show' => "facet.pager_type == 'per_page'"
            ],
            'per_page_options' => [
                'label' => __( 'Per page options', 'fwp' ),
                'notes' => 'A comma-separated list of choices. Optionally add a non-numeric choice to be used as a "Show all" option.',
                'default' => '10, 25, 50, 100',
                'show' => "facet.pager_type == 'per_page'"
            ]
        ];
    }
}
