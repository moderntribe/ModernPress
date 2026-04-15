<?php

class FacetWP_Builder
{

    public $css = [];
    public $data = [];
    public $custom_css;


    function __construct() {
        add_filter( 'facetwp_query_args', [ $this, 'hydrate_date_values' ], 999 );
        add_filter( 'facetwp_builder_dynamic_tag_value', [ $this, 'dynamic_tag_value' ], 0, 3 );
        add_action( 'admin_enqueue_scripts', [ $this, 'initialize_builder_editor' ] );
    }


    /**
     * Generate CSS class string (helper method)
     * @since 3.9.3
     */
    function get_classes( $type, $settings ) {
        $classes = [ $type, $settings['name'], $settings['css_class'] ];
        return trim( implode( ' ', $classes ) );
    }


    /**
     * Generate the layout HTML
     * @since 3.2.0
     */
    function render_layout( $layout ) {
        global $wp_query, $post;

        $counter = 0;
        $settings = $layout['settings'];

        $this->custom_css = "@media (max-width: 480px) { \n    body .facetwp-template .fwpl-layout,  \n    body .facetwp-template-static .fwpl-layout { grid-template-columns: 1fr; } \n} \n";

        $this->custom_css .= $settings['custom_css'];

        $selector = '.fwpl-layout';
        $selector .= empty( $settings['name'] ) ? '' : '.' . $settings['name'];

        $this->css = [
            '.fwpl-layout, .fwpl-row' => [
                'display' => 'grid'
            ],
            $selector => [
                'grid-template-columns' => 'repeat(' . $settings['num_columns'] . ', 1fr)',
                'grid-gap' => $settings['grid_gap'] . 'px'
            ],
            '.fwpl-btn'  => [
                'text-decoration' =>  'none'
            ],
            $selector . ' .fwpl-result' => $this->build_styles( $settings )
        ];

        $classes = $this->get_classes( 'fwpl-layout', $settings );

        $output = '<div class="' . $classes . '">';

        if ( have_posts() ) {
            while ( have_posts() ) : the_post();
                $counter++;

                // Default dynamic tags
                $tags = [
                    'post:id'       => $post->ID,
                    'post:name'     => $post->post_name,
                    'post:type'     => $post->post_type,
                    'post:title'    => $post->post_title,
                    'post:url'      => get_permalink()
                ];

                $params = [
                    'layout' => $layout,
                    'post' => $post
                ];

                $this->data = apply_filters( 'facetwp_builder_dynamic_tags', $tags, $params );

                $output .= '<div class="fwpl-result r' . $counter . '">';

                foreach ( $layout['items'] as $row ) {
                    $output .= $this->render_row( $row );
                }

                $output .= '</div>';

                $output = $this->parse_dynamic_tags( $output, $params );

            endwhile;
        }
        else {
            $no_results_text = $settings['no_results_text'] ?? '';
            $output .= do_shortcode( $no_results_text );
        }

        $output .= '</div>';

        $output .= $this->render_css();

        return $output;
    }


    /**
     * Generate the row HTML
     * @since 3.2.0
     */
    function render_row( $row ) {
        $settings = $row['settings'];

        $this->css['.fwpl-row.' . $settings['name'] ] = $this->build_styles( $settings );

        $classes = $this->get_classes( 'fwpl-row', $settings );

        $output = '<div class="' . $classes . '">';

        foreach ( $row['items'] as $col ) {
            $output .= $this->render_col( $col );
        }

        $output .= '</div>';

        return $output;
    }


    /**
     * Generate the col HTML
     * @since 3.2.0
     */
    function render_col( $col ) {
        $settings = $col['settings'];

        $this->css['.fwpl-col.' . $settings['name'] ] = $this->build_styles( $settings );

        $classes = $this->get_classes( 'fwpl-col', $settings );

        $output = '<div class="fwpl-col ' . $classes . '">';

        foreach ( $col['items'] as $item ) {
            if ( 'row' == $item['type'] ) {
                $output .= $this->render_row( $item );
            }
            elseif ( 'item' == $item['type'] ) {
                $output .= $this->render_item( $item );
            }
        }

        $output .= '</div>';

        return $output;
    }


    /**
     * Generate the item HTML
     * @since 3.2.0
     */
    function render_item( $item ) {
        global $post;

        $settings = $item['settings'];
        $name = $settings['name'];
        $source = $item['source'];
        $value = $source;

        $selector = '.fwpl-item.' . $name;
        $selector = ( 'button' == $source ) ? $selector . ' .fwpl-btn' : $selector;
        $this->css[ $selector ] = $this->build_styles( $settings );

        if ( 0 === strpos( $source, 'post_' ) || 'ID' == $source ) {
            if ( 'post_title' == $source ) {
                $value = $this->linkify( $post->$source, $settings['link'] );
            }
            elseif ( 'post_excerpt' == $source ) {
                $value = get_the_excerpt( $post->ID );
            }
            elseif ( 'post_content' == $source ) {
                $value = apply_filters( 'the_content', $post->post_content );
            }
            elseif ( 'post_author' == $source ) {
                $field = $settings['author_field'];
                $user = get_user_by( 'id', $post->$source );
                $value = $user->$field;
            }
            elseif ( 'post_type' == $source ) {
                $pt_obj = get_post_type_object( $post->$source );
                $value = $pt_obj->labels->singular_name ?? $post->$source;
            }
            else {
                $value = $post->$source;
            }
        }
        elseif ( 0 === strpos( $source, 'cf/attribute_' ) && 'product' == get_post_type( $post->ID ) ) {
            $value = '';
            $product = wc_get_product( $post->ID );
            $attr = substr( $source, 13 );
            $attributes = array_filter( $product->get_attributes(), 'wc_attributes_array_filter_visible' );
            if ( isset( $attributes[ $attr ] ) ) {
                $attribute = $attributes[ $attr ];
                if ( $attribute->is_taxonomy() ) {
                    $attribute_taxonomy = $attribute->get_taxonomy_object();
                    $attribute_values = wc_get_product_terms( $product->get_id(), $attribute->get_name(), [ 'fields' => 'all' ] );

                    foreach ( $attribute_values as $attribute_value ) {
                        $value_name = esc_html( $attribute_value->name );

                        if ( $attribute_taxonomy->attribute_public ) {
                            $values[] = '<a href="' . esc_url( get_term_link( $attribute_value->term_id, $attribute->get_name() ) ) . '" rel="tag">' . $value_name . '</a>';
                        }
                        else {
                            $values[] = $value_name;
                        }
                    }
                }
                else {
                    $values = $attribute->get_options();

                    foreach ( $values as &$value ) {
                        $value = make_clickable( esc_html( $value ) );
                    }
                }

                $value = implode( ", ", $values );
            }
        }
        elseif ( 0 === strpos( $source, 'cf/' ) ) {
            $value = get_post_meta( $post->ID, substr( $source, 3 ), true );
            $value = $this->linkify( $value, $settings['link'] );
        }
        elseif ( 0 === strpos( $source, 'tax/' ) ) {
            $temp = [];
            $taxonomy = substr( $source, 4 );
            $terms = get_the_terms( $post->ID, $taxonomy );

            if ( is_array( $terms ) ) {
                foreach ( $terms as $term_obj ) {
                    $term = $this->linkify( $term_obj->name, $settings['term_link'], [
                            'term_id' => $term_obj->term_id,
                            'taxonomy' => $taxonomy
                    ] );

                    $temp[] = '<span class="fwpl-term fwpl-term-' . $term_obj->slug . ' fwpl-tax-' . $taxonomy . '">' . $term . '</span>';
                }
            }

            $value = implode( $settings['separator'], $temp );
        }
        elseif ( 0 === strpos( $source, 'woo/' ) ) {
            $field = substr( $source, 4 );
            $product = wc_get_product( $post->ID );

            // Invalid product
            if ( ! is_object( $product ) ) {
                $value = '';
            }

            // Price
            elseif ( 'price' == $field || 'sale_price' == $field || 'regular_price' == $field ) {
                if ( $product->is_type( 'variable' ) ) {
                    $method_name = "get_variation_$field";
                    $value = $product->$method_name( 'min' ); // get_variation_price()
                }
                else {
                    $method_name = "get_$field";
                    $value = $product->$method_name(); // get_price()
                }
            }

            // Average Rating
            elseif ( 'average_rating' == $field ) {
                $value = $product->get_average_rating();
            }

            // Stock Status
            elseif ( 'stock_status' == $field ) {
                $value = $product->is_in_stock() ? __( 'In Stock', 'fwp' ) : __( 'Out of Stock', 'fwp' );
            }

            // On Sale
            elseif ( 'on_sale' == $field ) {
                $value = $product->is_on_sale() ? __( 'On Sale', 'fwp' ) : '';
            }

            // Product Type
            elseif ( 'product_type' == $field ) {
                $value = $product->get_type();
            }
        }
        elseif ( 0 === strpos( $source, 'acf/' ) && isset( FWP()->acf ) ) {
            $value = FWP()->acf->get_field( $source, $post->ID );
        }
        elseif ( 'featured_image' == $source ) {
            $value = get_the_post_thumbnail( $post->ID, $settings['image_size'] );
            $value = $this->linkify( $value, $settings['link'] );
        }
        elseif ( 'button' == $source ) {
            $settings['link']['class'] = 'fwpl-btn';
            $value = $this->linkify( facetwp_i18n( $settings['button_text'] ), $settings['link'] );
        }
        elseif ( 'html' == $source ) {
            $value = do_shortcode( $settings['content'] );
        }

        // Date format
        if ( ! empty( $settings['date_format'] ) && ! empty( $value ) ) {
            if ( ! empty( $settings['input_format'] ) ) {
                $date = DateTime::createFromFormat( $settings['input_format'], $value );
            }
            else {
                $date = new DateTime( $value );
            }

            // Use wp_date() to support i18n
            if ( $date ) {
                $value = wp_date( $settings['date_format'], $date->getTimestamp(), new DateTimeZone( 'UTC' ) );
            }
        }

        // Number format
        if ( ! empty( $settings['number_format'] ) && ! empty( $value ) ) {
            $decimals = 2;
            $format = $settings['number_format'];
            $decimal_sep = FWP()->helper->get_setting( 'decimal_separator' );
            $thousands_sep = FWP()->helper->get_setting( 'thousands_separator' );

            // No thousands separator
            if ( false === strpos( $format, ',' ) ) {
                $thousands_sep = '';
            }

            // Handle decimals
            if ( false === ( $pos = strpos( $format, '.' ) ) ) {
                $decimals = 0;
            }
            else {
                $decimals = strlen( $format ) - $pos - 1;
            }

            $value = number_format( $value, $decimals, $decimal_sep, $thousands_sep );
        }

        $output = '';
        $prefix = $settings['prefix'] ?? '';
        $suffix = $settings['suffix'] ?? '';

        // Allow value hooks
        $value = apply_filters( 'facetwp_builder_item_value', $value, $item );

        // Convert array to string
        if ( is_array( $value ) ) {
            $value = implode( ', ', $value );
        }

        // Store the RAW short-tag
        $this->data[ "$name:raw" ] = $value;

        // Attach the prefix / suffix to the value
        if ( '' != $value ) {
            $value = $prefix . $value . $suffix;
        }

        // Store the short-tag
        $this->data[ $name ] = $value;

        // Build the list of CSS classes
        $classes = $this->get_classes( 'fwpl-item', $settings );

        if ( '' == $value ) {
            $classes .= ' is-empty';
        }

        // Prevent output
        if ( ! $settings['is_hidden'] ) {
            $output = '<div class="' . $classes . '">' . $value . '</div>';
        }

        return $output;
    }


    /**
     * Parse dynamic tags, e.g. {{ first_name }}
     */
    function parse_dynamic_tags( $output, $params ) {
        $pattern = '/({{[ ]?(.*?)[ ]?}})/s';

        return preg_replace_callback( $pattern, function( $matches ) use( $params ) {
            $tag_name = $matches[2];
            $tag_value = $this->data[ $tag_name ] ?? '';
            return apply_filters( 'facetwp_builder_dynamic_tag_value', $tag_value, $tag_name, $params );
        }, $output );
    }


    /**
     * Calculate some dynamic tag values on-the-fly, to prevent
     * unnecessary queries and extra load time
     */
    function dynamic_tag_value( $tag_value, $tag_name, $params ) {
        if ( 'post:image' == $tag_name ) {
            $tag_value = get_the_post_thumbnail_url( $params['post']->ID, 'full' );
        }

        return $tag_value;
    }


    /**
     * Build the redundant styles (border, padding,etc)
     * @since 3.2.0
     */
    function build_styles( $settings ) {
        $styles = [];

        if ( isset( $settings['grid_template_columns'] ) ) {
            $styles['grid-template-columns'] = $settings['grid_template_columns'];
        }
        if ( isset( $settings['border'] ) ) {
            $styles['border-style'] = $settings['border']['style'];
            $styles['border-color'] = $settings['border']['color'];
            $styles['border-width'] = $this->get_widths( $settings['border']['width'] );
        }
        if ( isset( $settings['background_color'] ) ) {
            $styles['background-color'] = $settings['background_color'];
        }
        if ( isset( $settings['padding'] ) ) {
            $styles['padding'] = $this->get_widths( $settings['padding'] );
        }
        if ( isset( $settings['text_style'] ) ) {
            $styles['text-align'] = $settings['text_style']['align'];
            $styles['font-weight'] = $settings['text_style']['bold'] ? 'bold' : '';
            $styles['font-style'] = $settings['text_style']['italic'] ? 'italic' : '';
        }
        if ( isset( $settings['font_size'] ) ) {
            $styles['font-size'] = $settings['font_size']['size'] . $settings['font_size']['unit'];
        }
        if ( isset( $settings['text_color'] ) ) {
            $styles['color'] = $settings['text_color'];
        }
        if ( isset( $settings['button_border'] ) ) {
            $border = $settings['button_border'];
            $width = $border['width'];
            $unit = $width['unit'];

            $styles['color'] = $settings['button_text_color'];
            $styles['background-color'] = $settings['button_color'];
            $styles['padding'] = $this->get_widths( $settings['button_padding'] );
            $styles['border-style'] = $border['style'];
            $styles['border-color'] = $border['color'];
            $styles['border-top-width'] = $width['top'] . $unit;
            $styles['border-right-width'] = $width['right'] . $unit;
            $styles['border-bottom-width'] = $width['bottom'] . $unit;
            $styles['border-left-width'] = $width['left'] . $unit;
        }

        return $styles;
    }


    /**
     * Build the CSS widths, e.g. for "padding" or "border-width"
     * @since 3.2.0
     */
    function get_widths( $data ) {
        $unit = $data['unit'];
        $top = $data['top'];
        $right = $data['right'];
        $bottom = $data['bottom'];
        $left = $data['left'];

        if ( $top == $right && $right == $bottom && $bottom == $left ) {
            return "$top$unit";
        }
        elseif ( $top == $bottom && $left == $right ) {
            return "$top$unit $left$unit";
        }

        return "$top$unit $right$unit $bottom$unit $left$unit";
    }


    /**
     * Convert a value into a link
     * @since 3.2.0
     */
    function linkify( $value, $link_data, $term_data = [] ) {
        global $post;

        $type = $link_data['type'] ?? '';
        $href = $link_data['href'] ?? '';
        $class = $link_data['class'] ?? '';
        $target = $link_data['target'] ?? '';

        if ( 'none' !== $type ) {
            if ( 'post' == $type ) {
                $href = get_permalink();
            }
            if ( 'term' == $type ) {
                $href = get_term_link( $term_data['term_id'], $term_data['taxonomy'] );
            }

            if ( ! empty( $target ) ) {
                $target = ' target="' . $target . '"';
            }

            if ( ! empty( $class ) ) {
                $class = ' class="' . $class . '"';
            }

            $value = '<a href="' . $href . '"' . $class . $target . '>' . $value . '</a>';
        }

        return $value;
    }


    /**
     * Turn the CSS array into valid CSS
     * @since 3.2.0
     */
    function render_css() {
        $output = "\n<style>\n";

        foreach ( $this->css as $selector => $props ) {
            $valid_rules = $this->get_valid_css_rules( $props );

            if ( ! empty( $valid_rules ) ) {
                $output .= $selector . " {\n";
                foreach ( $valid_rules as $prop => $value ) {
                    $output .= "    $prop: $value;\n";
                }
                $output .= "}\n";
            }
        }

        if ( ! empty( $this->custom_css ) ) {
            $output .= $this->custom_css . "\n";
        }

        $output .= "</style>\n";

        return $output;
    }


    /**
     * Filter out empty or invalid rules
     * @since 3.2.0
     */
    function get_valid_css_rules( $props ) {
        $rules = [];

        foreach ( $props as $prop => $value ) {
            if ( $this->is_valid_css_rule( $prop, $value ) ) {
                $rules[ $prop ] = $value;
            }
        }

        return $rules;
    }


    /**
     * Optimize CSS rules
     * @since 3.2.0
     */
    function is_valid_css_rule( $prop, $value ) {
        $return = true;

        if ( empty( $value ) || 'px' === $value || '0px' === $value || 'none' === $value ) {
            $return = false;
        }

        if ( 'font-size' === $prop && '0px' === $value ) {
            $return = false;
        }

        if ( 'text-decoration' === $prop && 'none' === $value ) {
            $return = true;
        }

        return $return;
    }


    /**
     * Make sure the query is valid
     * @since 3.2.0
     */
    function parse_query_obj( $query_obj ) {
        $output = [];
        $tax_query = [];
        $meta_query = [];
        $date_query = [];
        $post_type = 'any';
        $post_status = [ 'publish' ];
        $posts_per_page = 10;
        $post_in = [];
        $post_not_in = [];
        $author_in = [];
        $author_not_in = [];
        $orderby = [];

        if ( ! empty( $query_obj['posts_per_page'] ) ) {
            $posts_per_page = (int) $query_obj['posts_per_page'];
        }

        if ( ! empty( $query_obj['post_type'] ) ) {
            $post_type = array_column( $query_obj['post_type'], 'value' );
        }

        if ( empty( $query_obj['filters'] ) ) {
            $query_obj['filters'] = [];
        }

        if ( empty( $query_obj['orderby'] ) ) {
            $query_obj['orderby'] = [];
        }

        foreach ( $query_obj['filters'] as $filter ) {
            $key = $filter['key'];
            $value = $filter['value'];
            $compare = $filter['compare'];
            $type = $filter['type'];

            // Cast as decimal for more accuracy
            $type = ( 'NUMERIC' == $type ) ? 'DECIMAL(16,4)' : $type;
            $exists_bypass = false;
            $value_bypass = false;

            // Clear the value for certain compare types
            if ( in_array( $compare, [ 'EXISTS', 'NOT EXISTS', 'EMPTY', 'NOT EMPTY' ] ) ) {
                $value_bypass = true;
                $value = '';
            }

            if ( in_array( $compare, [ 'EXISTS', 'NOT EXISTS' ] ) ) {
                $exists_bypass = true;
            }

            // If "EMPTY", use "=" compare type w/ empty string value
            if ( in_array( $compare, [ 'EMPTY', 'NOT EMPTY' ] ) ) {
                $compare = ( 'EMPTY' == $compare ) ? '=' : '!=';
            }

            // Handle multiple values
            if ( is_array( $value ) ) {
                if ( in_array( $compare, [ '=', '!=' ] ) ) {
                    $compare = ( '=' == $compare ) ? 'IN' : 'NOT IN';
                }

                if ( ! in_array( $compare, [ 'IN', 'NOT IN' ] ) ) {
                    $value = $value[0];
                }
            }

            if ( empty( $value ) && ! $value_bypass ) {
                continue;
            }

            // Support dynamic URL vars
            $value = $this->parse_uri_tags( $value );

            // Prepend with "date|" so we can populate with hydrate_date_values()
            if ( 'DATE' == $type ) {
                $value = 'date|' . $value;
            }

            if ( 'ID' == $key ) {
                $arg_name = ( 'IN' == $compare ) ? 'post_in' : 'post_not_in';
                $$arg_name = $value;
            }
            elseif ( 'post_author' == $key ) {
                $arg_name = ( 'IN' == $compare ) ? 'author_in' : 'author_not_in';
                $$arg_name = $value;
            }
            elseif ( 'post_status' == $key ) {
                $post_status = $value;
            }
            elseif ( 'post_date' == $key || 'post_modified' == $key ) {
                if ( '>' == $compare || '>=' == $compare ) {
                    $date_query[] = [
                        'after' => $value,
                        'inclusive' => ( '>=' == $compare )
                    ];
                }
                if ( '<' == $compare || '<=' == $compare ) {
                    $date_query[] = [
                        'before' => $value,
                        'inclusive' => ( '<=' == $compare )
                    ];
                }
            }
            elseif ( 0 === strpos( $key, 'tax/' ) ) {
                $temp = [
                    'taxonomy' => substr( $key, 4 ),
                    'field' => 'slug',
                    'operator' => $compare
                ];

                if ( ! $exists_bypass ) {
                    $temp['terms'] = $value;
                }

                $tax_query[] = $temp;
            }
            else {
                $temp = [
                    'key' => substr( $key, strpos( $key, '/' ) + 1 ),
                    'compare' => $compare,
                    'type' => $type
                ];

                if ( ! $exists_bypass ) {
                    $temp['value'] = $value;
                }

                $meta_query[] = $temp;
            }
        }

        foreach ( $query_obj['orderby'] as $index => $data ) {
            if ( 'cf/' == substr( $data['key'], 0, 3 ) ) {
                $type = $data['type'];

                // Cast as decimal for more accuracy
                $type = ( 'NUMERIC' == $type ) ? 'DECIMAL(16,4)' : $type;

                $meta_query['sort_' . $index] = [
                    'key' => substr( $data['key'], 3 ),
                    'type' => $type
                ];

                $orderby['sort_' . $index] = $data['order'];
            }
            else {
                $orderby[ $data['key'] ] = $data['order'];
            }
        }

        $temp = [
            'post_type' => $post_type,
            'post_status' => $post_status,
            'meta_query' => $meta_query,
            'tax_query' => $tax_query,
            'date_query' => $date_query,
            'post__in' => $post_in,
            'post__not_in' => $post_not_in,
            'author__in' => $author_in,
            'author__not_in' => $author_not_in,
            'orderby' => $orderby,
            'posts_per_page' => $posts_per_page
        ];

        foreach ( $temp as $key => $val ) {
            if ( ! empty( $val ) ) {
                $output[ $key ] = $val;
            }
        }

        return $output;
    }


    /**
     * Get necessary values for the layout builder
     * @since 3.2.0
     */
    function get_layout_data() {
        $sources = FWP()->helper->get_data_sources();
        unset( $sources['post'] );

        // Static options
        $output = [
            'row' => 'Child Row',
            'html' => 'HTML',
            'button' => 'Button',
            'featured_image' => 'Featured Image',
            'ID' => 'Post ID',
            'post_title' => 'Post Title',
            'post_name' => 'Post Name',
            'post_content' => 'Post Content',
            'post_excerpt' => 'Post Excerpt',
            'post_date' => 'Post Date',
            'post_modified' => 'Post Modified',
            'post_author' => 'Post Author',
            'post_type' => 'Post Type'
        ];

        foreach ( $sources as $group ) {
            foreach ( $group['choices'] as $name => $label ) {
                $output[ $name ] = $label;
            }
        }

        return $output;
    }


    /**
     * Get necessary data for the query builder
     * @since 3.0.0
     */
    function get_query_data() {
        $builder_post_types = [];

        $post_types = get_post_types( [ 'public' => true ], 'objects' );
        $data_sources = FWP()->helper->get_data_sources( 'builder' );

        // Remove ACF choices
        unset( $data_sources['acf'] );

        foreach ( $post_types as $type ) {
            $builder_post_types[] = [
                'label' => $type->labels->name,
                'value' => $type->name
            ];
        }

        $data_sources['posts']['choices'] = [
            'ID'                => 'ID',
            'post_author'       => 'Post Author',
            'post_status'       => 'Post Status',
            'post_date'         => 'Post Date',
            'post_modified'     => 'Post Modified'
        ];

        return apply_filters( 'facetwp_builder_query_data', [
            'post_types' => $builder_post_types,
            'filter_by' => $data_sources
        ] );
    }


    /**
     * Replace "date|" placeholders with actual dates
     */
    function hydrate_date_values( $query_args ) {
        if ( isset( $query_args['meta_query'] ) ) {
            foreach ( $query_args['meta_query'] as $index => $row ) {
                if ( isset( $row['value'] ) && is_string( $row['value'] ) && 0 === strpos( $row['value'], 'date|' ) ) {
                    $value = trim( substr( $row['value'], 5 ) );
                    $value = date( 'Y-m-d', strtotime( $value ) );
                    $query_args['meta_query'][ $index ]['value'] = $value;
                }
            }
        }

        return $query_args;
    }


    /**
     * Let users pull URI or GET params into the query builder
     * E.g. "http:uri", "http:uri:0", or "http:get:year"
     * @since 3.6.0
     */
    function parse_uri_tags( $values ) {
        $temp = (array) $values;

        foreach ( $temp as $key => $value ) {
            if ( 0 === strpos( $value, 'http:uri' ) ) {
                $uri = FWP()->helper->get_uri();
                $uri_parts = explode( '/', $uri );
                $tag_parts = explode( ':', $value );
                if ( isset( $tag_parts[2] ) ) {
                    $index = (int) $tag_parts[2];
                    $index = ( $index < 0 ) ? count( $uri_parts ) + $index : $index;
                    $temp[ $key ] = $uri_parts[ $index ] ?? '';
                }
                else {
                    $temp[ $key ] = $uri;
                }
            }
            elseif ( 0 === strpos( $value, 'http:get:' ) ) {
                $tag_parts = explode( ':', $value );
                $temp[ $key ] = $_GET[ $tag_parts[2] ] ?? '';
            }
        }

        return is_array( $values ) ? $temp : $temp[0];
    }

    /**
     * Initialize CodeMirror for Listing Builder editors
     * @since 4.3.2
     */
    function initialize_builder_editor( $hook ) {
        if ( 'settings_page_facetwp' == $hook ) {
            $fwp_editor_settings = wp_enqueue_code_editor( array( 'type' => 'php' ) );
            wp_localize_script( 'jquery', 'fwp_editor_settings', $fwp_editor_settings );
        }
    }
}
