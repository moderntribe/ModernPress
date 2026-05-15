(($) => {

    $(() => {
        init_vue();
        init_custom_js();
    });

    function init_vue() {

        Vue.config.devtools = true;

        Vue.component('v-select', VueSelect.VueSelect);

        Vue.filter('i18n', str => FWP.__(str));

        // Defaults mixin
        const builder_defaults = {
            methods: {
                defaultLayout() {
                    return {
                        items: [this.defaultRow()],
                        settings: this.getDefaultSettings('layout')
                    };
                },
                defaultRow() {
                    return {
                        type: 'row',
                        items: [this.defaultCol()],
                        settings: this.getDefaultSettings('row')
                    };
                },
                defaultCol() {
                    return {
                        type: 'col',
                        items: [],
                        settings: this.getDefaultSettings('col')
                    };
                },
                defaultItem(source) {
                    return {
                        type: 'item',
                        source,
                        settings: this.getDefaultSettings('item', source)
                    };
                },
                mergeSettings(settings, type, source) {
                    let defaults = this.getDefaultSettings(type, source);
                    let default_keys = Object.keys(defaults);
                    let setting_keys = Object.keys(settings);

                    // Automatically inject new settings
                    let missing_keys = default_keys.filter(name => !setting_keys.includes(name));

                    missing_keys.forEach((name, index) => {
                        Vue.set(settings, name, defaults[name]);
                    });

                    return settings;
                },
                getSettingsMeta() {
                    let settings = {
                        num_columns: {
                            type: 'number',
                            title: FWP.__('Number of grid columns '),
                            defaultValue: 1
                        },
                        grid_gap: {
                            type: 'number',
                            title: FWP.__('Spacing between results'),
                            defaultValue: 10
                        },
                        no_results_text: {
                            type: 'textarea',
                            title: FWP.__('No results text')
                        },
                        text_style: {
                            type: 'text-style',
                            title: FWP.__('Text style'),
                            tab: 'style',
                            defaultValue: {
                                align: '',
                                bold: false,
                                italic: false
                            }
                        },
                        text_color: {
                            type: 'color',
                            title: FWP.__('Text color'),
                            tab: 'style'
                        },
                        font_size: {
                            type: 'slider',
                            title: FWP.__('Font size'),
                            tab: 'style',
                            defaultValue: {
                                unit: 'px',
                                size: 0
                            }
                        },
                        background_color: {
                            type: 'color',
                            title: FWP.__('Background color'),
                            tab: 'style'
                        },
                        border: {
                            type: 'border',
                            title: FWP.__('Border'),
                            tab: 'style',
                            defaultValue: {
                                style: 'none',
                                color: '',
                                width: {
                                    unit: 'px',
                                    top: 0,
                                    right: 0,
                                    bottom: 0,
                                    left: 0
                                }
                            },
                            children: {
                                style: {
                                    type: 'select',
                                    title: FWP.__('Border style'),
                                    choices: {
                                        'none': FWP.__('None'),
                                        'solid': FWP.__('Solid'),
                                        'dashed': FWP.__('Dashed'),
                                        'dotted': FWP.__('Dotted'),
                                        'double': FWP.__('Double')
                                    }
                                },
                                color: {
                                    type: 'color',
                                    title: FWP.__('Border color')
                                },
                                width: {
                                    type: 'utrbl',
                                    title: FWP.__('Border width')
                                }
                            }
                        },
                        button_text: {
                            type: 'text',
                            title: FWP.__('Button text')
                        },
                        button_text_color: {
                            type: 'color',
                            title: FWP.__('Button text color')
                        },
                        button_color: {
                            type: 'color',
                            title: FWP.__('Button color')
                        },
                        button_padding: {
                            type: 'utrbl',
                            title: FWP.__('Button padding'),
                            defaultValue: {
                                unit: 'px',
                                top: 0,
                                right: 0,
                                bottom: 0,
                                left: 0
                            }
                        },
                        separator: {
                            type: 'text',
                            title: FWP.__('Separator'),
                            defaultValue: ', '
                        },
                        custom_css: {
                            type: 'textarea',
                            title: FWP.__('Custom CSS'),
                            tab: 'style'
                        },
                        grid_template_columns: {
                            type: 'text',
                            title: FWP.__('Column widths'),
                            defaultValue: '1fr'
                        },
                        content: {
                            type: 'textarea',
                            title: FWP.__('Content')
                        },
                        image_size: {
                            type: 'select',
                            title: FWP.__('Image size'),
                            defaultValue: 'thumbnail',
                            choices: FWP.image_sizes,
                            v_show: [
                                { type: 'source', value: 'featured_image' }
                            ]
                        },
                        author_field: {
                            type: 'select',
                            title: FWP.__('Author field'),
                            defaultValue: 'display_name',
                            choices: {
                                'display_name': FWP.__('Display name'),
                                'user_login': FWP.__('User login'),
                                'ID': FWP.__('User ID')
                            }
                        },
                        field_type: {
                            type: 'select',
                            title: FWP.__('Field type'),
                            defaultValue: 'text',
                            choices: {
                                'text': 'Text',
                                'date': 'Date',
                                'number': 'Number'
                            }
                        },
                        date_format: {
                            type: 'text',
                            title: FWP.__('Date format'),
                            defaultValue: 'F j, Y',
                            v_show: [
                                { type: 'field_type', value: 'date' },
                                { type: 'source', value: 'post_date' },
                                { type: 'source', value: 'post_modified' }
                            ]
                        },
                        input_format: {
                            type: 'text',
                            title: FWP.__('Input format'),
                            defaultValue: 'Y-m-d',
                            v_show: [
                                { type: 'field_type', value: 'date' },
                                { type: 'source', value: 'post_date' },
                                { type: 'source', value: 'post_modified' }
                            ]
                        },
                        number_format: {
                            type: 'select',
                            title: FWP.__('Number format'),
                            choices: {
                                '': FWP.__('None'),
                                'n': '1234',
                                'n.n': '1234.5',
                                'n.nn': '1234.56',
                                'n,n': '1,234',
                                'n,n.n': '1,234.5',
                                'n,n.nn': '1,234.56'
                            },
                            v_show: [
                                { type: 'field_type', value: 'number' }
                            ]
                        },
                        link: {
                            type: 'link',
                            title: FWP.__('Link'),
                            defaultValue: {
                                type: 'none',
                                href: '',
                                target: ''
                            },
                            children: {
                                type: {
                                    type: 'select',
                                    title: FWP.__('Link type'),
                                    choices: {
                                        'none': FWP.__('None'),
                                        'post': FWP.__('Post URL'),
                                        'custom': FWP.__('Custom URL')
                                    }
                                }
                            }
                        },
                        prefix: {
                            type: 'text',
                            title: FWP.__('Prefix')
                        },
                        suffix: {
                            type: 'text',
                            title: FWP.__('Suffix')
                        },
                        is_hidden: {
                            type: 'checkbox',
                            defaultValue: false,
                            suffix: FWP.__('Hide item?')
                        },
                        padding: {
                            type: 'utrbl',
                            title: FWP.__('Padding'),
                            defaultValue: {
                                unit: 'px',
                                top: 0,
                                right: 0,
                                bottom: 0,
                                left: 0
                            },
                            tab: 'style'
                        },
                        name: {
                            type: 'text',
                            title: FWP.__('Unique name'),
                            notes: '(Required) unique element name, without spaces'
                        },
                        css_class: {
                            type: 'text',
                            title: FWP.__('CSS class'),
                            tab: 'style'
                        }
                    };

                    settings.button_border = this.$root.cloneObj(settings.border);
                    settings.button_border.title = FWP.__('Button border');
                    settings.button_border.tab = 'basic';

                    settings.term_link = this.$root.cloneObj(settings.link);
                    settings.term_link.children.type.choices = {
                        'none': FWP.__('None'),
                        'term': FWP.__('Term URL'),
                        'custom': FWP.__('Custom URL')
                    };

                    return settings;
                },
                getDefaultFields(type, source) {
                    let fields = [];

                    if ('layout' == type) {
                        fields.push('num_columns', 'grid_gap', 'no_results_text');
                    }

                    if ('row' == type) {
                        fields.push('grid_template_columns');
                    }

                    if ('item' == type) {
                        if ('html' == source) {
                            fields.push('content');
                        }
                        if ('featured_image' == source) {
                            fields.push('image_size', 'link');
                        }
                        if ('button' == source) {
                            fields.push('button_text', 'button_text_color', 'button_color', 'button_padding', 'button_border', 'link');
                        }
                        if ('post_date' == source || 'post_modified' == source) {
                            fields.push('date_format');
                        }
                        if ('post_title' == source) {
                            fields.push('link');
                        }
                        if ('post_author' == source) {
                            fields.push('author_field');
                        }
                        if (0 === source.indexOf('cf/')) {
                            fields.push('field_type', 'date_format', 'input_format', 'number_format', 'link');
                        }
                        if (0 === source.indexOf('woo/')) {
                            fields.push('field_type', 'date_format', 'input_format', 'number_format');
                        }
                        if (0 === source.indexOf('tax/')) {
                            fields.push('separator', 'term_link');
                        }
                        if (!['html', 'button', 'featured_image'].includes(source)) {
                            fields.push('prefix', 'suffix');
                        }
                    }

                    fields.push('border', 'background_color', 'padding', 'text_color', 'text_style', 'font_size', 'name', 'css_class');

                    if ('layout' == type) {
                        fields.push('custom_css');
                    }

                    if ('item' == type) {
                        fields.push('is_hidden');
                    }

                    return fields;
                },
                getDefaultSettings(type, source) {
                    let settings = {};
                    let settings_meta = this.getSettingsMeta();
                    let fields = this.getDefaultFields(type, source);

                    fields.forEach(name => {
                        let defaultValue = settings_meta[name].defaultValue || '';

                        if ('name' == name) {
                            defaultValue = 'el-' + Math.random().toString(36).substring(7);
                        }

                        settings[name] = defaultValue;
                    });

                    return settings;
                }
            }
        };

        /* ================ query builder ================ */

        Vue.component('query-builder', {
            props: {
                query_obj: {
                    type: Object,
                    required: true
                },
                template: {
                    type: Object,
                    required: true
                }
            },
            template: `
            <div class="qb-wrap">
                <h3>Which results should be in the listing?</h3>

                <div class="qb-condition">
                    {{ 'Fetch' | i18n }}
                    <v-select
                        v-model="query_obj.post_type"
                        :options="FWP.query_data.post_types"
                        :multiple="true"
                        :searchable="false"
                        :close-on-select="false"
                        placeholder="All post types">
                    </v-select>

                    {{ 'and show' | i18n }}
                    <input type="number" v-model.number="query_obj.posts_per_page" class="qb-posts-per-page" />
                    {{ 'per page' | i18n }}
                </div>

                <div class="qb-condition"
                    v-show="query_obj.orderby.length">
                    {{ 'Sort by' | i18n }}
                </div>

                <div v-for="(row, index) in query_obj.orderby" class="qb-condition">
                    <fselect :row="row">
                        <optgroup label="Posts">
                            <option value="ID">ID</option>
                            <option value="title">{{ 'Post Title' | i18n }}</option>
                            <option value="name">{{ 'Post Name' | i18n }}</option>
                            <option value="type">{{ 'Post Type' | i18n }}</option>
                            <option value="date">{{ 'Post Date' | i18n }}</option>
                            <option value="modified">{{ 'Post Modified' | i18n }}</option>
                            <option value="comment_count">{{ 'Comment Count' | i18n }}</option>
                            <option value="menu_order">{{ 'Menu Order' | i18n }}</option>
                            <option value="post__in">post__in</option>
                        </optgroup>
                        <optgroup label="Custom Fields">
                            <option v-for="(label, name) in FWP.data_sources.custom_fields.choices" :value="name">{{ label }}</option>
                        </optgroup>
                    </fselect>
                    <select v-model="row.type" v-show="row.key.substr(0, 3) == 'cf/'" class="qb-type">
                        <option value="CHAR">TEXT</option>
                        <option value="NUMERIC">NUMERIC</option>
                    </select>
                    <select v-model="row.order" class="qb-order">
                        <option value="ASC">ASC</option>
                        <option value="DESC">DESC</option>
                    </select>
                    <span @click="deleteSortCriteria(index)" class="qb-remove" v-html="FWP.svg['minus-circle']"></span>
                </div>

                <div class="qb-condition"
                    v-show="query_obj.filters.length">
                    {{ 'Narrow results by' | i18n }}
                </div>

                <div v-for="(row, index) in query_obj.filters" class="qb-condition">
                    <fselect :row="row">
                        <optgroup v-for="data in FWP.query_data.filter_by" :label="data.label">
                            <option v-for="(label, name) in data.choices" :value="name" v-html="label"></option>
                        </optgroup>
                    </fselect>

                    <select v-model="row.type" v-show="row.key.substr(0, 3) == 'cf/'" class="qb-type">
                        <option value="CHAR">TEXT</option>
                        <option value="NUMERIC">NUMERIC</option>
                        <option value="DATE">DATE</option>
                    </select>

                    <select v-model="row.compare" class="qb-compare">
                        <option v-if="showCompare('=', row)" value="=">=</option>
                        <option v-if="showCompare('!=', row)" value="!=">!=</option>
                        <option v-if="showCompare('>', row)" value=">">&gt;</option>
                        <option v-if="showCompare('>=', row)" value=">=">&gt;=</option>
                        <option v-if="showCompare('<', row)" value="<">&lt;</option>
                        <option v-if="showCompare('<=', row)" value="<=">&lt;=</option>
                        <option v-if="showCompare('IN', row)" value="IN">IN</option>
                        <option v-if="showCompare('NOT IN', row)" value="NOT IN">NOT IN</option>
                        <option v-if="showCompare('EXISTS', row)" value="EXISTS">EXISTS</option>
                        <option v-if="showCompare('NOT EXISTS', row)" value="NOT EXISTS">NOT EXISTS</option>
                        <option v-if="showCompare('EMPTY', row)" value="EMPTY">EMPTY</option>
                        <option v-if="showCompare('NOT EMPTY', row)" value="NOT EMPTY">NOT EMPTY</option>
                    </select>

                    <v-select
                        v-model="row.value"
                        v-show="maybeShowValue(row.compare)"
                        :options="[]"
                        :multiple="true"
                        :taggable="true"
                        :close-on-select="false"
                        :placeholder="getPlaceholder(row)">
                        <div slot="no-options">
                            Type a value, then press "Enter" to add it
                        </div>
                    </v-select>

                    <span @click="deleteFilterCriteria(index)" class="qb-remove" v-html="FWP.svg['minus-circle']"></span>
                </div>

                <div class="qb-actions">
                    <span class="facetwp-btn" @click="addSortCriteria">{{ 'Add query sort' | i18n }}</span>
                    <span class="facetwp-btn" @click="addFilterCriteria">{{ 'Add query filter' | i18n }}</span>
                    <span class="facetwp-btn" @click="$root.getQueryArgs(template)">{{ 'Convert to query args' | i18n }}</span>
                </div>
            </div>
            `,
            methods: {
                addTag(newTag, value) {
                    value.push(newTag);
                },
                getPlaceholder({key}) {
                    return ('tax/' == key.substr(0, 4)) ? FWP.__('Enter term slugs') : FWP.__('Enter values');
                },
                maybeShowValue(compare) {
                    return !['EXISTS', 'NOT EXISTS', 'EMPTY', 'NOT EMPTY'].includes(compare);
                },
                showCompare(option, {key, type}) {
                    if ('tax/' == key.substr(0, 4)) {
                        if (!['IN', 'NOT IN', 'EXISTS', 'NOT EXISTS'].includes(option)) {
                            return false;
                        }
                    }
                    else if (['ID', 'post_author', 'post_status', 'post_name'].includes(key)) {
                        if (option != 'IN' && option != 'NOT IN') {
                            return false;
                        }
                    }
                    else if ('DATE' == type || 'post_date' == key || 'post_modified' == key) {
                        if (!['>', '>=', '<', '<='].includes(option)) {
                            return false;
                        }
                    }
                    else if ('CHAR' == type) {
                        if (['>', '>=', '<', '<='].includes(option)) {
                            return false;
                        }
                    }
                    return true;
                },
                addSortCriteria() {
                    this.query_obj.orderby.push({
                        key: 'title',
                        order: 'ASC',
                        type: 'CHAR'
                    });
                },
                addFilterCriteria() {
                    this.query_obj.filters.push({
                        key: 'ID',
                        value: [],
                        compare: 'IN',
                        type: 'CHAR'
                    });
                },
                deleteSortCriteria(index) {
                    Vue.delete(this.query_obj.orderby, index);
                },
                deleteFilterCriteria(index) {
                    Vue.delete(this.query_obj.filters, index);
                }
            }
        });

        Vue.component('fselect', {
            data() {
                return {
                    prev_key: ''
                };
            },
            props: ['row'],
            template: `
            <select v-model="row.key" class="qb-object" :data-key="row.key">
                <slot></slot>
            </select>
            `,
            mounted() {
                fSelect(this.$el);
            },
            /**
             * fSelects won't refresh when deleting, so we need to
             * manually reload() the changed elements
             */
            beforeUpdate() {
                this.prev_key = this.$el.getAttribute('data-key');
            },
            updated() {
                if (this.row.key != this.prev_key) {
                    this.$el.fselect.reload();
                }
            }
        });

        /* ================ layout builder ================ */


        Vue.component('builder', {
            props: {
                layout: Object
            },
            template: `
            <div class="builder-wrap">
                <div class="builder-canvas-wrap">
                    <h3>How should an individual result appear?</h3>
                    <div class="builder-canvas">
                        <draggable :list="layout.items" handle=".builder-row-actions.not-child">
                            <builder-row
                                v-for="(row, index) in layout.items"
                                :row="row"
                                :rows="layout.items"
                                :index="index"
                                :key="index">
                            </builder-row>
                        </draggable>
                    </div>
                </div>
                <builder-settings :layout="layout"></builder-settings>
            </div>
            `
        });

        Vue.component('setting-wrap', {
            mixins: [builder_defaults],
            props: ['settings', 'name', 'source', 'tab'],
            template: `
            <div class="builder-setting" v-show="isVisible">
                <div v-if="meta.notes" class="setting-title facetwp-tooltip">
                    {{ title }}
                    <div class="facetwp-tooltip-content" v-html="meta.notes"></div>
                </div>
                <div v-else class="setting-title" v-html="title"></div>
                <div><component :is="getSettingComponent" v-bind="$props" :meta="meta"></component></div>
            </div>
            `,
            computed: {
                getSettingComponent() {
                    return 'setting-' + this.type;
                },
                isVisible() {
                    let ret = true;
                    let self = this;

                    if ('undefined' === typeof this.meta.tab) {
                        this.meta.tab = 'basic';
                    }

                    if (this.meta.tab !== this.tab) {
                        ret = false;
                    }
                    else if ('undefined' !== typeof this.meta.v_show) {
                        ret = false;
                        this.meta.v_show.forEach((cond, index) => {
                            let type = cond.type;
                            let setting_val = ('source' == type) ? self[type] : self.settings[type];
                            let cond_value = cond.value || '';
                            let cond_compare = cond.compare || '==';
                            let is_match = ('==' == cond_compare)
                                ? setting_val == cond_value
                                : setting_val != cond_value;

                            if (is_match) {
                                ret = true;
                            }
                        });
                    }

                    return ret;
                }
            },
            created() {
                this.settings_meta = this.getSettingsMeta();
                this.meta = this.settings_meta[this.name];
                this.type = this.meta.type;
                this.title = this.meta.title;
            }
        });

        Vue.component('setting-text', {
            props: ['settings', 'name', 'meta'],
            template: '<input type="text" v-model="settings[name]" :placeholder="meta.placeholder" />'
        });

        Vue.component('setting-number', {
            props: ['settings', 'name', 'meta'],
            template: '<input type="number" v-model.number="settings[name]" :placeholder="meta.placeholder" />'
        });

        Vue.component('setting-textarea', {
            props: ['settings', 'name', 'meta'],
            template: '<textarea v-model="settings[name]"></textarea>'
        });

        Vue.component('setting-slider', {
            props: ['settings', 'name', 'meta'],
            template: `
            <div>
                <input type="range" min="0" max="80" step="1" v-model.number="settings[name].size" />
                <span v-html="fontSizeLabel" style="vertical-align:top"></span>
            </div>
            `,
            computed: {
                fontSizeLabel() {
                    let val = this.settings[this.name];
                    return (0 === val.size) ? 'none' : val.size + val.unit;
                }
            }
        });

        Vue.component('setting-color', {
            props: ['settings', 'name', 'meta'],
            template: `
            <div class="color-wrap">
                <div class="color-canvas">
                    <span class="color-preview"></span>
                    <input type="text" class="color-input" v-model="settings[name]" />
                </div>
                <span class="color-clear">X</span>
            </div>`,
            mounted() {
                let self = this;
                let $canvas = self.$el.getElementsByClassName('color-canvas')[0];
                let $preview = self.$el.getElementsByClassName('color-preview')[0];
                let $input = self.$el.getElementsByClassName('color-input')[0];
                let $clear = self.$el.getElementsByClassName('color-clear')[0];
                $preview.style.backgroundColor = $input.value;

                let picker = new Picker({
                    parent: $canvas,
                    popup: 'left',
                    alpha: false,
                    onDone(color) {
                        let hex = color.hex().substr(0, 7);
                        self.settings[self.name] = hex;
                        $preview.style.backgroundColor = hex;
                    }
                });

                picker.onOpen = function(color) {
                    picker.setColor($input.value);
                };

                $clear.addEventListener('click', function() {
                    self.settings[self.name] = '';
                    $preview.style.backgroundColor = '';
                });
            }
        });

        Vue.component('setting-link', {
            props: ['settings', 'name', 'meta'],
            template: `
            <div>
                <setting-select
                    :settings="settings[name]"
                    name="type"
                    :meta="meta.children.type">
                </setting-select>

                <div v-show="settings[name].type == 'custom'">
                    <input
                        type="text"
                        v-model="settings[name].href"
                        placeholder="https://"
                    />
                </div>
                <div v-show="settings[name].type != 'none'">
                    <input
                        type="checkbox"
                        v-model="settings[name].target"
                        true-value="_blank"
                        false-value=""
                    />
                    {{ 'Open in new tab?' | i18n }}
                </div>
            </div>
            `
        });

        Vue.component('setting-border', {
            props: ['settings', 'name', 'meta'],
            template: `
            <div>
                <setting-select
                    :settings="settings[name]"
                    name="style"
                    :meta="meta.children.style">
                </setting-select>

                <div v-show="settings[name].style != 'none'">
                    <div v-html="meta.children.color.title" style="margin-top:10px"></div>

                    <setting-color
                        :settings="settings[name]"
                        name="color"
                        :meta="meta.children.color">
                    </setting-color>

                    <div v-html="meta.children.width.title" style="margin-top:10px"></div>

                    <setting-utrbl
                        :settings="settings[name]"
                        name="width"
                        :meta="meta.children.width">
                    </setting-utrbl>
                </div>
            </div>
            `
        });

        Vue.component('setting-checkbox', {
            props: ['settings', 'name', 'meta'],
            template: `
            <div>
                <input type="checkbox" v-model="settings[name]" /> {{ meta.suffix }}
            </div>
            `
        });

        Vue.component('setting-select', {
            props: ['settings', 'name', 'meta'],
            template: `
            <select v-model="settings[name]">
                <option v-for="(label, value) in meta.choices" :value="value">{{ label }}</option>
            </select>
            `
        });

        Vue.component('setting-utrbl', {
            props: ['settings', 'name', 'meta'],
            template: `
            <div>
                <div class="utrbl utrbl-unit"><input type="text" v-model="settings[name].unit" /><span>unit</span></div>
                <div class="utrbl"><input type="text" v-model.number="settings[name].top" /><span>top</span></div>
                <div class="utrbl"><input type="text" v-model.number="settings[name].right" /><span>right</span></div>
                <div class="utrbl"><input type="text" v-model.number="settings[name].bottom" /><span>bottom</span></div>
                <div class="utrbl"><input type="text" v-model.number="settings[name].left" /><span>left</span></div>
            </div>
            `
        });

        Vue.component('setting-text-style', {
            props: ['settings', 'name', 'meta'],
            template: `
            <div class="text-style-icons">
                <span @click="toggleChoice('align', 'left')" :class="{ active: isActive('align', 'left') }" v-html="FWP.svg['align-left']"></span>
                <span @click="toggleChoice('align', 'center')" :class="{ active: isActive('align', 'center') }" v-html="FWP.svg['align-center']"></span>
                <span @click="toggleChoice('align', 'right')" :class="{ active: isActive('align', 'right') }" v-html="FWP.svg['align-right']"></span>
                <span @click="toggleChoice('bold')" :class="{ active: isActive('bold') }" v-html="FWP.svg['bold']"></span>
                <span @click="toggleChoice('italic')" :class="{ active: isActive('italic') }" v-html="FWP.svg['italic']"></span>
            </div>
            `,
            methods: {
                toggleChoice(opt, val) {
                    let old_val = this.settings[this.name][opt];

                    if ('undefined' !== typeof val) {
                        this.settings[this.name][opt] = (val !== old_val) ? val : '';
                    }
                    else {
                        this.settings[this.name][opt] = ! old_val;
                    }
                },
                isActive(opt, val) {
                    let new_val = ('undefined' !== typeof val) ? val : true;
                    return this.settings[this.name][opt] === new_val;
                }
            }
        });

        Vue.component('builder-settings', {
            mixins: [builder_defaults],
            props: {
                layout: Object
            },
            data() {
                return {
                    title: '',
                    type: 'layout',
                    settings: this.layout.settings,
                    source: '',
                    active_tab: 'basic'
                }
            },
            template: `
            <div class="builder-settings-wrap">
                <h3>
                    <div v-show="this.title" class="builder-crumb">
                        <a href="javascript:;" @click="$root.$emit('edit-layout')">{{ 'Settings' | i18n }}</a> &raquo;
                    </div>
                    {{ settingTitle }}
                </h3>
                <div class="builder-settings">
                    <div class="template-tabs">
                        <span @click="setActiveTab('basic')" :class="isActiveTab('basic')">{{ 'Basic' | i18n }}</span>
                        <span @click="setActiveTab('style')" :class="isActiveTab('style')">{{ 'Style' | i18n }}</span>
                    </div>
                    <setting-wrap
                        v-for="name in settingsFields"
                        :settings="settings"
                        :name="name"
                        :source="source"
                        :tab="active_tab"
                        :key="uniqueKey()">
                    </setting-wrap>
                </div>
            </div>
            `,
            computed: {
                settingTitle() {
                    return ('' === this.title) ? FWP.__('Settings') : this.title;
                },
                settingsFields() {
                    return this.getDefaultFields(this.type, this.source);
                }
            },
            methods: {
                uniqueKey() {
                    // method to prevent caching
                    return Math.floor(Math.random() * 999999);
                },
                isActiveTab(which) {
                    return (this.active_tab === which) ? 'active' : '';
                },
                setActiveTab(which) {
                    this.active_tab = which;
                }
            },
            created() {
                let self = this;

                this.$root.$on('edit-layout', () => {
                    self.title = '';
                    self.type = 'layout';
                    self.settings = self.mergeSettings(self.layout.settings, self.type);
                    self.source = '';
                });

                this.$root.$on('edit-row', ({settings}, num) => {
                    self.title = FWP.__('Row') + ' ' + num;
                    self.type = 'row';
                    self.settings = self.mergeSettings(settings, self.type);
                    self.source = '';
                });

                this.$root.$on('edit-col', ({settings}, num) => {
                    self.title = FWP.__('Column') + ' ' + num;
                    self.type = 'col';
                    self.settings = self.mergeSettings(settings, self.type);
                    self.source = '';
                });

                this.$root.$on('edit-item', ({source, settings}) => {
                    self.title = FWP.layout_data[source];
                    self.type = 'item';
                    self.settings = self.mergeSettings(settings, self.type, source);
                    self.source = source;
                });
            }
        });

        Vue.component('builder-row', {
            mixins: [builder_defaults],
            props: {
                row: Object,
                rows: Array,
                index: Number,
                is_child: Boolean
            },
            template: `
            <div class="builder-row">
                <div class="builder-row-actions" :class="classIsChild">
                    <span @click="editRow" title="Edit row" v-html="FWP.svg['cog']"></span>
                    <span @click="addCol" title="Add columm" v-html="FWP.svg['columns']"></span>
                    <span @click="addRow" title="Add row" v-html="FWP.svg['plus']"></span>
                    <span @click="deleteRow" title="Delete row" v-html="FWP.svg['times']"></span>
                </div>
                <div class="builder-row-inner" :style="{ gridTemplateColumns: row.settings.grid_template_columns }">
                    <builder-col
                        v-for="(col, index) in row.items"
                        :col="col"
                        :cols="row.items"
                        :index="index"
                        :key="index">
                    </builder-col>
                </div>
            </div>
            `,
            computed: {
                classIsChild() {
                    return this.is_child ? 'is-child' : 'not-child';
                }
            },
            methods: {
                addRow() {
                    this.rows.splice(this.index + 1, 0, this.defaultRow());

                    if (1 < this.rows.length) {
                        this.$root.$emit('edit-row', this.rows[this.index + 1], this.index + 2);
                    }
                    else {
                        this.$root.$emit('edit-layout');
                    }
                },
                addCol() {
                    let len = this.row.items.push(this.defaultCol());
                    this.$root.$emit('edit-col', this.row.items[len - 1], len);

                    let grid_str = '1fr '.repeat(this.row.items.length).trim();
                    this.row.settings.grid_template_columns = grid_str;
                },
                editRow() {
                    this.$root.$emit('edit-row', this.row, this.index + 1);
                },
                deleteRow() {
                    Vue.delete(this.rows, this.index);
                    this.$root.$emit('edit-layout');

                    // Add default row
                    if (this.rows.length < 1) {
                        if (! this.is_child) {
                            this.addRow();
                        }
                    }
                }
            }
        });

        Vue.component('builder-col', {
            mixins: [builder_defaults],
            props: {
                col: Object,
                cols: Array,
                index: Number
            },
            data() {
                return {
                    adding_item: false
                }
            },
            template: `
            <div class="builder-col">
                <col-resizer :cols="cols" :index="index" v-show="index < (cols.length - 1)"></col-resizer>
                <popover :col="col" v-if="adding_item"></popover>
                <div class="builder-col-actions">
                    <span @click="editCol" title="Edit columm" v-html="FWP.svg['cog']"></span>
                    <span @click="deleteCol" title="Delete column" v-html="FWP.svg['times']"></span>
                </div>
                <div class="builder-col-inner" :class="[ !col.items.length ? 'empty-col' : '' ]">
                    <draggable v-model="col.items" handle=".item-drag" group="drag-across-columns" class="draggable">
                        <div v-for="(item, index) in col.items" :key="index">
                        <builder-item
                            v-if="item.type != 'row'"
                            :item="item"
                            :items="col.items"
                            :index="index">
                        </builder-item>
                        <builder-row
                            v-if="item.type == 'row'"
                            :row="item"
                            :rows="col.items"
                            :index="index"
                            :is_child="true">
                        </builder-row>
                        </div>
                        <div class="builder-empty-view" @click="addItem">
                            <div class="builder-first-add">+</div>
                        </div>
                    </draggable>
                </div>
            </div>
            `,
            methods: {
                addItem() {
                    this.adding_item = ! this.adding_item;
                },
                editCol() {
                    this.$root.$emit('edit-col', this.col, this.index + 1);
                    this.adding_item = false;
                },
                deleteCol() {
                    // Remove the column
                    this.cols.splice(this.index, 1);

                    // Show the "Layout" settings
                    this.$root.$emit('edit-layout');

                    // Add default column
                    if (this.cols.length < 1) {
                        this.cols.push(this.defaultCol());
                    }

                    // Adjust the row's `grid_template_columns` string
                    let grid_str = '1fr '.repeat(this.cols.length).trim();
                    this.$parent.row.settings.grid_template_columns = grid_str;
                },
                away() {
                    this.adding_item = false;
                }
            }
        });

        Vue.component('col-resizer', {
            props: {
                cols: Array,
                index: Number
            },
            data() {
                return {
                    isResizing: false
                }
            },
            template: '<div :class="classNames" @mousedown="onMouseDown"></div>',
            computed: {
                classNames() {
                    return [
                        'resizer',
                        this.isResizing ? 'is-resizing' : ''
                    ];
                }
            },
            methods: {
                onMouseDown({ target: resizer, pageX: initialPageX, pageY: initialPageY }) {
                    if (! resizer.classList.contains('resizer')) {
                        return;
                    }

                    let self = this;
                    let pane = resizer.parentElement;
                    let row_inner = pane.parentElement;
                    let initialPaneWidth = pane.offsetWidth;

                    const resize = (initialSize, offset = 0) => {
                        let containerWidth = row_inner.clientWidth;
                        let paneWidth = initialSize + offset;
                        let width = ((paneWidth / containerWidth) * 100).toFixed(1) + '%';
                        let gridColumns = this.$parent.$parent.row.settings.grid_template_columns.split(' ');

                        gridColumns[this.index] = width;

                        this.$parent.$parent.row.settings.grid_template_columns = gridColumns.join(' ');
                    };

                    // This adds is-resizing class to container
                    self.isResizing = true;

                    const onMouseMove = ({ pageX, pageY }) => {
                        resize(initialPaneWidth, pageX - initialPageX);
                    };

                    const onMouseUp = () => {
                        // Run resize one more time to set computed width/height.
                        resize(pane.clientWidth);

                        // This removes is-resizing class to container
                        self.isResizing = false;

                        window.removeEventListener('mousemove', onMouseMove);
                        window.removeEventListener('mouseup', onMouseUp);
                    };

                    window.addEventListener('mousemove', onMouseMove);
                    window.addEventListener('mouseup', onMouseUp);
                }
            }
        });

        Vue.component('builder-item', {
            props: {
                item: Object,
                items: Array,
                index: Number
            },
            template: `
            <div class="builder-item">
                    <div class="builder-item-actions">
                    <span @click="deleteItem" title="Delete item" v-html="FWP.svg['times']"></span>
                </div>
                <div class="builder-item-inner" @click="editItem" :class="[ item.settings.is_hidden ? 'is-hidden' : '' ]">
                    <span class="item-drag" v-html="FWP.layout_data[item.source]"></span>
                    <span v-if="item.settings.is_hidden" v-html="FWP.svg['eye-slash']"></span>
                </div>
            </div>
            `,
            methods: {
                editItem() {
                    this.$root.$emit('edit-item', this.item);
                },
                deleteItem() {
                    this.items.splice(this.index, 1);
                    this.$root.$emit('edit-layout');
                }
            }
        });

        Vue.component('popover', {
            mixins: [builder_defaults],
            props: {
                col: Object
            },
            data() {
                return {
                    keywords: ''
                }
            },
            template: `
            <div class="popover" tabindex="0" @focusout="handleBlur">
                <div class="popover-search">
                    <input
                        type="text"
                        ref="keywords"
                        placeholder="Start typing"
                        v-model="keywords"
                    />
                </div>
                <div class="popover-choices">
                    <div
                        @click="saveItem(source)"
                        v-for="(label, source) in FWP.layout_data"
                        v-show="isMatch(label)"
                        v-html="label">
                    </div>
                </div>
            </div>
            `,
            methods: {
                handleBlur(e) {
                    if (!e.currentTarget.contains(e.relatedTarget)) {
                        this.$parent.adding_item = false;
                    }
                },
                isMatch(label) {
                    let bool = ('' == this.keywords) ? true : false;

                    if (false === bool) {
                        let needle = this.keywords.toLowerCase();
                        let haystack = label.toLowerCase();
                        if (haystack.includes(needle)) {
                            bool = true;
                        }
                    }

                    return bool;
                },
                saveItem(source) {
                    if ('row' == source) {
                        let len = this.col.items.push(this.defaultRow());
                        this.$root.$emit('edit-row', this.col.items[len - 1], len);
                    }
                    else {
                        let len = this.col.items.push(this.defaultItem(source));
                        this.$root.$emit('edit-item', this.col.items[len - 1]);
                    }

                    this.$parent.adding_item = false;
                }
            },
            mounted() {
                this.$refs.keywords.focus();
            }
        });


        /* ================ facets / templates ================ */


        Vue.component('facets', {
            props: ['facets'],
            template: `
            <draggable class="facetwp-cards" v-model="$root.app.facets" handle=".card-drag">
                <div
                    class="facetwp-card"
                    v-for="(facet, index) in facets"
                    @click="$root.editItem('facet', facet)"
                >
                    <div class="card-drag">&#9776;</div>
                    <div class="card-label">
                        <span class="label-text">{{ facet.label }}</span>
                        <span v-if="facet._code" v-html="FWP.svg['lock']"></span>
                    </div>
                    <div class="card-name">{{ facet.name }}</div>
                    <div class="card-type">{{ facet.type }}</div>
                    <div class="card-source" v-html="getSource(facet.source)"></div>
                    <div class="card-rows">{{ getRowCount(facet.name) }}</div>
                    <div class="card-actions">
                        <div class="actions-wrap">
                            <div class="actions-btn" v-html="FWP.svg['cog']"></div>
                            <div class="actions-modal">
                                <div @click.stop="$root.copyToClipboard(facet.name, 'facet', $event)">Copy shortcode</div>
                                <div @click.stop="$root.duplicateItem('facet', index)">Duplicate</div>
                                <div @click.stop="$root.deleteItem('facet', index)">Delete</div>
                            </div>
                        </div>
                    </div>
                </div>
            </draggable>
            `,
            methods: {
                getSource(source) {
                    return FWP.layout_data[source] || '-';
                },
                getRowCount(facet_name) {
                    if (this.$root.is_indexing) {
                        return '...';
                    }
                    return this.$root.row_counts[facet_name] || '-';
                }
            }
        });

        Vue.component('templates', {
            props: ['templates'],
            template: `
            <draggable class="facetwp-cards" v-model="$root.app.templates" handle=".card-drag">
                <div
                    class="facetwp-card"
                    v-for="(template, index) in templates"
                    @click="$root.editItem('template', template)"
                >
                    <div class="card-drag">&#9776;</div>
                    <div class="card-label">
                        <span class="label-text">{{ template.label }}</span>
                        <span v-if="template._code" v-html="FWP.svg['lock']"></span>
                    </div>
                    <div class="card-name">{{ template.name }}</div>
                    <div class="card-display-mode">{{ getDisplayMode(index) }}</div>
                    <div class="card-post-types">{{ getPostTypes(index) }}</div>
                    <div class="card-actions">
                        <div class="actions-wrap">
                            <div class="actions-btn" v-html="FWP.svg['cog']"></div>
                            <div class="actions-modal">
                                <div @click.stop="$root.copyToClipboard(template.name, 'template', $event)">Copy shortcode</div>
                                <div @click.stop="$root.duplicateItem('template', index)">Duplicate</div>
                                <div @click.stop="$root.deleteItem('template', index)">Delete</div>
                            </div>
                        </div>
                    </div>
                </div>
            </draggable>
            `,
            methods: {
                getDisplayMode(index) {
                    let template = this.templates[index];
                    return ('undefined' !== typeof template.modes) ? template.modes.display : 'advanced';
                },
                getPostTypes(index) {
                    let template = this.templates[index];
                    if ('undefined' !== typeof template.modes) {
                        if ('visual' == template.modes.query) {
                            let post_types = template.query_obj.post_type;
                            if (0 === post_types.length) {
                                return '<any>';
                            }
                            else {
                                return post_types.map(type => type.label).join(', ');
                            }
                        }
                    }
                    return '<raw query>';
                }
            }
        });

        Vue.component('facet-edit', {
            data() {
                return {
                    facet: {},
                    codeEditors: {
                        marker_content: null
                    }
                }
            },
            created() {
                this.facet = this.$root.editing;
            },
            mounted() {
                if ('map' === this.facet.type) {

                    // Init template code editor
                    this.initCodeMirror('marker-content-editor', 'marker_content', false);
                }
            },
            watch: {
                'facet.ui_type': function(val) {
                    switch (val) {
                        case 'radio':
                            Vue.delete(this.facet, 'multiple');
                            Vue.delete(this.facet, 'show_expanded');
                            Vue.delete(this.facet, 'hierarchical');
                            break;
                        case 'dropdown':
                            Vue.delete(this.facet, 'multiple');
                            Vue.delete(this.facet, 'show_expanded');
                            break;
                        case 'checkboxes':
                            Vue.delete(this.facet, 'multiple');
                            break;
                        case 'fselect':
                            Vue.delete(this.facet, 'show_expanded');
                            break;
                    }
                }
            },
            methods: {
                setName(e) {
                    this.facet.name = this.$root.sanitizeName(e.target.innerHTML);
                },
                unlock() {
                    Vue.delete(this.facet, '_code');
                },
                initCodeMirror(textareaId, type, reset) {
                    try {
                        let textarea = document.getElementById(textareaId);

                        // Reset editor and start over when switching facet type and back to Map
                        // Same behavior as other facet settings when switching facet type
                        if (reset) {
                            this.codeEditors[type] = null;
                        }
                        let editor = wp.codeEditor.initialize(jQuery(textarea), fwp_editor_settings);

                        // Update when editor content changed
                        this.updateCodeMirrorContent(editor, type);

                        // Store a reference to the CodeMirror instance
                        this.codeEditors[type] = editor;

                    } catch (error) {
                        console.error('Error initializing CodeMirror:', error);
                    }
                },
                updateCodeMirrorContent(editor, type) {

                    // Process editor changes so they can be saved
                    try {
                        if (editor !== null) {
                            editor.codemirror.on('change', () => {
                                let newContent = editor.codemirror.getValue();
                                newContent = JSON.parse(JSON.stringify(newContent));
                                Vue.set(this.facet, type, newContent);
                            });
                        }
                    } catch (error) {
                        console.error('Error updating CodeMirror content:', error);
                    }
                }
            },
            template: `
            <div>
                <div class="item-locked" v-if="facet._code">
                    This facet is registered in code. Click to allow edits:
                    <span @click="unlock" v-html="FWP.svg['lock-open']"></span>
                </div>
                <div class="facetwp-content" :class="[ 'type-' + facet.type, { locked: facet._code } ]">
                    <div class="facetwp-row">
                        <div>{{ 'Label' | i18n }}</div>
                        <div>
                            <input
                                type="text"
                                v-model="facet.label"
                                @focus="$root.isNameEditable(facet)"
                                @keyup="$root.maybeEditName(facet)"
                            />
                            <code class="item-name" contenteditable v-text="facet.name" @blur="setName" @keydown.enter.prevent autocorrect="off"></code>
                            <span class="facetwp-btn" @click="$root.copyToClipboard(facet.name, 'facet', $event)">
                                {{ 'Copy shortcode' | i18n }}
                            </span>
                        </div>
                    </div>
                    <div class="facetwp-row">
                        <div>{{ 'Facet type' | i18n }}</div>
                        <div>
                            <facet-types
                                :facet="facet"
                                :selected="facet.type"
                                :types="FWP.facet_types">
                            </facet-types>
                        </div>
                    </div>
                    <div class="facetwp-row field-data-source">
                        <div>{{ 'Data source' | i18n }}</div>
                        <div>
                            <data-sources :facet="facet"></data-sources>
                        </div>
                    </div>
                    <facet-settings :facet="facet" :initCodeMirror="initCodeMirror"></facet-settings>
                </div>
            </div>
            `
        });

        Vue.component('template-edit', {
            mixins: [builder_defaults],
            data() {
                return {
                    template: {},
                    tab: 'display',
                    codeEditors: {
                        template: null,
                        query: null
                    }
                }
            },
            created() {
                this.template = this.$root.editing;

                // Set defaults for the layout builder
                if (! this.template.layout) {
                    Vue.set(this.template, 'layout', this.defaultLayout());
                }

                // Set defaults for the query builder
                if (! this.template.query_obj) {
                    Vue.set(this.template, 'query_obj', {
                        post_type: [],
                        posts_per_page: 10,
                        orderby: [],
                        filters: []
                    });
                }

                // Set the modes
                if (! this.template.modes) {
                    Vue.set(this.template, 'modes', {
                        display: ('' !== this.template.template) ? 'advanced' : 'visual',
                        query: ('' !== this.template.query) ? 'advanced' : 'visual'
                    });
                }
            },
            mounted() {

                // Init template code editor
                this.initCodeMirror('template-editor', 'template');

                // Update query editor when 'Convert to query args' button is clicked
                this.$root.$on('query-updated', () => {
                    this.codeEditors['query'].codemirror.setValue(this.template.query);
                });
            },
            watch: {
                tab(newTab, oldTab) {

                    // Init query code editor
                    if (newTab !== oldTab) {
                        if (newTab === 'query') {
                            this.initCodeMirror('query-editor', 'query');
                            this.refreshCodeMirror(this.codeEditors['query']);
                        }
                    }
                }
            },
            methods: {
                setName(e) {
                    this.template.name = this.$root.sanitizeName(e.target.innerHTML);
                },
                isMode(mode) {
                    return this.template.modes[this.tab] === mode;
                },
                switchMode() {
                    const now = this.template.modes[this.tab];
                    this.template.modes[this.tab] = ('visual' === now) ? 'advanced' : 'visual';

                    let editor = null;

                    // Refresh editor when switching to advanced on both tabs
                    if ('visual' === now) {
                        if (this.tab === 'display') {
                            editor = this.codeEditors['template'];
                        } else {
                            editor = this.codeEditors['query'];
                        }
                    }
                    this.refreshCodeMirror(editor);

                },
                unlock() {
                    Vue.delete(this.template, '_code');
                },
                initCodeMirror(textareaId, type) {
                    try {
                        let textarea = document.getElementById(textareaId);

                        if (this.codeEditors[type] === null) {
                            let editor = wp.codeEditor.initialize(jQuery(textarea), fwp_editor_settings);

                            // Update when editor content changed
                            this.updateCodeMirrorContent(editor, type);

                            // Store a reference to the CodeMirror instance
                            this.codeEditors[type] = editor;
                        }
                    } catch (error) {
                        console.error('Error initializing CodeMirror:', error);
                    }
                },
                refreshCodeMirror(editor) {
                    if ( editor !== null ) {
                        this.$nextTick(() => {
                            editor.codemirror.refresh();
                        });
                    }
                },
                updateCodeMirrorContent(editor, type) {

                    // Process editor changes so they can be saved
                    try {
                        if (editor !== null) {
                            editor.codemirror.on('change', () => {
                                let newContent = editor.codemirror.getValue();
                                newContent = JSON.parse(JSON.stringify(newContent));
                                Vue.set(this.template, type, newContent);
                            });
                        }
                    } catch (error) {
                        console.error('Error updating CodeMirror content:', error);
                    }
                }
            },
            template: `
            <div>
                <div class="item-locked" v-if="template._code">
                    This template is registered in code. Click to allow edits:
                    <span @click="unlock" v-html="FWP.svg['lock-open']"></span>
                </div>
                <div class="facetwp-content" :class="{ locked: template._code }">
                    <div class="table-row">
                        <input
                            type="text"
                            v-model="template.label"
                            @focus="$root.isNameEditable(template)"
                            @keyup="$root.maybeEditName(template)"
                        />
                        <code class="item-name" contenteditable v-text="template.name" @blur="setName" @keydown.enter.prevent autocorrect="off"></code>
                        <span class="facetwp-btn" @click="$root.copyToClipboard(template.name, 'template', $event)">
                            {{ 'Copy shortcode' | i18n }}
                        </span>
                    </div>

                    <label class="facetwp-dev-mode">
                        <input type="checkbox" :checked="isMode('advanced')" @change="switchMode()"> Dev mode?
                    </label>

                    <div class="template-tabs top-level">
                        <span @click="tab = 'display'" :class="{ active: tab == 'display' }">{{ 'Display' | i18n }}</span>
                        <span @click="tab = 'query'" :class="{ active: tab == 'query' }">{{ 'Query' | i18n }}</span>
                    </div>

                    <div v-show="tab == 'display'">
                        <div class="table-row" v-show="template.modes.display == 'visual'">
                            <builder :layout="template.layout"></builder>
                        </div>
                        <div class="table-row" v-show="template.modes.display == 'advanced'">
                            <h3>{{ 'Display Code' | i18n }} <a class="facetwp-btn" href="https://facetwp.com/help-center/listing-templates/listing-builder/using-the-listing-builder-in-dev-mode/#how-to-use-display-code-in-dev-mode" target="_blank">{{ 'Help' | i18n }}</a></h3>
                            <textarea id="template-editor" v-model="template.template"></textarea>
                            <p class="note">{{ 'To search your code, click in the editor, then use Ctrl+F (Windows/Linux) or Cmd+F (Mac).' | i18n }}</p>
                        </div>
                    </div>

                    <div v-show="tab == 'query'">
                        <div class="table-row" v-show="template.modes.query == 'visual'">
                            <query-builder :query_obj="template.query_obj" :template="template"></query-builder>
                        </div>
                        <div class="table-row" v-show="template.modes.query == 'advanced'">
                            <h3>{{ 'Query Arguments' | i18n }} <a class="facetwp-btn" href="https://facetwp.com/help-center/listing-templates/listing-builder/using-the-listing-builder-in-dev-mode/#how-to-use-query-arguments-in-dev-mode" target="_blank">{{ 'Help' | i18n }}</a></h3>
                            <textarea id="query-editor" v-model="template.query"></textarea>
                            <p class="note">{{ 'To search your code, click in the editor, then use Ctrl+F (Windows/Linux) or Cmd+F (Mac).' | i18n }}</p>
                        </div>
                    </div>
                </div>
            </div>
            `
        });

        Vue.component('facet-types', {
            props: ['facet', 'selected', 'types'],
            template: `
            <select v-model="facet.type">
                <option v-for="(type, key) in types" :value="key" :selected="selected == key">{{ type.label }}</option>
            </select>
            `
        });

        Vue.component('facet-settings', {
            props: ['facet','initCodeMirror'],
            template: '<component :is="dynComponent" :facet="facet"></component>',
            methods: {
                getFields(aliases) {
                    let output = [];
                    $.each(aliases, function(name) {
                        output = output.concat(FWP.facet_fields[name].names);
                    });
                    return output;
                }
            },
            computed: {
                // dynamic component so the data bindings (e.g. v-model) get compiled
                dynComponent() {
                    return {
                        template: '<div class="facet-fields">' + this.settingsHtml + '</div>',
                        props: ['facet']
                    }
                },
                settingsHtml() {
                    let self = this;
                    let facet_obj = FWP.facet_types[self.facet.type];
                    let aliases = facet_obj.fields;

                    // Support for settings_html() in < 3.9
                    if ('undefined' === typeof aliases) {
                        if ('undefined' !== typeof FWP.clone[self.facet.type]) {
                            FWP.facet_fields[self.facet.type + '_fields'] = {
                                names: [],
                                html: FWP.clone[self.facet.type]
                            };

                            var $html = $(FWP.clone[self.facet.type]);
                            $.each($html.nodes[0].children, function(chunk) {
                                $(chunk).find('input, textarea, select, [setting-name]').each(function() {
                                    let $el = $(this);
                                    let setting_name = $el.attr('setting-name');

                                    if (null === setting_name) {
                                        setting_name = $el.attr('class').split(' ')[0].replace(/-/g, '_').substr(6);
                                    }

                                    FWP.facet_fields[self.facet.type + '_fields'].names.push(setting_name);
                                });
                            });

                            aliases = [self.facet.type + '_fields'];
                        }
                    }

                    // Get the actual fields by parsing the aliases (groups)
                    let fields = self.getFields(aliases);
                    let html = '';

                    let combined = ['label', 'name', 'type', 'source', '_code'].concat(fields);

                    // Remove irrelevant settings
                    $.each(Object.keys(self.facet), function(setting_name) {
                        if (-1 == combined.indexOf(setting_name)) {
                            Vue.delete(self.facet, setting_name);
                        }
                    });

                    // Add new settings
                    $.each(aliases, function(alias_name) {
                        let $parsed = $(FWP.facet_fields[alias_name].html);

                        $.each(FWP.facet_fields[alias_name].names, function(setting_name) {
                            let name_dashed = setting_name.replace(/_/g, '-');
                            let $input = $parsed.find('.facet-' + name_dashed);
                            let val = $input.val();

                            if (0 < $input.len()) {
                                $input.attr('v-model', 'facet.' + setting_name);

                                if ('undefined' === typeof self.facet[setting_name]) {
                                    if ($input.is('[type=checkbox]')) {
                                        val = $input.nodes[0].checked ? 'yes' : 'no';
                                    }
                                    if ('[]' === val) {
                                        val = [];
                                    }
                                }
                                else {
                                    val = self.facet[setting_name];
                                    Vue.delete(self.facet, setting_name);
                                }

                                Vue.set(self.facet, setting_name, val);
                            }
                        });

                        // Update the documentFragment HTML to include the "v-model"
                        $.each($parsed.nodes[0].children, function(el) {
                            html += el.outerHTML;
                        });
                    });

                    return html;
                }
            },
            watch: {
                'facet.type': function(val,oldVal) {
                    if ('search' == val || 'pager' == val || 'reset' == val || 'sort' == val) {
                        Vue.delete(this.facet, 'source');
                    }
                    if ( val !== oldVal && 'undefined' != typeof this.facet.ui_type ) {
                        // reset ui_type when changing facet type
                        Vue.delete(this.facet, 'ui_type');
                    }
                    if ('map' == val) {
                        this.$nextTick(() => {

                            // Init template code editor
                            this.initCodeMirror('marker-content-editor', 'marker_content', true);
                        });
                    }
                },
            }
        });

        Vue.component('data-sources', {
            props: {
                facet: Object,
                settingName: {
                    type: String,
                    default: 'source'
                }
            },
            template: `
            <select :class="className" v-model="facet[settingName]">
                <option v-if="settingName != 'source'" value="">{{ 'None' | i18n }}</option>
                <optgroup v-for="optgroup in FWP.data_sources" :label="optgroup.label">
                    <option v-for="(label, key) in optgroup.choices" :value="key" :selected="facet[settingName] == key">{{ label }}</option>
                </optgroup>
            </select>
            `,
            computed: {
                className() {
                    return 'facet-' + this.settingName.replace(/_/g, '-');
                }
            },
            mounted() {
                fSelect(this.$el);
            }
        });

        Vue.component('facet-names', {
            props: {
                facet: Object,
                setting: String
            },
            template: `
            <select :class="className" v-model="facet[setting]" multiple>
                <template v-for="(f) in FWP.data.facets">
                    <option v-if="!['reset'].includes(f.type)" :value="f.name" :class="bindSelectedClass(f.name)">{{ f.label }}</option>
                </template>
            </select>
            `,
            computed: {
                className() {
                    return 'facet-' + this.setting.replace(/_/g, '-');
                }
            },
            methods: {
                bindSelectedClass(name) {
                    return this.facet[this.setting].includes(name) ? 'selected' : '';
                }
            },
            created() {
                if ('undefined' === typeof this.facet[this.setting]) {
                    this.facet[this.setting] = [];
                }
            },
            mounted() {
                fSelect(this.$el, { 'placeholder': 'Choose facets' });
            }
        });

        Vue.component('sort-options', {
            props: {
                facet: Object
            },
            template: `
            <div class="qb-wrap">
                <div v-for="(rowOuter, indexOuter) in facet.sort_options" class="qb-row">
                    <div>
                        <input
                            type="text"
                            v-model="rowOuter.label"
                            @focus="$root.isNameEditable(rowOuter)"
                            @keyup="$root.maybeEditName(rowOuter)"
                        />
                        <code class="item-name" contenteditable v-text="rowOuter.name" @blur="setName(rowOuter, $event)" @keydown.enter.prevent autocorrect="off"></code>

                        <div v-for="(row, index) in rowOuter.orderby" class="qb-order-row">
                            <fselect :row="row">
                                <optgroup label="Posts">
                                    <option value="ID">ID</option>
                                    <option value="title">{{ 'Post Title' | i18n }}</option>
                                    <option value="name">{{ 'Post Name' | i18n }}</option>
                                    <option value="type">{{ 'Post Type' | i18n }}</option>
                                    <option value="date">{{ 'Post Date' | i18n }}</option>
                                    <option value="modified">{{ 'Post Modified' | i18n }}</option>
                                    <option value="comment_count">{{ 'Comment Count' | i18n }}</option>
                                    <option value="menu_order">{{ 'Menu Order' | i18n }}</option>
                                    <option value="post__in">post__in</option>
                                </optgroup>
                                <optgroup label="Custom Fields">
                                    <option v-for="(label, name) in FWP.data_sources.custom_fields.choices" :value="name">{{ label }}</option>
                                </optgroup>
                            </fselect>
                            <select v-model="row.type" v-show="row.key.substr(0, 3) == 'cf/'" class="qb-type">
                                <option value="CHAR">TEXT</option>
                                <option value="NUMERIC">NUMERIC</option>
                            </select>
                            <select v-model="row.order" class="qb-order">
                                <option value="ASC">ASC</option>
                                <option value="DESC">DESC</option>
                            </select>
                            <span @click="addSortField(rowOuter.orderby, index)" class="qb-add" v-html="FWP.svg['plus-circle']"></span>
                            <span @click="removeItem(rowOuter.orderby, index)" class="qb-remove" v-html="FWP.svg['minus-circle']" v-show="rowOuter.orderby.length > 1"></span>
                        </div>
                    </div>
                    <div class="align-right">
                        <span @click="moveUp(facet.sort_options, indexOuter)" class="qb-move" v-html="FWP.svg['arrow-circle-up']" v-show="indexOuter > 0"></span>
                        <span @click="removeItem(facet.sort_options, indexOuter)" class="qb-remove" v-html="FWP.svg['minus-circle']"></span>
                    </div>
                </div>

                <div>
                    <span class="facetwp-btn" @click="addSort">{{ 'Add sort' | i18n }}</span>
                </div>
            </div>
            `,
            methods: {
                addSort() {
                    this.facet.sort_options.push({
                        label: 'New option',
                        name: 'new_option',
                        orderby: [{
                            key: 'title',
                            order: 'ASC',
                            type: 'CHAR'
                        }]
                    });
                },
                addSortField(opts, index) {
                    opts.splice(index + 1, 0, {
                        key: 'title',
                        order: 'ASC',
                        type: 'CHAR'
                    });
                },
                moveUp(opts, index) {
                    opts.splice(index -1, 0, opts.splice(index, 1)[0]);
                },
                removeItem(row, index) {
                    Vue.delete(row, index);
                },
                setName(row, e) {
                    row.name = this.$root.sanitizeName(e.target.innerHTML);
                }
            }
        });

        Vue.component('color-picker', {
            props: {
                facet: Object,
                settingName: {
                    type: String,
                    default: 'color'
                },
                defaultColor: {
                    type: String,
                    default: '#000'
                },
            },
            template: `
            <div class="color-wrap">
                <div class="color-canvas">
                    <span class="color-preview"></span>
                    <input type="text" :class="className" v-model="facet[settingName]" :placeholder="colorName" />
                </div>
                <span class="color-clear">X</span>
            </div>`,
            created() {
                if ('undefined' === typeof this.facet[this.settingName]) {
                    this.facet[this.settingName] = this.defaultColor;
                }
            },
            computed: {
                className() {
                    return 'facet-' + this.settingName.replace(/_/g, '-') + ' color-input';
                },
                colorName() {
                    return this.defaultColor;
                }
            },
            mounted() {
                let self = this;
                let $canvas = self.$el.getElementsByClassName('color-canvas')[0];
                let $preview = self.$el.getElementsByClassName('color-preview')[0];
                let $input = self.$el.getElementsByClassName('color-input')[0];
                let $clear = self.$el.getElementsByClassName('color-clear')[0];
                $preview.style.backgroundColor = $input.value;

                let picker = new Picker({
                    parent: $canvas,
                    popup: 'right',
                    alpha: false,
                    onDone(color) {
                        let hex = color.hex().substr(0, 7);
                        self.facet[self.settingName] = hex;
                        $input.value = hex;
                        $preview.style.backgroundColor = hex;
                    }
                });

                picker.onOpen = function(color) {
                    picker.setColor($input.value);
                };

                $clear.addEventListener('click', function() {
                    self.facet[self.settingName] = self.defaultColor;
                    $input.value = self.defaultColor;
                    $preview.style.backgroundColor = $input.value;
                });
            }
        });

        // Vue instance
        FWP.vue = new Vue({
            el: '#app',
            data: {
                app: FWP.data,
                editing: {},
                editing_facet: false,
                editing_template: false,
                row_counts: {},
                active_tab: 'facets',
                active_subnav: 'general',
                is_support_loaded: false,
                is_name_editable: false,
                is_rebuild_open: false,
                is_indexing: false,
                timeout: null
            },
            methods: {
                addItem(type) {
                    if ('facet' == type) {
                        let len = this.app.facets.push({
                            'name': 'new_facet',
                            'label': 'New Facet',
                            'type': 'checkboxes',
                            'source': 'post_type'
                        });
                        this.editItem('facet', this.app.facets[len-1]);
                    }
                    else {
                        let len = this.app.templates.push({
                            'name': 'new_template',
                            'label': 'New Template',
                            'query': '',
                            'template': ''
                        });
                        this.editItem('template', this.app.templates[len-1]);
                    }
                },
                duplicateItem(type, index) {
                    let facet = this.cloneObj(this.app[type + 's'][index]);
                    facet.label += ' (copy)';
                    facet.name += '_copy';

                    this.app[type + 's'].splice(index+1, 0, facet)
                    this.editItem(type, facet);
                },
                editItem(type, data) {
                    this['editing_' + type] = true;
                    this.editing = data;
                    window.scrollTo(0, 0);
                },
                doneEditing() {
                    this.editing_template = false;
                    this.editing_facet = false;
                    this.editing = {};
                },
                tabClick(which) {
                    this.doneEditing();
                    this.active_tab = which;
                    if ('support' === which) {
                        this.is_support_loaded = true;
                    }
                    const url = new URL(location);
                    url.searchParams.set("tab", which);
                    history.pushState({}, "", url);
                },
                getItemLabel() {
                    return this.editing.label;
                },
                deleteItem(type, index) {
                    this.app[type + 's'].splice(index, 1);
                },
                saveChanges() {
                    window.setStatus('load', FWP.__('Saving') + '...');

                    let data = JSON.parse(JSON.stringify(FWP.data));

                    // Remove code-based facets and templates
                    data.facets = data.facets.filter(obj => 'undefined' === typeof obj['_code']);
                    data.templates = data.templates.filter(obj => 'undefined' === typeof obj['_code']);

                    // Settings save hook
                    data = FWP.hooks.applyFilters('facetwp/save_settings', {
                        action: 'facetwp_save_settings',
                        nonce: FWP.nonce,
                        data: data
                    });

                    $.post(ajaxurl, data, {
                        done: ({code, message}) => {
                            var code = ('success' == code) ? 'ok' : code;
                            window.setStatus(code, message);
                        },
                        fail: (err) => {
                            window.setStatus('error', err);
                        }
                    });
                },
                rebuildAction() {
                    this.is_indexing ? this.cancelReindex() : this.rebuildIndex();
                },
                rebuildIndex() {
                    let self = this;

                    if (this.is_indexing) {
                        return;
                    }

                    this.is_indexing = true;

                    $.post(ajaxurl, { action: 'facetwp_rebuild_index', nonce: FWP.nonce });
                    window.setStatus('load', FWP.__('Indexing') + '... 0%');
                    this.timeout = setTimeout(() => {
                        self.getProgress();
                    }, 5000);
                },
                cancelReindex() {
                    let self = this;

                    $.post(ajaxurl, {
                        action: 'facetwp_get_info',
                        type: 'cancel_reindex',
                        nonce: FWP.nonce
                    }, {
                        done: ({message}) => {
                            self.is_indexing = false;
                            clearTimeout(self.timeout);
                            window.setStatus('error', message);
                        }
                    });
                },
                getProgress() {
                    let self = this;
                    let isNumeric = (obj) => !Array.isArray(obj) && (obj - parseFloat(obj) + 1) >= 0;

                    $.post(ajaxurl, {
                        action: 'facetwp_heartbeat',
                        nonce: FWP.nonce
                    }, {
                        done: (data) => {
                            if ('-1' == data.pct) {
                                self.is_indexing = false;

                                if (data.rows.length < 1) {
                                    window.setStatus('error', FWP.__('The index table is empty'));
                                }
                                else {
                                    window.setStatus('ok', FWP.__('Indexing complete'));

                                    // Update the row counts
                                    $.each(self.$root.app.facets, function(facet) {
                                        Vue.set(self.row_counts, facet.name, data.rows[facet.name]);
                                    });
                                }
                            }
                            else if (isNumeric(data.pct)) {
                                window.setStatus('load', FWP.__('Indexing') + '... ' + data.pct + '%');
                                self.is_indexing = true;

                                self.timeout = setTimeout(() => {
                                    self.getProgress();
                                }, 5000);
                            }
                            else {
                                window.setStatus('error', data);
                                self.is_indexing = false;
                            }
                        }
                    });
                },
                getInfo(type, label) {
                    window.setStatus('load', FWP.__(label) + '...');

                    $.post(ajaxurl, {
                        action: 'facetwp_get_info',
                        type,
                        nonce: FWP.nonce
                    }, {
                        done: ({message}) => {
                            window.setStatus('error', message);
                        }
                    });
                },
                getQueryArgs(template) {
                    let self = this;

                    template.modes.query = 'advanced';
                    template.query = FWP.__('Loading') + '...';

                    $.post(ajaxurl, {
                        action: 'facetwp_get_query_args',
                        query_obj: template.query_obj,
                        nonce: FWP.nonce
                    }, {
                        done: (message) => {
                            var json = JSON.stringify(message, null, 2);
                            json = "<?php\nreturn " + json + ';';
                            json = json.replace(/[\{]/g, '[');
                            json = json.replace(/[\}]/g, ']');
                            json = json.replace(/":/g, '" =>');
                            template.query = json;

                            // Emit an event when template.query is changed to trigger updating the editor
                            this.$root.$emit('query-updated');
                        }
                    })
                },
                showIndexerStats() {
                    this.getInfo('indexer_stats', 'Looking');
                },
                searchablePostTypes() {
                    this.getInfo('post_types', 'Looking');
                },
                purgeIndexTable() {
                    this.getInfo('purge_index_table', 'Purging');
                },
                copyToClipboard(name, type, {target}) {
                    const $this = $(target);
                    const $el = $('.facetwp-clipboard');
                    const orig_text = $this.text();

                    try {
                        $el.removeClass('hidden');
                        $el.val('[facetwp ' + type + '="' + name + '"]');
                        $el.nodes[0].select();
                        document.execCommand('copy');
                        $el.addClass('hidden');
                        $this.text(FWP.__('Copied!'));
                    }
                    catch(err) {
                        $this.text(FWP.__('Press CTRL+C to copy'));
                    }

                    window.setTimeout(() => {
                        $this.text(orig_text);
                    }, 2000);
                },
                activate() {
                    $('.facetwp-activation-status').html(FWP.__('Activating') + '...');

                    $.post(ajaxurl, {
                        action: 'facetwp_license',
                        nonce: FWP.nonce,
                        license: $('.facetwp-license').val()
                    }, {
                        done: ({message}) => {
                            $('.facetwp-activation-status').html(message);
                        }
                    })
                },
                
                checkApi(testTypes, apikey) {

                    const statuses = {};
                    const doTests = testTypes.split(",");

                    const updateStatus = ( test, message, status = 'none', action = 'none' ) => {
                        const testEl = document.querySelector('.facetwp-api-status .test-' + test);
                        testEl.innerHTML = message;
                        testEl.classList = '';
                        testEl.classList.add( 'test-'+test );
                        testEl.classList.add( 'status-'+status);
                        testEl.classList.add( 'action-'+action);
                        statuses[test] = status;
                    }
                    
                    if (!/AIza[0-9A-Za-z\-_]{35}/.test(apikey)) {
                        alert( FWP.__('Your Google Maps API key format is invalid. Please check your key before continuing.') );
                        return false; // Invalid format
                    }

                    const prompt = FWP.__('Each test will count as one billable API request in your Google Cloud account. Continue?');
                    if (confirm(prompt)) {
                        updateStatus( 'status', FWP.__('Testing ... '), 'testing', 'testing' );
                    } else {
                        return;
                    }

                    const testFunctions = {};

                    testFunctions.checkPlaces = async () => {
                        updateStatus( 'places', FWP.__('Checking Places API (New) ...'), 'testing', 'testing' );                        
                        const placeUrl = `https://places.googleapis.com/v1/places/ChIJj61dQgK6j4AR4GeTYWZsKWw?fields=id,displayName,formattedAddress&key=${apikey}`;

                        try {
                            const response = await fetch(placeUrl);
                            if (!response.ok) {
                                if ('403' == response.status) {
                                    throw new Error('permission denied');
                                } else {
                                    throw new Error('unknown error');
                                }
                            } else {
                                updateStatus('places', '<span>' + FWP.__('Places API (New)') + ' </span> – <span class="status">' + FWP.__('connected ') + '</span>', 'pass', 'testing');
                            }
                        } catch (error) {
                            updateStatus('places', '<span>' + FWP.__('Places API (New)') + ' </span> – <span class="status">' + FWP.__('connection failed: ') + error.message + '</span>', 'fail', 'testing');
                        }
                    }
                    
                    testFunctions.checkPlaceslegacy = async () => {
                        updateStatus( 'placeslegacy', FWP.__('Checking Places API (Legacy) ...'), 'testing', 'testing' );                        
                        
                        this.loadGmaps(apikey);

                        const {PlacesService} = await google.maps.importLibrary("places");

                        const service = new PlacesService(
                            document.createElement('div')
                        );

                        service.getDetails({
                            placeId: 'ChIJj61dQgK6j4AR4GeTYWZsKWw',
                            fields: ['geometry']
                        }, function(place, status) {
                            if (status === google.maps.places.PlacesServiceStatus.OK) {
                                updateStatus('placeslegacy', '<span>' + FWP.__('Places API (Legacy)') + ' </span> – <span class="status">' + FWP.__('connected ') + '</span>', 'pass', 'testing');
                            } else {
                                updateStatus('placeslegacy', '<span>' + FWP.__('Places API (Legacy)') + ' </span> – <span class="status">' + FWP.__('connection failed: ') + status + '</span>', 'fail', 'testing');
                            }
                        });
                    }

                    testFunctions.checkGeocoder = async () => {                        
                        updateStatus( 'geocoder', FWP.__('Checking Geocoding API ...'), 'testing', 'testing' ); 

                        this.loadGmaps(apikey);

                        const {Geocoder} = await google.maps.importLibrary("geocoding");                        
                        const latlng = { lat: 40.714224, lng: -73.961452 };
                        const geocoding = new Geocoder;

                        geocoding.geocode({ 'location': latlng }, function(results, status) {
                            if (status === google.maps.GeocoderStatus.OK) {
                                updateStatus('geocoder', '<span>' + FWP.__('Geocoding API') + ' </span> – <span class="status">' + FWP.__('connected ') + '</span>', 'pass', 'testing');
                            } else {
                                updateStatus('geocoder', '<span>' + FWP.__('Geocoding API') + ' </span> – <span class="status">' + FWP.__('connection failed: ') + status + '</span>', 'fail', 'testing');
                            }
                        });
                    }

                    testFunctions.checkMaps = async () => {

                        updateStatus( 'maps', FWP.__('Checking Maps JavaScript API ...'), 'testing', 'testing' );    

                        this.loadGmaps(apikey);

                        const { Map } = await google.maps.importLibrary("maps");

                        const mapDiv = document.createElement("div");
                        mapDiv.id = "map";
                        mapDiv.style.width = "1px";
                        mapDiv.style.height = "1px";
                        document.getElementsByTagName('body')[0].appendChild(mapDiv);
                        const map = new Map(document.getElementById("map"), {
                            center: { lat: -34.397, lng: 150.644 },
                            zoom: 8,
                        });
                        map.addListener('tilesloaded', function() {
                            updateStatus( 'maps', '<span>' + FWP.__('Maps JavaScript API') + ' </span> – <span class="status">' + FWP.__('connected') + '</span>', 'pass', 'testing' );
                        });

                    }

                    const validTests = [ 'maps', 'places', 'placeslegacy', 'geocoder' ];
                    validTests.forEach((t) => {
                        if ( doTests.includes(t) ) {
                            let tn = "check"+t.charAt(0).toUpperCase()+t.slice(1);
                            testFunctions[tn]();
                        } 
                    });
                                

                    setTimeout(() => {
                        updateStatus( 'status', FWP.__('Test results:'), 'done', 'testing' );
                        'testing' !== statuses.maps || updateStatus( 'maps', '<span>' + FWP.__('Maps JavaScript API') + ' </span> – <span class="status">' + FWP.__('connection failed') + '</span>', 'fail', 'testing' );
                        'testing' !== statuses.geocoder || updateStatus( 'geocoder', '<span>' + FWP.__('Geocoding API') + ' </span> – <span class="status">' + FWP.__('connection failed') + '</span>', 'fail', 'testing' );
                        'testing' !== statuses.places || updateStatus( 'places', '<span>' + FWP.__('Places API (New)') + ' </span> – <span class="status">' + FWP.__('connection failed') + '</span>', 'fail', 'testing' );
                        'testing' !== statuses.placeslegacy || updateStatus( 'placeslegacy', '<span>' + FWP.__('Places API (Legacy)') + ' </span> – <span class="status">' + FWP.__('connection failed') + '</span>', 'fail', 'testing' );

                    }, "2000");

                },
                loadGmaps(apikey) {                   
                    /** we can only load the maps scripts once without reloading the page */
                    if ( !this.$root.gmapsLoaded ) {
                        let scriptTag = document.createElement('script');
                        scriptTag.id = "fwp-gmaps-api";
                        scriptTag.innerText = '(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=\`https://maps.${c}apis.com/maps/api/js?\`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({ key: "'+apikey+'", v: "quarterly", });';
                        document.getElementsByTagName('head')[0].appendChild(scriptTag);
                        this.$root.gmapsLoaded = true;
                    }
                },
                resetCheckApi() {
                    let prompt = FWP.__('FacetWP\'s settings page needs to reload to test a different Google Maps API key. Unsaved changes to other settings will be discarded. Continue?');
                    if (confirm(prompt)) {
                        window.location.reload();
                    } else {
                        return;
                    }
                },
                isNameEditable({name}) {
                    this.is_name_editable = ('' == name || 'new_' == name.substr(0, 4));
                },
                maybeEditName(item) {
                    if (this.is_name_editable) {
                        item.name = this.sanitizeName(item.label);
                    }
                },
                sanitizeName(name) {
                    let val = name.trim().toLowerCase();
                    let res = [ 'pager', 'sort', 'labels', 'length', 'name', 'method', 'num_choices' ];
                    val = val.replace(/[^\w- ]/g, ''); // strip invalid characters
                    val = val.replace(/[- ]/g, '_'); // replace space and hyphen with underscore
                    val = val.replace(/[_]{2,}/g, '_'); // strip consecutive underscores
                    val = res.includes(val) ? val + '_' : val; // reserved
                    return val;
                },
                documentClick({target}) {
                    let el = target;

                    if (! el.classList.contains('btn-caret')) {
                        this.is_rebuild_open = false;
                    }
                },
                cloneObj(obj) {
                    return JSON.parse(JSON.stringify(obj));
                }
            },
            computed: {
                isEditing() {
                    return this.editing_facet || this.editing_template;
                },
                indexButtonLabel() {
                    return this.is_indexing ? FWP.__('Stop indexer') : FWP.__('Re-index');
                }
            },
            created() {
                document.addEventListener('click', this.documentClick);
            },
            mounted() {
                let params = new URLSearchParams(document.location.search);
                let tab = params.get("tab");
                null == tab || !['facets', 'templates', 'settings', 'support'].includes(tab) || this.tabClick(tab);
                this.getProgress();
            },
            watch: {
                'app.settings.gmaps_api_key': function(oldVal,newVal) {
                    if ( this.$root.gmapsLoaded && oldVal != newVal && '' != newVal) {
                        this.$root.gmapsChanged = true;
                    }
                }
            }
        });
    }

    function init_custom_js() {

        window.setStatus = (code, message) => {
            $('.facetwp-response').html(message);
            $('.facetwp-response-icon').nodes[0].setAttribute('data-status', code);

            if ('error' == code) {
                $('.facetwp-response').addClass('visible');
            }
        };

        $().on('click', '.facetwp-settings-section .facetwp-switch', () => {
            window.setStatus('error', 'Press "Save changes" to apply');
        });

        $().on('click', '.facetwp-response-wrap', () => {
            $('.facetwp-response').toggleClass('visible');
        });

        $().on('click', '.export-all', (e) => {
            let $opts = $('.export-items').next('.fs-wrap');
            let selectedType = "undefined" != typeof e.target.dataset.value ? e.target.dataset.value+"-" : "";
            $opts.find('.fs-option:not(.selected)').each(function() {
                let $opt = $(this);
                if ( $opt.attr('data-value').includes(selectedType) ) {
                    $opt.trigger('click');
                }
            });
            $opts.find('.fs-option.selected').each(function() {
                let $opt = $(this);
                if ( !$opt.attr('data-value').includes(selectedType) ) {
                    $opt.trigger('click');
                }
            });
            $opts.nodes[0]._rel.fselect.open();
            e.stopImmediatePropagation();
        });

        $().on('click', '.export-none', (e) => {
            let $opts = $('.export-items').next('.fs-wrap');
            $opts.find('.fs-option.selected').each(function() {
                let $opt = $(this);
                $opt.trigger('click');
            });
            $opts.nodes[0]._rel.fselect.open();
            e.stopImmediatePropagation();
        });

        // Export
        $().on('click', '.export-submit', () => {
            $('.import-code').val(FWP.__('Loading') + '...');

            $.post(ajaxurl, {
                action: 'facetwp_backup',
                nonce: FWP.nonce,
                action_type: 'export',
                items: $('.export-items').val()
            }, {
                done: (resp) => {
                    $('.import-code').val(JSON.stringify(resp));
                }
            })
        });

        // Import
        $().on('click', '.import-submit', () => {
            window.setStatus('load', FWP.__('Importing') + '...');

            try {
                var code = JSON.parse($('.import-code').val());

                $.post(ajaxurl, {
                    action: 'facetwp_backup',
                    nonce: FWP.nonce,
                    action_type: 'import',
                    import_code: code,
                    overwrite: $('.import-overwrite').nodes[0].checked ? 1 : 0
                }, {
                    dataType: 'text',
                    done: (resp) => {
                        window.setStatus('ok', resp);
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                });
            }
            catch(err) {
                window.setStatus('error', 'Invalid JSON');
            }
        });

        // Initialize tooltips
        $().on('mouseover', '.facetwp-tooltip', function() {
            if (!this.classList.contains('.ftip-enabled')) {
                fTip(this, {
                    content: (node) => $(node).find('.facetwp-tooltip-content').html()
                }).open();
            }
        });

        // fSelect
        fSelect('.export-items');
    }

})(fUtil);
