<?php

class FacetWP_Settings
{

    /**
     * Get the field settings array
     * @since 3.0.0
     */
    function get_registered_settings() {
        $defaults = [
            'general' => [
                'label' => __( 'General', 'fwp' ),
                'fields' => [
                    'license_key' => [
                        'label' => __( 'License key', 'fwp' ),
                        'html' => $this->get_setting_html( 'license_key' )
                    ],
                    'gmaps_api_key' => [
                        'label' => __( 'Google Maps API key', 'fwp' ),
                        'html' => $this->get_setting_html( 'gmaps_api_key' )
                    ],
                    'gmaps_api_key_test' => [
                        'label' => __( 'Test Google APIs', 'fwp' ),
                        'notes' => 'Test if the required Google API\'s are connected:<br>1. Maps JavaScript API: required for the Map / Proximity facets to work.<br>2. Geocoding API: required for the Proximity facet\'s "Locate me" button.<br>3. Places API (Legacy): required for the Legacy Proximity facet\'s autocomplete function (with the "Use Legacy Proximity facet" setting enabled).<br>4. Places API (New): required for the new Proximity facet\'s autocomplete function (with the "Use Legacy Proximity facet" setting disabled).<br>Note: changes in the Google Cloud Console settings may take up to five minutes to take effect. Clear your browser cache before testing changed settings.',
                        'html' => $this->get_setting_html( 'gmaps_api_key_test' )
                    ],
                    'places_version' => [
                        'label' => __( 'Use Legacy Proximity facet', 'fwp' ),
                        'notes' => 'Enable to use the Legacy Proximity facet type. This legacy facet type uses Google\'s legacy "Places" API, instead of the "Places (New)" API. Important: make sure the correct Places API is enabled in your Google Cloud Console for your project: enable "Places" API for the Legacy Proximity facet and "Places (New)" for the normal Proximity facet. ',
                        'html' => $this->get_setting_html( 'places_version', 'toggle', [
                            'true_value' => 'places-service',
                            'false_value' => 'place-class'
                        ]),
                        'show' => '"" != app.settings.gmaps_api_key'
                    ],
                    'separators' => [
                        'label' => __( 'Separators', 'fwp' ),
                        'notes' => 'Enter the thousands and decimal separators, respectively',
                        'html' => $this->get_setting_html( 'separators' )
                    ],
                    'prefix' => [
                        'label' => __( 'URL prefix', 'fwp' ),
                        'html' => $this->get_setting_html( 'prefix', 'dropdown', [
                            'choices' => [ 'fwp_' => 'fwp_', '_' => '_' ]
                        ])
                    ],
                    'load_jquery' => [
                        'label' => __( 'Load jQuery', 'fwp' ),
                        'notes' => 'FacetWP no longer requires jQuery, but enable if needed',
                        'html' => $this->get_setting_html( 'load_jquery', 'toggle', [
                            'true_value' => 'yes',
                            'false_value' => 'no'
                        ])
                    ],
                    'load_a11y' => [
                        'label' => __( 'Load a11y support', 'fwp' ),
                        'notes' => 'Improved accessibility for users with disabilities',
                        'html' => $this->get_setting_html( 'load_a11y', 'toggle', [
                            'true_value' => 'yes',
                            'false_value' => 'no'
                        ])
                    ],
                    'strict_query_detection' => [
                        'label' => __( 'Strict query detection', 'fwp' ),
                        'notes' => 'Enable if FacetWP auto-detects the wrong archive query',
                        'html' => $this->get_setting_html( 'strict_query_detection', 'toggle', [
                            'true_value' => 'yes',
                            'false_value' => 'no'
                        ])
                    ],
                    'debug_mode' => [
                        'label' => __( 'Debug mode', 'fwp' ),
                        'notes' => 'After enabling, type "FWP.settings.debug" into the browser console on your front-end facet page',
                        'html' => $this->get_setting_html( 'debug_mode', 'toggle', [
                            'true_value' => 'on',
                            'false_value' => 'off',
                            'message' => 'Debug Mode exposes some of your settings and can influence loading speeds.<br />Make sure to disable it when not needed for troubleshooting.'
                        ]),
                    ],
                    'enable_indexer' => [
                        'label' => __( 'Enable automatic indexing', 'fwp' ),
                        'notes' => 'Disable to prevent editing posts and terms from updating the indexing table.',
                        'html' => $this->get_setting_html( 'enable_indexer', 'toggle', [
                            'true_value' => 'yes',
                            'false_value' => 'no'
                        ])
                    ]
                ]
            ],
            'woocommerce' => [
                'label' => __( 'WooCommerce', 'fwp' ),
                'fields' => [
                    'wc_enable_variations' => [
                        'label' => __( 'Support product variations?', 'fwp' ),
                        'notes' => __( 'Enable if your store uses variable products.', 'fwp' ),
                        'html' => $this->get_setting_html( 'wc_enable_variations', 'toggle' )
                    ],
                    'wc_index_all' => [
                        'label' => __( 'Index out-of-stock products?', 'fwp' ),
                        'notes' => __( 'Show facet choices for out-of-stock products?', 'fwp' ),
                        'html' => $this->get_setting_html( 'wc_index_all', 'toggle' )
                    ]
                ]
            ],
            'backup' => [
                'label' => __( 'Import / Export', 'fwp' ),
                'fields' => [
                    'export' => [
                        'label' => __( 'Export', 'fwp' ),
                        'html' => $this->get_setting_html( 'export' )
                    ],
                    'import' => [
                        'label' => __( 'Import', 'fwp' ),
                        'html' => $this->get_setting_html( 'import' )
                    ]
                ]
            ]
        ];

        if ( $this-> get_gmaps_key_other() ) {
            unset( $defaults['general']['fields']['places_version']['show'] );
        }

        if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            unset( $defaults['woocommerce'] );
        }

        if ( '_' == FWP()->helper->settings['settings']['prefix'] ) {
            unset( $defaults['general']['fields']['prefix'] );
        }

        return apply_filters( 'facetwp_settings_admin', $defaults, $this );
    }



    /**
     * Get gmaps api key if set from filter or constant
     * @since 4.4
     */
    function get_gmaps_key_other() {

        $params = apply_filters( 'facetwp_gmaps_params', [] );
        if ( !empty( $params['key'] )  ) {
            return $params['key'];
        }

        return apply_filters( 'facetwp_gmaps_api_key', defined( 'GMAPS_API_KEY' ) ? GMAPS_API_KEY : '' );
    }


    /**
     * All facet admin fields
     * @since 3.9
     */
    function get_registered_facet_fields() {
        $settings = [
            'label_any' => [
                'label' => __( 'Default label', 'fwp' ),
                'notes' => 'Customize the "Any" label',
                'default' => __( 'Any', 'fwp' ),
                'show' => "facet.ui_type != 'checkboxes'"
            ],
            'placeholder' => [
                'label' => __( 'Placeholder text', 'fwp' )
            ],
            'parent_term' => [
                'label' => __( 'Parent term', 'fwp' ),
                'notes' => 'To show only child terms, enter the parent <a href="https://facetwp.com/how-to-find-a-wordpress-terms-id/" target="_blank">term ID</a>. Otherwise, leave blank.',
                'show' => "facet.source.substr(0, 3) == 'tax'"
            ],
            'hierarchical' => [
                'type' => 'toggle',
                'label' => __( 'Hierarchical', 'fwp' ),
                'notes' => 'Is this a hierarchical taxonomy?',
                'show' => "facet.source.substr(0, 3) == 'tax' && facet.ui_type != 'radio'"
            ],
            'show_expanded' => [
                'type' => 'toggle',
                'label' => __( 'Show expanded', 'fwp' ),
                'notes' => 'Should child terms be visible by default?',
                'show' => "facet.hierarchical == 'yes' && !['radio','fselect','dropdown'].includes(facet.ui_type)"
            ],
            'multiple' => [
                'type' => 'toggle',
                'label' => __( 'Multi-select', 'fwp' ),
                'notes' => 'Allow multiple selections?',
                'show' => "!['radio','checkboxes', 'dropdown'].includes(facet.ui_type)"
            ],
            'ghosts' => [
                'type' => 'alias',
                'items' => [
                    'ghosts' => [
                        'type' => 'toggle',
                        'label' => __( 'Show ghosts', 'fwp' ),
                        'notes' => 'Show choices that would return zero results?'
                    ],
                    'preserve_ghosts' => [
                        'type' => 'toggle',
                        'label' => __( 'Preserve ghost order', 'fwp' ),
                        'notes' => 'Keep ghost choices in the same order?',
                        'show' => "facet.ghosts == 'yes' && facet.orderby != 'count'"
                    ]
                ]
            ],
            'modifiers' => [
                'type' => 'alias',
                'items' => [
                    'modifier_type' => [
                        'type' => 'select',
                        'label' => __( 'Value modifiers', 'fwp' ),
                        'notes' => 'Include or exclude certain values?',
                        'choices' => [
                            'off' => __( 'Off', 'fwp' ),
                            'exclude' => __( 'Exclude these values', 'fwp' ),
                            'include' => __( 'Show only these values', 'fwp' )
                        ]
                    ],
                    'modifier_values' => [
                        'type' => 'textarea',
                        'label' => '',
                        'placeholder' => 'Add one value per line',
                        'show' => "facet.modifier_type != 'off'"
                    ]
                ]
            ],
            'operator' => [
                'type' => 'select',
                'label' => __( 'Facet logic', 'fwp' ),
                'notes' => 'How should multiple selections affect the results?',
                'choices' => [
                    'and' => __( 'AND (match all)', 'fwp' ),
                    'or' => __( 'OR (match any)', 'fwp' ),
                ],
                'show' => "facet.ui_type == 'checkboxes' || facet.multiple == 'yes' || facet.type == 'checkboxes' || facet.type == 'color'"
            ],
            'orderby' => [
                'type' => 'select',
                'label' => __( 'Sort by', 'fwp' ),
                'default' => 'count',
                'choices' => [
                    'count' => __( 'Highest count', 'fwp' ),
                    'display_value' => __( 'Display value', 'fwp' ),
                    'raw_value' => __( 'Raw value', 'fwp' ),
                    'term_order' => __( 'Term order', 'fwp' )
                ]
            ],
            'count' => [
                'label' => __( 'Count', 'fwp' ),
                'notes' => 'The maximum number of choices to show (-1 for no limit)',
                'default' => 10
            ],
            'soft_limit' => [
                'label' => __( 'Soft limit', 'fwp' ),
                'notes' => 'Show a toggle link after this many choices',
                'default' => 5,
                'show' => "facet.hierarchical != 'yes' && !['radio','fselect', 'dropdown'].includes(facet.ui_type)"
            ],
            'source_other' => [
                'label' => __( 'Other data source', 'fwp' ),
                'notes' => 'Use a separate value for the upper limit?',
                'html' => '<data-sources :facet="facet" setting-name="source_other"></data-sources>'
            ],
            'compare_type' => [
                'type' => 'select',
                'label' => __( 'Compare type', 'fwp' ),
                'notes' => "<strong>Basic</strong> - entered range surrounds the post's range<br /><strong>Enclose</strong> - entered range is fully inside the post's range<br /><strong>Intersect</strong> - entered range overlaps the post's range<br /><br />When in doubt, choose <strong>Basic</strong>",
                'choices' => [
                    '' => __( 'Basic', 'fwp' ),
                    'enclose' => __( 'Enclose', 'fwp' ),
                    'intersect' => __( 'Intersect', 'fwp' )
                ],
                'show' => "'undefined' != typeof facet.source_other && facet.source_other != ''"
            ],
            'ui_type' => [
                'label' => __( 'UI type', 'fwp' ),
                'type' => 'select',
                'choices' => [
                    'checkboxes' => __( 'Checkboxes', 'fwp' ),
                    'radio' => __( 'Radio', 'fwp' ),
                    'dropdown' => __( 'Dropdown', 'fwp' ),
                    'fselect' => __( 'fSelect', 'fwp' )
                ]
            ],
            'ui_ghosts' => [
                'type' => 'toggle',
                'label' => __( 'Show ghosts', 'fwp' ),
                'notes' => 'Show choices that would return zero results?'
            ],
            'reset_text' => [
                'label' => __( 'Reset text', 'fwp' ),
                'default' => 'Reset'
            ]
        ];

        foreach ( FWP()->helper->facet_types as $name => $obj ) {
            if ( method_exists( $obj, 'register_fields' ) ) {
                $settings = array_merge( $settings, $obj->register_fields() );
            }
        }

        return $settings;
    }


    /**
     * Return HTML for a single facet field (supports aliases)
     * @since 3.9
     */
    function get_facet_field_html( $name ) {
        ob_start();

        $fields = FWP()->settings->get_registered_facet_fields();

        if ( isset( $fields[ $name ] ) ) {
            $field = $fields[ $name ];

            if ( isset( $field['type'] ) && 'alias' == $field['type'] ) {
                foreach ( $field['items'] as $k => $v ) {
                    $v['name'] = $k;
                    $this->render_facet_field( $v );
                }
            }
            else {
                $field['name'] = $name;
                $this->render_facet_field( $field );
            }
        }

        return ob_get_clean();
    }


    /**
     * Render a facet field
     * @since 3.9
     */
    function render_facet_field( $field ) {
        $name = str_replace( '_', '-', $field['name'] );
        $type = $field['type'] ?? 'text';
        $placeholder = $field['placeholder'] ?? '';
        $show = isset( $field['show'] ) ? ' v-show="' . $field['show'] . '"' : '';
        $default = isset( $field['default'] ) ? ' value="' . $field['default'] . '"' : '';
        $label = empty( $field['label'] ) ? '' : $field['label'];
        $checked = ( isset( $field['default'] ) && 'checked' == $field['default'] ) ? ' checked="checked"' : '';

        if ( isset( $field['notes'] ) ) {
            $label = '<div class="facetwp-tooltip">' . $label . '<div class="facetwp-tooltip-content">' . $field['notes'] . '</div></div>';
        }

        ob_start();

        if ( isset( $field['html'] ) ) {
            echo $field['html'];
        }
        elseif ( 'text' == $type ) {
?>
                <input type="text" class="facet-<?php echo $name; ?>" placeholder="<?php echo $placeholder; ?>"<?php echo $default; ?> />
<?php
        }
        elseif ( 'textarea' == $type ) {
?>
                <textarea class="facet-<?php echo $name; ?>" placeholder="<?php echo $placeholder; ?>"></textarea>
<?php
        }
        elseif ( 'toggle' == $type ) {
            ?>
                <label class="facetwp-switch">
                    <input type="checkbox" class="facet-<?php echo $name; ?>" true-value="yes" false-value="no"<?php echo $checked; ?> />
                    <span class="facetwp-slider"></span>
                </label>
<?php
        }
        elseif ( 'select' == $type ) {
?>
                <select class="facet-<?php echo $name; ?>">
                    <?php foreach ( $field['choices'] as $k => $v ) : ?>
                    <option value="<?php echo $k; ?>"><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
<?php
        }

        $html = ob_get_clean();
?>
        <div class="facetwp-row"<?php echo $show; ?>>
            <div><?php echo $label; ?></div>
            <div><?php echo $html; ?></div>
        </div>
<?php
    }


    /**
     * Return HTML for a setting field
     * @since 3.0.0
     */
    function get_setting_html( $setting_name, $field_type = 'text', $atts = [] ) {
        ob_start();

        if ( 'license_key' == $setting_name ) : ?>

        <input type="text" class="facetwp-license" style="width:360px" value="<?php echo FWP()->helper->get_license_key(); ?>"<?php echo defined( 'FACETWP_LICENSE_KEY' ) ? ' disabled' : ''; ?> />
        <div @click="activate" class="btn-normal"><?php _e( 'Activate', 'fwp' ); ?></div>
        <div class="facetwp-activation-status field-notes"><?php echo $this->get_activation_status(); ?></div>

<?php elseif ( 'gmaps_api_key' == $setting_name ) : ?>

        <input type="text" v-model="app.settings.gmaps_api_key" style="width:360px"<?php echo $this->get_gmaps_key_other() ? ' disabled placeholder="'.$this->get_gmaps_key_other().'"' : ''; ?> />
        <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank"><?php _e( 'Get an API key', 'fwp' ); ?></a>
        <?php echo $this->get_gmaps_key_other() ? '<div class="field-notes">The API key is set with a hook or in wp-config.php</div>' : ''; ?>

<?php elseif ( 'gmaps_api_key_test' == $setting_name ) : ?>

        <?php if ( !empty( $this->get_gmaps_key_other() ) ) : ?>

            <div @click="checkApi('maps','<?php echo $this->get_gmaps_key_other(); ?>')" class="apitestcheck btn-normal" ><?php _e( 'Maps JavaScript API', 'fwp' ); ?></div>
            <div @click="checkApi('geocoder','<?php echo $this->get_gmaps_key_other(); ?>')" class="apitestcheck btn-normal" ><?php _e( 'Geocoding API', 'fwp' ); ?></div>
            <div @click="checkApi('placeslegacy','<?php echo $this->get_gmaps_key_other(); ?>')" class="apitestcheck btn-normal" v-show="app.settings.places_version == 'places-service'"><?php _e( 'Places API (Legacy)', 'fwp' ); ?></div>
            <div @click="checkApi('places','<?php echo $this->get_gmaps_key_other(); ?>')" class="apitestcheck btn-normal" ><?php _e( 'Places API (New)', 'fwp' ); ?></div>
            <div @click="resetCheckApi" class="apitestreset btn-normal" v-show="app.settings.gmaps_api_key && this.$root.gmapsChanged"><?php _e( 'Retest', 'fwp' ); ?></div>

        <?php else : ?>

            <div v-show="!app.settings.gmaps_api_key"><?php _e( 'Enter a Google Maps API key above to test.', 'fwp' ); ?></div>

            <div @click="checkApi('maps',app.settings.gmaps_api_key)" class="apitestcheck btn-normal" v-show="app.settings.gmaps_api_key && !this.$root.gmapsChanged"><?php _e( 'Maps JavaScript API', 'fwp' ); ?></div>
            <div @click="checkApi('geocoder',app.settings.gmaps_api_key)" class="apitestcheck btn-normal" v-show="app.settings.gmaps_api_key && !this.$root.gmapsChanged"><?php _e( 'Geocoding API', 'fwp' ); ?></div>
            <div @click="checkApi('placeslegacy',app.settings.gmaps_api_key)" class="apitestcheck btn-normal" v-show="app.settings.gmaps_api_key && !this.$root.gmapsChanged && app.settings.places_version == 'places-service'"><?php _e( 'Places API (Legacy)', 'fwp' ); ?></div>
            <div @click="checkApi('places',app.settings.gmaps_api_key)" class="apitestcheck btn-normal" v-show="app.settings.gmaps_api_key && !this.$root.gmapsChanged"><?php _e( 'Places API (New)', 'fwp' ); ?></div>
            <div @click="resetCheckApi" class="apitestreset btn-normal" v-show="app.settings.gmaps_api_key && this.$root.gmapsChanged"><?php _e( 'Retest', 'fwp' ); ?></div>

            <?php endif; ?>

        <div class="facetwp-api-status">
            <span class="test-status"></span>
            <span class="test-maps"></span>
            <span class="test-geocoder"></span>
            <span class="test-placeslegacy"></span>
            <span class="test-places"></span>
        </div>

<?php elseif ( 'separators' == $setting_name ) : ?>

        Thousands:
        <input type="text" v-model="app.settings.thousands_separator" style="width:30px" /> &nbsp;
        Decimal:
        <input type="text" v-model="app.settings.decimal_separator" style="width:30px" />

<?php elseif ( 'export' == $setting_name ) : ?>

        <select class="export-items" multiple="multiple">
            <?php foreach ( $this->get_export_choices() as $val => $label ) : ?>
                <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>
        <div class="select-all-none">Select: <span class="export-all"><?php _e( 'All', 'fwp' ); ?></span> | <span class="export-all" data-value="facet"><?php _e( 'All facet', 'fwp' ); ?></span> | <span class="export-all" data-value="template"><?php _e( 'All listings', 'fwp' ); ?></span> | <span class="export-none"><?php _e( 'Reset', 'fwp' ); ?></span></div>
        <div class="btn-normal export-submit">
            <?php _e( 'Export', 'fwp' ); ?>
        </div>

<?php elseif ( 'import' == $setting_name ) : ?>

        <div><textarea class="import-code" placeholder="<?php _e( 'Paste the import code here', 'fwp' ); ?>"></textarea></div>
        <div><input type="checkbox" class="import-overwrite" /> <?php _e( 'Overwrite existing items?', 'fwp' ); ?></div>
        <div style="margin-top:5px">
            <div class="btn-normal import-submit"><?php _e( 'Import', 'fwp' ); ?></div>
        </div>

<?php elseif ( 'dropdown' == $field_type ) : ?>

        <select class="facetwp-setting" v-model="app.settings.<?php echo $setting_name; ?>">
            <?php foreach ( $atts['choices'] as $val => $label ) : ?>
            <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
            <?php endforeach; ?>
        </select>

<?php elseif ( 'toggle' == $field_type ) : ?>
<?php

$true_value = $atts['true_value'] ?? 'yes';
$false_value = $atts['false_value'] ?? 'no';
$message = $atts['message'] ?? '';

?>
        <label class="facetwp-switch">
            <input
                type="checkbox"
                v-model="app.settings.<?php echo $setting_name; ?>"
                true-value="<?php echo $true_value; ?>"
                false-value="<?php echo $false_value; ?>"
            />
            <span class="facetwp-slider"></span>
        </label>

        <?php if ( '' != $message ) { ?>
            <div v-if="app.settings.<?php echo $setting_name; ?> == '<?php echo $true_value; ?>'" class="facetwp-toggle-status field-notes"><?php echo $message; ?></div>
        <?php } ?>

<?php endif;

        return ob_get_clean();
    }


    /**
     * Get an array of all facets and templates
     * @since 3.0.0
     */
    function get_export_choices() {
        $export = [];

        $settings = FWP()->helper->settings;

        foreach ( $settings['facets'] as $facet ) {
            $export['facet-' . $facet['name']] = 'Facet - ' . $facet['label'];
        }

        foreach ( $settings['templates'] as $template ) {
            $export['template-' . $template['name']] = 'Listing - '. $template['label'];
        }

        return $export;
    }


    /**
     * Get the activation status
     * @since 3.0.0
     */
    function get_activation_status() {
        $message = __( 'Not yet activated', 'fwp' );
        $status = FWP()->helper->get_license_meta( 'status' );

        if ( false !== $status ) {
            if ( 'success' == $status ) {
                $expiration = FWP()->helper->get_license_meta( 'expiration' );
                $expiration = date( 'M j, Y', strtotime( $expiration ) );
                $message = __( 'Valid until', 'fwp' ) . ' ' . $expiration;
            }
            else {
                $message = FWP()->helper->get_license_meta( 'message' );
            }
        }

        return $message;
    }

    /**
     * Load i18n admin strings
     * @since 3.2.0
     */
    function get_i18n_strings() {
        return [
            'Number of grid columns' => __( 'Number of grid columns', 'fwp' ),
            'Spacing between results' => __( 'Spacing between results', 'fwp' ),
            'No results text' => __( 'No results text', 'fwp' ),
            'Text style' => __( 'Text style', 'fwp' ),
            'Text color' => __( 'Text color', 'fwp' ),
            'Font size' => __( 'Font size', 'fwp' ),
            'Background color' => __( 'Background color', 'fwp' ),
            'Border' => __( 'Border', 'fwp' ),
            'Border style' => __( 'Border style', 'fwp' ),
            'None' => __( 'None', 'fwp' ),
            'Solid' => __( 'Solid', 'fwp' ),
            'Dashed' => __( 'Dashed', 'fwp' ),
            'Dotted' => __( 'Dotted', 'fwp' ),
            'Double' => __( 'Double', 'fwp' ),
            'Border color' => __( 'Border color', 'fwp' ),
            'Border width' => __( 'Border width', 'fwp' ),
            'Button text' => __( 'Button text', 'fwp' ),
            'Button text color' => __( 'Button text color', 'fwp' ),
            'Button padding' => __( 'Button padding', 'fwp' ),
            'Separator' => __( 'Separator', 'fwp' ),
            'Custom CSS' => __( 'Custom CSS', 'fwp' ),
            'Column widths' => __( 'Column widths', 'fwp' ),
            'Content' => __( 'Content', 'fwp' ),
            'Image size' => __( 'Image size', 'fwp' ),
            'Author field' => __( 'Author field', 'fwp' ),
            'Display name' => __( 'Display name', 'fwp' ),
            'User login' => __( 'User login', 'fwp' ),
            'User ID' => __( 'User ID', 'fwp' ),
            'Field type' => __( 'Field type', 'fwp' ),
            'Text' => __( 'Text', 'fwp' ),
            'Date' => __( 'Date', 'fwp' ),
            'Number' => __( 'Number', 'fwp' ),
            'Date format' => __( 'Date format', 'fwp' ),
            'Input format' => __( 'Input format', 'fwp' ),
            'Number format' => __( 'Number format', 'fwp' ),
            'Link' => __( 'Link', 'fwp' ),
            'Link type' => __( 'Link type', 'fwp' ),
            'Post URL' => __( 'Post URL', 'fwp' ),
            'Custom URL' => __( 'Custom URL', 'fwp' ),
            'Open in new tab?' => __( 'Open in new tab?', 'fwp' ),
            'Prefix' => __( 'Prefix', 'fwp' ),
            'Suffix' => __( 'Suffix', 'fwp' ),
            'Hide item?' => __( 'Hide item?', 'fwp' ),
            'Padding' => __( 'Padding', 'fwp' ),
            'CSS class' => __( 'CSS class', 'fwp' ),
            'Button Border' => __( 'Button border', 'fwp' ),
            'Term URL' => __( 'Term URL', 'fwp' ),
            'Fetch' => __( 'Fetch', 'fwp' ),
            'All post types' => __( 'All post types', 'fwp' ),
            'and show' => __( 'and show', 'fwp' ),
            'per page' => __( 'per page', 'fwp' ),
            'Sort by' => __( 'Sort by', 'fwp' ),
            'Posts' => __( 'Posts', 'fwp' ),
            'Post Title' => __( 'Post Title', 'fwp' ),
            'Post Name' => __( 'Post Name', 'fwp' ),
            'Post Type' => __( 'Post Type', 'fwp' ),
            'Post Date' => __( 'Post Date', 'fwp' ),
            'Post Modified' => __( 'Post Modified', 'fwp' ),
            'Comment Count' => __( 'Comment Count', 'fwp' ),
            'Menu Order' => __( 'Menu Order', 'fwp' ),
            'Custom Fields' => __( 'Custom Fields', 'fwp' ),
            'Narrow results by' => __( 'Narrow results by', 'fwp' ),
            'Hit Enter' => __( 'Hit Enter', 'fwp' ),
            'Add query sort' => __( 'Add query sort', 'fwp' ),
            'Add query filter' => __( 'Add query filter', 'fwp' ),
            'Clear' => __( 'Clear', 'fwp' ),
            'Enter term slugs' => __( 'Enter term slugs', 'fwp' ),
            'Enter values' => __( 'Enter values', 'fwp' ),
            'Layout' => __( 'Layout', 'fwp' ),
            'Content' => __( 'Content', 'fwp' ),
            'Style' => __( 'Style', 'fwp' ),
            'Row' => __( 'Row', 'fwp' ),
            'Column' => __( 'Column', 'fwp' ),
            'Start typing' => __( 'Start typing', 'fwp' ),
            'Label' => __( 'Label', 'fwp' ),
            'Unique name' => __( 'Unique name', 'fwp' ),
            'Facet type' => __( 'Facet type', 'fwp' ),
            'Copy shortcode' => __( 'Copy shortcode', 'fwp' ),
            'Data source' => __( 'Data source', 'fwp' ),
            'Switch to advanced mode' => __( 'Switch to advanced mode', 'fwp' ),
            'Switch to visual mode' => __( 'Switch to visual mode', 'fwp' ),
            'Display' => __( 'Display', 'fwp' ),
            'Query' => __( 'Query', 'fwp' ),
            'Help' => __( 'Help', 'fwp' ),
            'Display Code' => __( 'Display Code', 'fwp' ),
            'Query Arguments' => __( 'Query Arguments', 'fwp' ),
            'Saving' => __( 'Saving', 'fwp' ),
            'Indexing' => __( 'Indexing', 'fwp' ),
            'The index table is empty' => __( 'The index table is empty', 'fwp' ),
            'Indexing complete' => __( 'Indexing complete', 'fwp' ),
            'Looking' => __( 'Looking', 'fwp' ),
            'Purging' => __( 'Purging', 'fwp' ),
            'Copied!' => __( 'Copied!', 'fwp' ),
            'Press CTRL+C to copy' => __( 'Press CTRL+C to copy', 'fwp' ),
            'Activating' => __( 'Activating', 'fwp' ),
            'Re-index' => __( 'Re-index', 'fwp' ),
            'Stop indexer' => __( 'Stop indexer', 'fwp' ),
            'Loading' => __( 'Loading', 'fwp' ),
            'Importing' => __( 'Importing', 'fwp' ),
            'Convert to query args' => __( 'Convert to query args', 'fwp' )
        ];
    }


    /**
     * Get available image sizes
     * @since 3.2.7
     */
    function get_image_sizes() {
        global $_wp_additional_image_sizes;

        $sizes = [];

        $default_sizes = [ 'thumbnail', 'medium', 'medium_large', 'large', 'full' ];

        foreach ( get_intermediate_image_sizes() as $size ) {
            if ( in_array( $size, $default_sizes ) ) {
                $sizes[ $size ]['width'] = (int) get_option( "{$size}_size_w" );
                $sizes[ $size ]['height'] = (int) get_option( "{$size}_size_h" );
            }
            elseif ( isset( $_wp_additional_image_sizes[ $size ] ) ) {
                $sizes[ $size ] = $_wp_additional_image_sizes[ $size ];
            }
        }

        return $sizes;
    }


    /**
     * Return an array of formatted image sizes
     * @since 3.2.7
     */
    function get_image_size_labels() {
        $labels = [];
        $sizes = $this->get_image_sizes();

        foreach ( $sizes as $size => $data ) {
            $height = ( 0 === $data['height'] ) ? 'w' : 'x' . $data['height'];
            $label = $size . ' (' . $data['width'] . $height . ')';
            $labels[ $size ] = $label;
        }

        $labels['full'] = __( 'full', 'fwp' );

        return $labels;
    }


    /**
     * Create SVG images (based on Font Awesome)
     * @license https://fontawesome.com/license/free CC BY 4.0
     * @since 3.6.5
     */
    function get_svg() {
        $output = [];

        $icons = [
            // [viewBox width, viewBox height, icon width, svg data]
            "align-center" => [448, 512, 14, "M432 160H16a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h416a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16zm0 256H16a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h416a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16zM108.1 96h231.81A12.09 12.09 0 0 0 352 83.9V44.09A12.09 12.09 0 0 0 339.91 32H108.1A12.09 12.09 0 0 0 96 44.09V83.9A12.1 12.1 0 0 0 108.1 96zm231.81 256A12.09 12.09 0 0 0 352 339.9v-39.81A12.09 12.09 0 0 0 339.91 288H108.1A12.09 12.09 0 0 0 96 300.09v39.81a12.1 12.1 0 0 0 12.1 12.1z"],
            "align-left" => [448, 512, 14, "M12.83 352h262.34A12.82 12.82 0 0 0 288 339.17v-38.34A12.82 12.82 0 0 0 275.17 288H12.83A12.82 12.82 0 0 0 0 300.83v38.34A12.82 12.82 0 0 0 12.83 352zm0-256h262.34A12.82 12.82 0 0 0 288 83.17V44.83A12.82 12.82 0 0 0 275.17 32H12.83A12.82 12.82 0 0 0 0 44.83v38.34A12.82 12.82 0 0 0 12.83 96zM432 160H16a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h416a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16zm0 256H16a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h416a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16z"],
            "align-right" => [448, 512, 14, "M16 224h416a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16H16a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16zm416 192H16a16 16 0 0 0-16 16v32a16 16 0 0 0 16 16h416a16 16 0 0 0 16-16v-32a16 16 0 0 0-16-16zm3.17-384H172.83A12.82 12.82 0 0 0 160 44.83v38.34A12.82 12.82 0 0 0 172.83 96h262.34A12.82 12.82 0 0 0 448 83.17V44.83A12.82 12.82 0 0 0 435.17 32zm0 256H172.83A12.82 12.82 0 0 0 160 300.83v38.34A12.82 12.82 0 0 0 172.83 352h262.34A12.82 12.82 0 0 0 448 339.17v-38.34A12.82 12.82 0 0 0 435.17 288z"],
            "arrow-circle-up" => [512, 512, 16, "M8 256C8 119 119 8 256 8s248 111 248 248-111 248-248 248S8 393 8 256zm143.6 28.9l72.4-75.5V392c0 13.3 10.7 24 24 24h16c13.3 0 24-10.7 24-24V209.4l72.4 75.5c9.3 9.7 24.8 9.9 34.3.4l10.9-11c9.4-9.4 9.4-24.6 0-33.9L273 107.7c-9.4-9.4-24.6-9.4-33.9 0L106.3 240.4c-9.4 9.4-9.4 24.6 0 33.9l10.9 11c9.6 9.5 25.1 9.3 34.4-.4z"],
            "bold" => [384, 512, 12, "M333.49 238a122 122 0 0 0 27-65.21C367.87 96.49 308 32 233.42 32H34a16 16 0 0 0-16 16v48a16 16 0 0 0 16 16h31.87v288H34a16 16 0 0 0-16 16v48a16 16 0 0 0 16 16h209.32c70.8 0 134.14-51.75 141-122.4 4.74-48.45-16.39-92.06-50.83-119.6zM145.66 112h87.76a48 48 0 0 1 0 96h-87.76zm87.76 288h-87.76V288h87.76a56 56 0 0 1 0 112z"],
            "caret-down" => [320, 512, 10, "M31.3 192h257.3c17.8 0 26.7 21.5 14.1 34.1L174.1 354.8c-7.8 7.8-20.5 7.8-28.3 0L17.2 226.1C4.6 213.5 13.5 192 31.3 192z"],
            "cog" => [512, 512, 16, "M487.4 315.7l-42.6-24.6c4.3-23.2 4.3-47 0-70.2l42.6-24.6c4.9-2.8 7.1-8.6 5.5-14-11.1-35.6-30-67.8-54.7-94.6-3.8-4.1-10-5.1-14.8-2.3L380.8 110c-17.9-15.4-38.5-27.3-60.8-35.1V25.8c0-5.6-3.9-10.5-9.4-11.7-36.7-8.2-74.3-7.8-109.2 0-5.5 1.2-9.4 6.1-9.4 11.7V75c-22.2 7.9-42.8 19.8-60.8 35.1L88.7 85.5c-4.9-2.8-11-1.9-14.8 2.3-24.7 26.7-43.6 58.9-54.7 94.6-1.7 5.4.6 11.2 5.5 14L67.3 221c-4.3 23.2-4.3 47 0 70.2l-42.6 24.6c-4.9 2.8-7.1 8.6-5.5 14 11.1 35.6 30 67.8 54.7 94.6 3.8 4.1 10 5.1 14.8 2.3l42.6-24.6c17.9 15.4 38.5 27.3 60.8 35.1v49.2c0 5.6 3.9 10.5 9.4 11.7 36.7 8.2 74.3 7.8 109.2 0 5.5-1.2 9.4-6.1 9.4-11.7v-49.2c22.2-7.9 42.8-19.8 60.8-35.1l42.6 24.6c4.9 2.8 11 1.9 14.8-2.3 24.7-26.7 43.6-58.9 54.7-94.6 1.5-5.5-.7-11.3-5.6-14.1zM256 336c-44.1 0-80-35.9-80-80s35.9-80 80-80 80 35.9 80 80-35.9 80-80 80z"],
            "columns" => [512, 512, 16, "M464 32H48C21.49 32 0 53.49 0 80v352c0 26.51 21.49 48 48 48h416c26.51 0 48-21.49 48-48V80c0-26.51-21.49-48-48-48zM224 416H64V160h160v256zm224 0H288V160h160v256z"],
            "eye-slash" => [640, 512, 20, "M320 400c-75.85 0-137.25-58.71-142.9-133.11L72.2 185.82c-13.79 17.3-26.48 35.59-36.72 55.59a32.35 32.35 0 0 0 0 29.19C89.71 376.41 197.07 448 320 448c26.91 0 52.87-4 77.89-10.46L346 397.39a144.13 144.13 0 0 1-26 2.61zm313.82 58.1l-110.55-85.44a331.25 331.25 0 0 0 81.25-102.07 32.35 32.35 0 0 0 0-29.19C550.29 135.59 442.93 64 320 64a308.15 308.15 0 0 0-147.32 37.7L45.46 3.37A16 16 0 0 0 23 6.18L3.37 31.45A16 16 0 0 0 6.18 53.9l588.36 454.73a16 16 0 0 0 22.46-2.81l19.64-25.27a16 16 0 0 0-2.82-22.45zm-183.72-142l-39.3-30.38A94.75 94.75 0 0 0 416 256a94.76 94.76 0 0 0-121.31-92.21A47.65 47.65 0 0 1 304 192a46.64 46.64 0 0 1-1.54 10l-73.61-56.89A142.31 142.31 0 0 1 320 112a143.92 143.92 0 0 1 144 144c0 21.63-5.29 41.79-13.9 60.11z"],
            "italic" => [320, 512, 10, "M320 48v32a16 16 0 0 1-16 16h-62.76l-80 320H208a16 16 0 0 1 16 16v32a16 16 0 0 1-16 16H16a16 16 0 0 1-16-16v-32a16 16 0 0 1 16-16h62.76l80-320H112a16 16 0 0 1-16-16V48a16 16 0 0 1 16-16h192a16 16 0 0 1 16 16z"],
            "lock" => [448, 512, 14, "M400 224h-24v-72C376 68.2 307.8 0 224 0S72 68.2 72 152v72H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V272c0-26.5-21.5-48-48-48zm-104 0H152v-72c0-39.7 32.3-72 72-72s72 32.3 72 72v72z"],
            "lock-open" => [576, 512, 18, "M423.5 0C339.5.3 272 69.5 272 153.5V224H48c-26.5 0-48 21.5-48 48v192c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V272c0-26.5-21.5-48-48-48h-48v-71.1c0-39.6 31.7-72.5 71.3-72.9 40-.4 72.7 32.1 72.7 72v80c0 13.3 10.7 24 24 24h32c13.3 0 24-10.7 24-24v-80C576 68 507.5-.3 423.5 0z"],
            "minus-circle" => [512, 512, 16, "M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zM124 296c-6.6 0-12-5.4-12-12v-56c0-6.6 5.4-12 12-12h264c6.6 0 12 5.4 12 12v56c0 6.6-5.4 12-12 12H124z"],
            "plus" => [448, 512, 14, "M416 208H272V64c0-17.67-14.33-32-32-32h-32c-17.67 0-32 14.33-32 32v144H32c-17.67 0-32 14.33-32 32v32c0 17.67 14.33 32 32 32h144v144c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32V304h144c17.67 0 32-14.33 32-32v-32c0-17.67-14.33-32-32-32z"],
            "plus-circle" => [512, 512, 16, "M256 8C119 8 8 119 8 256s111 248 248 248 248-111 248-248S393 8 256 8zm144 276c0 6.6-5.4 12-12 12h-92v92c0 6.6-5.4 12-12 12h-56c-6.6 0-12-5.4-12-12v-92h-92c-6.6 0-12-5.4-12-12v-56c0-6.6 5.4-12 12-12h92v-92c0-6.6 5.4-12 12-12h56c6.6 0 12 5.4 12 12v92h92c6.6 0 12 5.4 12 12v56z"],
            "times" => [352, 512, 11, "M242.72 256l100.07-100.07c12.28-12.28 12.28-32.19 0-44.48l-22.24-22.24c-12.28-12.28-32.19-12.28-44.48 0L176 189.28 75.93 89.21c-12.28-12.28-32.19-12.28-44.48 0L9.21 111.45c-12.28 12.28-12.28 32.19 0 44.48L109.28 256 9.21 356.07c-12.28 12.28-12.28 32.19 0 44.48l22.24 22.24c12.28 12.28 32.2 12.28 44.48 0L176 322.72l100.07 100.07c12.28 12.28 32.2 12.28 44.48 0l22.24-22.24c12.28-12.28 12.28-32.19 0-44.48L242.72 256z"]
        ];

        foreach ( $icons as $name => $attr ) {
            $svg = '<svg aria-hidden="true" focusable="false" class="svg-inline--fa fa-{name} fa-w-{faw}" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}"><path fill="currentColor" d="{d}"></path></svg>';
            $search = [ '{name}', '{w}', '{h}', '{faw}', '{d}' ];
            $replace = [ $name, $attr[0], $attr[1], $attr[2], $attr[3] ];
            $output[ $name ] = str_replace( $search, $replace, $svg );
        }

        return $output;
    }
}
