<?php

class FacetWP_Facet_Date_Range extends FacetWP_Facet
{

    function __construct() {
        $this->label = __( 'Date Range', 'fwp' );
        $this->fields = [ 'source_other', 'compare_type', 'date_fields', 'date_format' ];
    }


    /**
     * Generate the facet HTML
     */
    function render( $params ) {

        $output = '';
        $value = (array) $params['selected_values'];
        $fields = empty( $params['facet']['fields'] ) ? 'both' : $params['facet']['fields'];

        $text_date = facetwp_i18n( __( 'Date', 'fwp-front' ) );
        $text_start_date = facetwp_i18n( __( 'Start date', 'fwp-front' ) );
        $text_end_date = facetwp_i18n( __( 'End date', 'fwp-front' ) );

        $text_date_empty = facetwp_i18n( __( 'No dates', 'fwp-front' ) );
        $text_start_date_empty = facetwp_i18n( __( 'No start dates', 'fwp-front' ) );
        $text_end_date_empty = facetwp_i18n( __( 'No end dates', 'fwp-front' ) );


        if ( 'exact' == $fields ) {
            $output .= '<input type="text" class="facetwp-date facetwp-date-min" data-empty="' . esc_attr( $text_date_empty ) . '" value="' . esc_attr( $value[0] ?? '' ) . '" placeholder="' . esc_attr( $text_date ) . '" />';
        }
        if ( 'both' == $fields || 'start_date' == $fields ) {
            $output .= '<input type="text" class="facetwp-date facetwp-date-min" data-empty="' . esc_attr( $text_start_date_empty ) . '" value="' . esc_attr( $value[0] ?? '' ) . '" placeholder="' . esc_attr( $text_start_date ) . '" />';
        }
        if ( 'both' == $fields || 'end_date' == $fields ) {
            $output .= '<input type="text" class="facetwp-date facetwp-date-max" data-empty="' . esc_attr( $text_end_date_empty ) . '" value="' . esc_attr( $value[1] ?? '' ) . '" placeholder="' . esc_attr( $text_end_date ) . '" />';
        }

        return $output;
    }


    /**
     * Filter the query based on selected values
     */
    function filter_posts( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $values = $params['selected_values'];
        $where = '';

        $min = empty( $values[0] ) ? false : $values[0];
        $max = empty( $values[1] ) ? false : $values[1];

        $fields = $facet['fields'] ?? 'both';
        $compare_type = empty( $facet['compare_type'] ) ? 'basic' : $facet['compare_type'];
        $is_dual = ! empty( $facet['source_other'] );

        if ( $is_dual && 'basic' != $compare_type ) {
            if ( 'exact' == $fields ) {
                $max = $min;
            }

            /**
             * Enclose type defaults
             * default min or max to 
             * opposite value
             **/
            if ( 'enclose' == $compare_type ) {
                $min = ( false !== $min ) ? $min : $max;
                $max = ( false !== $max ) ? $max : $min;
            }

            $min = ( false !== $min ) ? $min : '0000-00-00';
            $max = ( false !== $max ) ? $max : '3000-12-31';

            /**
             * Enclose compare
             * The post's range must surround the user-defined range
             */
            if ( 'enclose' == $compare_type ) {
                $where .= " AND LEFT(facet_value, 10) <= '$min'";
                $where .= " AND LEFT(facet_display_value, 10) >= '$max'";
            }

            /**
             * Intersect compare
             * @link http://stackoverflow.com/a/325964
             */
            if ( 'intersect' == $compare_type ) {
                $where .= " AND LEFT(facet_value, 10) <= '$max'";
                $where .= " AND LEFT(facet_display_value, 10) >= '$min'";
            }
        }

        /**
         * Basic compare
         * The user-defined range must surround the post's range
         */
        else {
            if ( 'exact' == $fields ) {
                $max = $min;
            }
            if ( false !== $min ) {
                $where .= " AND LEFT(facet_value, 10) >= '$min'";
            }
            if ( false !== $max ) {
                $where .= " AND LEFT(facet_display_value, 10) <= '$max'";
            }
        }

        $sql = "
        SELECT DISTINCT post_id FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '{$facet['name']}' $where";
        return facetwp_sql( $sql, $facet );
    }


    /**
     * Output any front-end scripts
     */
    function front_scripts() {
        FWP()->display->assets['fDate.css'] = FACETWP_URL . '/assets/vendor/fDate/fDate.css';
        FWP()->display->assets['fDate.js'] = FACETWP_URL . '/assets/vendor/fDate/fDate.min.js';
    }


    function register_fields() {
        return [
            'date_fields' => [
                'type' => 'alias',
                'items' => [
                    'fields' => [
                        'type' => 'select',
                        'label' => __( 'Fields to show', 'fwp' ),
                        'choices' => [
                            'both' => __( 'Start + End Dates', 'fwp' ),
                            'exact' => __( 'Exact Date', 'fwp' ),
                            'start_date' => __( 'Start Date', 'fwp' ),
                            'end_date' => __( 'End Date', 'fwp' )
                        ]
                    ]
                ]
            ],
            'date_format' => [
                'type' => 'alias',
                'items' => [
                    'format' => [
                        'label' => __( 'Display format', 'fwp' ),
                        'notes' => 'See available <a href="https://facetwp.com/help-center/facets/facet-types/date-range/#tokens" target="_blank">formatting tokens</a>',
                        'placeholder' => 'Y-m-d'
                    ]
                ]
            ]
        ];
    }


    /**
     * (Front-end) Attach settings to the AJAX response
     */
    function settings_js( $params ) {
        global $wpdb;

        $facet = $params['facet'];
        $selected_values = $params['selected_values'];
        $fields = empty( $facet['fields'] ) ? 'both' : $facet['fields'];
        $format = empty( $facet['format'] ) ? '' : $facet['format'];

        // Use "OR" mode by excluding the facet's own selection
        $where_clause = $this->get_where_clause( $facet );

        $sql = "
        SELECT MIN(facet_value) AS `minDate`, MAX(facet_display_value) AS `maxDate` FROM {$wpdb->prefix}facetwp_index
        WHERE facet_name = '{$facet['name']}' AND facet_display_value != '' $where_clause";
        $row = $wpdb->get_row( $sql );

        $min = substr( $row->minDate ?? '', 0, 10 );
        $max = substr( $row->maxDate ?? '', 0, 10 );

        if ( 'both' == $fields ) {
            $min_upper = ! empty( $selected_values[1] ) ? $selected_values[1] : $max;
            $max_lower = ! empty( $selected_values[0] ) ? $selected_values[0] : $min;

            $range = [
                'min' => [
                    'minDate' => $min,
                    'maxDate' => $min_upper
                ],
                'max' => [
                    'minDate' => $max_lower,
                    'maxDate' => $max
                ]
            ];
        }
        else {
            $range = [
                'minDate' => $min,
                'maxDate' => $max
            ];
        }

        return [
            'locale' => $this->get_i18n_labels(),
            'format' => $format,
            'fields' => $fields,
            'range' => $range
        ];
    }


    function get_i18n_labels() {
        $locale = get_locale();
        $locale = empty( $locale ) ? 'en' : substr( $locale, 0, 2 );

        $locales = [
            'ca' => [
                'weekdays_short' => ['Dg', 'Dl', 'Dt', 'Dc', 'Dj', 'Dv', 'Ds'],
                'months_short' => ['Gen', 'Febr', 'Març', 'Abr', 'Maig', 'Juny', 'Jul', 'Ag', 'Set', 'Oct', 'Nov', 'Des'],
                'months' => ['Gener', 'Febrer', 'Març', 'Abril', 'Maig', 'Juny', 'Juliol', 'Agost', 'Setembre', 'Octubre', 'Novembre', 'Desembre'],
                'firstDayOfWeek' => 1
            ],
            'da' => [
                'weekdays_short' => ['søn', 'man', 'tir', 'ons', 'tors', 'fre', 'lør'],
                'months_short' => ['jan', 'feb', 'mar', 'apr', 'maj', 'jun', 'jul', 'aug', 'sep', 'okt', 'nov', 'dec'],
                'months' => ['januar', 'februar', 'marts', 'april', 'maj', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'december'],
                'firstDayOfWeek' => 1
            ],
            'de' => [
                'weekdays_short' => ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
                'months_short' => ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
                'months' => ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
                'firstDayOfWeek' => 1
            ],
            'es' => [
                'weekdays_short' => ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'],
                'months_short' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
                'months' => ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                'firstDayOfWeek' => 1
            ],
            'fr' => [
                'weekdays_short' => ['dim', 'lun', 'mar', 'mer', 'jeu', 'ven', 'sam'],
                'months_short' => ['janv', 'févr', 'mars', 'avr', 'mai', 'juin', 'juil', 'août', 'sept', 'oct', 'nov', 'déc'],
                'months' => ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'],
                'firstDayOfWeek' => 1
            ],
            'it' => [
                'weekdays_short' => ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'],
                'months_short' => ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'],
                'months' => ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'],
                'firstDayOfWeek' => 1
            ],
            'nb' => [
                'weekdays_short' => ['Søn', 'Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lør'],
                'months_short' => ['Jan', 'Feb', 'Mar', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Des'],
                'months' => ['Januar', 'Februar', 'Mars', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Desember'],
                'firstDayOfWeek' => 1
            ],
            'nl' => [
                'weekdays_short' => ['zo', 'ma', 'di', 'wo', 'do', 'vr', 'za'],
                'months_short' => ['jan', 'feb', 'mrt', 'apr', 'mei', 'jun', 'jul', 'aug', 'sept', 'okt', 'nov', 'dec'],
                'months' => ['januari', 'februari', 'maart', 'april', 'mei', 'juni', 'juli', 'augustus', 'september', 'oktober', 'november', 'december'],
                'firstDayOfWeek' => 1
            ],
            'pl' => [
                'weekdays_short' => ['Nd', 'Pn', 'Wt', 'Śr', 'Cz', 'Pt', 'So'],
                'months_short' => ['Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'],
                'months' => ['Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'],
                'firstDayOfWeek' => 1
            ],
            'pt' => [
                'weekdays_short' => ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
                'months_short' => ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'],
                'months' => ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
                'firstDayOfWeek' => 0
            ],
            'ro' => [
                'weekdays_short' => ['Dum', 'Lun', 'Mar', 'Mie', 'Joi', 'Vin', 'Sâm'],
                'months_short' => ['Ian', 'Feb', 'Mar', 'Apr', 'Mai', 'Iun', 'Iul', 'Aug', 'Sep', 'Oct', 'Noi', 'Dec'],
                'months' => ['Ianuarie', 'Februarie', 'Martie', 'Aprilie', 'Mai', 'Iunie', 'Iulie', 'August', 'Septembrie', 'Octombrie', 'Noiembrie', 'Decembrie'],
                'firstDayOfWeek' => 1
            ],
            'ru' => [
                'weekdays_short' => ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
                'months_short' => ['Янв', 'Фев', 'Март', 'Апр', 'Май', 'Июнь', 'Июль', 'Авг', 'Сен', 'Окт', 'Ноя', 'Дек'],
                'months' => ['Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь', 'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'],
                'firstDayOfWeek' => 1
            ],
            'sv' => [
                'weekdays_short' => ['Sön', 'Mån', 'Tis', 'Ons', 'Tor', 'Fre', 'Lör'],
                'months_short' => ['Jan', 'Feb', 'Mar', 'Apr', 'Maj', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec'],
                'months' => ['Januari', 'Februari', 'Mars', 'April', 'Maj', 'Juni', 'Juli', 'Augusti', 'September', 'Oktober', 'November', 'December'],
                'firstDayOfWeek' => 1
            ]
        ];

        if ( isset( $locales[ $locale ] ) ) {
            $locales[ $locale ]['clearText'] = __( 'Clear', 'fwp-front' );
            return $locales[ $locale ];
        }

        return '';
    }
}
