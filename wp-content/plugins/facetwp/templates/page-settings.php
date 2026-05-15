<?php

// Support tab HTML
include( FACETWP_DIR . '/templates/page-support.php' );
$support_html = new FacetWP_Support();
$support_html = $support_html->get_html();

// Settings
$settings = FWP()->settings->get_registered_settings();
$i18n = FWP()->settings->get_i18n_strings();
$image_sizes = FWP()->settings->get_image_size_labels();

// Useful data
$data = FWP()->helper->settings;
$facet_types = FWP()->helper->facet_types;
$data_sources = FWP()->helper->get_data_sources();
$layout_data = FWP()->builder->get_layout_data();
$query_data = FWP()->builder->get_query_data();

// Generate fields and handle aliases
$facet_fields = [];
$fields = FWP()->settings->get_registered_facet_fields();

foreach ( $fields as $name => $field ) {
    $output = FWP()->settings->get_facet_field_html( $name );
    $output = preg_replace( '/[ ]+/s', ' ', $output );
    $facet_fields[ $name ] = [
        'names' => isset( $field['items'] ) ? array_keys( $field['items'] ) : [ $name ],
        'html' => trim( $output )
    ];
}

// Get SVG icons
$svg_icons = FWP()->settings->get_svg();

// Clone facet settings HTML
$facet_clone = [];
$admin_scripts = '';

foreach ( $facet_types as $name => $class ) {
    if (method_exists( $class, 'admin_scripts' ) ) {
        ob_start();
        $class->admin_scripts();
        $admin_scripts .= ob_get_clean();
    }

    if ( method_exists( $class, 'settings_html' ) ) {
        ob_start();
        $class->settings_html();
        $facet_clone[ $name ] = trim( ob_get_clean() );
    }
}

?>

<script src="<?php echo FACETWP_URL; ?>/assets/vendor/vue/vue.min.js"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/vendor/vue/Sortable.min.js"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/vendor/vue/vuedraggable.min.js"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/vendor/vue/vue-select/vue-select.js"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/js/src/event-manager.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/vendor/fUtil/fUtil.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/vendor/fTip/fTip.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/vendor/fSelect/fSelect.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<script src="<?php echo FACETWP_URL; ?>/assets/vendor/vanilla-picker-mini/vanilla-picker-mini.min.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<?php

// Let add-ons register custom Vue components
echo $admin_scripts;

?>
<script src="<?php echo FACETWP_URL; ?>/assets/js/dist/admin.min.js?ver=<?php echo FACETWP_VERSION; ?>"></script>
<script>

window.FWP = {
    __: function(str) {
        return ('undefined' !== typeof FWP.i18n[str]) ? FWP.i18n[str] : str;
    },
    data: <?php echo json_encode( $data ); ?>,
    i18n: <?php echo json_encode( $i18n ); ?>,
    svg: <?php echo json_encode( $svg_icons ); ?>,
    image_sizes: <?php echo json_encode( $image_sizes ); ?>,
    clone: <?php echo json_encode( $facet_clone ); ?>,
    facet_fields: <?php echo json_encode( $facet_fields ); ?>,
    facet_types: <?php echo json_encode( $facet_types ); ?>,
    data_sources: <?php echo json_encode( $data_sources ); ?>,
    layout_data: <?php echo json_encode( $layout_data ); ?>,
    query_data: <?php echo json_encode( $query_data ); ?>,
    support_html: <?php echo json_encode( $support_html ); ?>,
    nonce: '<?php echo wp_create_nonce( 'fwp_admin_nonce' ); ?>',
    hooks: FWP.hooks // WP-JS-Hooks
};

// Settings load hook
FWP.data.settings = FWP.hooks.applyFilters('facetwp/load_settings', FWP.data.settings);

</script>
<link href="<?php echo FACETWP_URL; ?>/assets/css/admin.css?ver=<?php echo FACETWP_VERSION; ?>" rel="stylesheet">
<link href="<?php echo FACETWP_URL; ?>/assets/vendor/fTip/fTip.css?ver=<?php echo FACETWP_VERSION; ?>" rel="stylesheet">
<link href="<?php echo FACETWP_URL; ?>/assets/vendor/fSelect/fSelect.css?ver=<?php echo FACETWP_VERSION; ?>" rel="stylesheet">
<link href="<?php echo FACETWP_URL; ?>/assets/vendor/vue/vue-select/vue-select.css" rel="stylesheet">

<div id="app">
    <div class="facetwp-header">
      <a href="https://facetwp.com/help-center/" target="_blank" class="facetwp-logo" title="FacetWP Help Center">&nbsp;</a>
      <a href="https://facetwp.com/help-center/changelog/" target="_blank" class="facetwp-version" title="FacetWP Changelog">v<?php echo FACETWP_VERSION; ?></a>
        <span class="facetwp-header-nav">
            <a class="facetwp-tab" :class="{ active: active_tab == 'facets' }" @click="tabClick('facets')"><?php _e( 'Facets', 'fwp' ); ?></a><!--
            --><a class="facetwp-tab" :class="{ active: active_tab == 'templates' }" @click="tabClick('templates')"><?php _e( 'Listings', 'fwp' ); ?></a><!--
            --><a class="facetwp-tab" :class="{ active: active_tab == 'settings' }" @click="tabClick('settings')"><?php _e( 'Settings', 'fwp' ); ?><span class="facetwp-settings-icon" :class="{ active: app.settings.debug_mode == 'on' }"></span></a><!--
            --><a class="facetwp-tab" :class="{ active: active_tab == 'support' }" @click="tabClick('support')"><?php _e( 'Support', 'fwp' ); ?></a>
        </span>

        <span class="facetwp-actions">
            <div class="btn-split facetwp-rebuild">
                <div class="btn-label" @click="rebuildAction" v-html="indexButtonLabel"><?php _e( 'Re-index', 'fwp' ); ?></div><!--
                --><div class="btn-caret" @click="is_rebuild_open = !is_rebuild_open"><?php echo $svg_icons['caret-down']; ?></div>
                <div class="btn-dropdown" v-cloak v-show="is_rebuild_open">
                    <div class="dropdown-inner">
                        <div @click="showIndexerStats"><?php _e( 'Show indexer stats', 'fwp' ); ?></div>
                        <div @click="searchablePostTypes"><?php _e( 'Show indexable post types', 'fwp' ); ?></div>
                        <div @click="purgeIndexTable"><?php _e( 'Purge the index table', 'fwp' ); ?></div>
                    </div>
                </div>
            </div>
            <div class="btn-normal" @click="saveChanges">
                <?php _e( 'Save changes', 'fwp' ); ?>
            </div>
        </span>

        <div class="facetwp-response-wrap">
            <div class="facetwp-response"></div>
            <div class="facetwp-response-icon" data-status="load">
                <span></span>
            </div>
        </div>
    </div>

    <div class="wrap">
        <div class="facetwp-loading" :class="{ hidden: true }">
            Check your browser console if this text doesn't disappear
        </div>

        <!-- Facets tab -->

        <div class="facetwp-region facetwp-region-facets" :class="{ active: active_tab == 'facets' }">
            <h3 v-show="isEditing">
                <a @click="doneEditing"><?php _e( 'Facets', 'fwp' ); ?></a> &nbsp;&raquo;&nbsp;
                <span>{{ getItemLabel() }}</span>
            </h3>
            <h3 v-show="!isEditing">
                <?php _e( 'Facets', 'fwp' ); ?>
                <span class="facetwp-btn facetwp-add" @click="addItem('facet')"><?php _e( 'Add new', 'fwp' ); ?></span>
            </h3>

            <div class="content-facets" v-show="!isEditing">
                <div class="facetwp-table-header">
                    <div></div>
                    <div><?php _e( 'Label', 'fwp' ); ?></div>
                    <div><?php _e( 'Name', 'fwp' ); ?></div>
                    <div><?php _e( 'Type', 'fwp' ); ?></div>
                    <div><?php _e( 'Source', 'fwp' ); ?></div>
                    <div><?php _e( 'Rows', 'fwp' ); ?></div>
                    <div></div>
                </div>
                <facets :facets="app.facets"></facets>
            </div>

            <facet-edit v-if="editing_facet"></facet-edit>
        </div>

        <!-- Listings tab -->

        <div class="facetwp-region facetwp-region-templates" :class="{ active: active_tab == 'templates' }">
            <h3 v-show="isEditing">
                <a @click="doneEditing"><?php _e( 'Listings', 'fwp' ); ?></a> &nbsp;&raquo;&nbsp;
                <span>{{ getItemLabel() }}</span>
            </h3>
            <h3 v-show="!isEditing">
                <?php _e( 'Listings', 'fwp' ); ?>
                <span class="facetwp-btn facetwp-add" @click="addItem('template')"><?php _e( 'Add new', 'fwp' ); ?></span>
            </h3>

            <div class="content-templates" v-show="!isEditing">
                <div class="facetwp-table-header">
                    <div></div>
                    <div><?php _e( 'Label', 'fwp' ); ?></div>
                    <div><?php _e( 'Name', 'fwp' ); ?></div>
                    <div><?php _e( 'Display mode', 'fwp' ); ?></div>
                    <div><?php _e( 'Post types', 'fwp' ); ?></div>
                    <div></div>
                </div>
                <templates :templates="app.templates"></templates>
            </div>

            <template-edit v-if="editing_template"></template-edit>
        </div>

        <!-- Settings tab -->

        <div class="facetwp-region facetwp-region-settings" :class="{ active: active_tab == 'settings' }">
            <div class="facetwp-subnav">
                <?php foreach ( $settings as $key => $tab ) : ?>
                <a :class="{ active: active_subnav == '<?php echo $key; ?>' }" @click="active_subnav = '<?php echo $key; ?>'"><?php echo $tab['label']; ?></a>
                <?php endforeach; ?>
            </div>

            <?php foreach ( $settings as $key => $tab ) : ?>
            <div class="facetwp-settings-section" :class="{ active: active_subnav == '<?php echo $key; ?>' }">
                <?php foreach ( $tab['fields'] as $field_data ) : ?>
                <div class="facetwp-row"<?php if ( !empty($field_data['show']) ) echo 'v-show=\'' . $field_data['show'] . '\''; ?>>
                    <div>
                        <?php if ( isset( $field_data['notes'] ) ) : ?>
                        <div class="facetwp-tooltip">
                            <?php echo $field_data['label']; ?>
                            <div class="facetwp-tooltip-content"><?php echo $field_data['notes']; ?></div>
                        </div>
                        <?php else : ?>
                        <?php echo $field_data['label']; ?>
                        <?php endif; ?>
                    </div>
                    <div><?php echo $field_data['html']; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Support tab -->

        <div class="facetwp-region facetwp-region-support" :class="{ active: active_tab == 'support' }">
            <div v-if="is_support_loaded" v-html="FWP.support_html"></div>
        </div>

        <!-- Copy to clipboard -->

        <input class="facetwp-clipboard hidden" value="" />

    </div>
</div>
