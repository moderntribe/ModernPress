window.FWP = (($) => {

    class FacetWP {
        constructor() {
            this.import();
            this.bindEvents();
        }

        import() {
            if ('undefined' !== typeof FWP) {
                $.each(FWP, (val, key) => this[key] = val);
            }
        }

        init() {
            var FWP = this;

            this.setDefaults();

            if (0 < $('.facetwp-sort').len()) {
                FWP.extras.sort = 'default';
            }

            if (0 < $('.facetwp-pager').len()) {
                FWP.extras.pager = true;
            }

            if (0 < $('.facetwp-per-page').len()) {
                FWP.extras.per_page = 'default';
            }

            if (0 < $('.facetwp-counts').len()) {
                FWP.extras.counts = true;
            }

            if (0 < $('.facetwp-selections').len()) {
                FWP.extras.selections = true;
            }

            // Make sure there's a template
            var has_template = $('.facetwp-template').len() > 0;

            if (! has_template) {
                var has_loop = FWP.helper.detectLoop(document.body);

                if (has_loop) {
                    $(has_loop).addClass('facetwp-template');
                }
                else {
                    console.error('FacetWP has not detected a listing template');
                    return;
                }
            }

            var $div = $('.facetwp-template').first();
            FWP.template = $div.attr('data-name') ? $div.attr('data-name') : 'wp';

            // Facets inside the template?
            if ($div.find('.facetwp-facet').len() > 0) {
                console.error('Facets should not be inside the "facetwp-template" container');
            }

            FWP.hooks.doAction('facetwp/ready');

            // Generate the user selections
            if (FWP.extras.selections) {
                FWP.hooks.addAction('facetwp/loaded', () => {

                    var selections = '';
                    var skipped = ['pager', 'reset', 'sort'];
                    var selected_facets = ! FWP.loaded && ! FWP.is_bfcache && $.isset(FWP_HTTP.url_vars) ? Object.assign(FWP.facets, FWP_HTTP.url_vars): FWP.facets;

                    $.each(selected_facets, (val, key) => {
                        if (val.length < 1 || ! $.isset(FWP.settings.labels[key]) || skipped.includes(FWP.facet_type[key])) {
                            return true; // skip facet
                        }

                        var choices = val;
                        var $el = $('.facetwp-facet-' + key);
                        var facet_type = $el.attr('data-ui') || $el.attr('data-type');
                        choices = FWP.hooks.applyFilters('facetwp/selections/' + facet_type, choices, {
                            'el': $el,
                            'selected_values': choices
                        });

                        if (choices.length) {
                            if ('string' === typeof choices) {
                                choices = [{ value: '', label: choices }];
                            }
                            else if (! $.isset(choices[0].label)) {
                                choices = [{ value: '', label: choices[0] }];
                            }
                        }

                        var values = '';
                        $.each(choices, (choice) => {
                            values += '<span class="facetwp-selection-value" data-value="' + choice.value + '">' + FWP.helper.escapeHtml(choice.label) + '</span>';
                        });

                        if ('' !== values) {
                            selections += '<li data-facet="' + key + '"><span class="facetwp-selection-label">' + FWP.settings.labels[key] + ':</span> ' + values + '</li>';
                        }
                    });

                    if ('' !== selections) {
                        selections = '<ul>' + selections + '</ul>';
                    }

                    $('.facetwp-selections').html(selections);
                });
            }

            FWP.refresh();
        }

        setDefaults() {
            let defaults = {
                'facets': {},
                'template': null,
                'settings': {},
                'is_reset': false,
                'is_refresh': false,
                'is_bfcache': false,
                'is_hash_click': false,
                'is_load_more': false,
                'auto_refresh': true,
                'soft_refresh': false,
                'frozen_facets': {},
                'active_facet': null,
                'facet_type': {},
                'loaded': false,
                'extras': {},
                'paged': 1
            };

            for (var prop in defaults) {
                if (!$.isset(this[prop])) {
                    this[prop] = defaults[prop];
                }
            }
        }

        refresh() {
            FWP.is_refresh = true;

            // Add the loading overlay
            FWP.toggleOverlay('on');

            // Load facet DOM values
            if (! FWP.is_reset) {
                FWP.parseFacets();
            }

            // Check the URL on pageload
            if (! FWP.loaded) {
                FWP.loadFromHash();
            }

            // Fire a notification event
            $().trigger('facetwp-refresh');

            // Trigger window.onpopstate
            if (FWP.loaded && ! FWP.is_popstate && ! FWP.is_load_more) {
                FWP.setHash();
            }

            // Preload?
            if (! FWP.loaded && ! FWP.is_bfcache && $.isset(FWP_JSON.preload_data)) {
                FWP.render(FWP_JSON.preload_data);
            }
            else {
                FWP.fetchData();
            }

            // Unfreeze any soft-frozen facets
            $.each(FWP.frozen_facets, (type, name) => {
                if ('hard' !== type) {
                    delete FWP.frozen_facets[name];
                }
            });

            // Cleanup
            FWP.paged = 1;
            FWP.soft_refresh = false;
            FWP.is_refresh = false;
            FWP.is_reset = false;
        }

        autoload() {
            if (FWP.auto_refresh && ! FWP.is_refresh) {
                FWP.refresh();
            }
        }

        parseFacets() {
            FWP.facets = {};

            $('.facetwp-facet').each(function() {
                var $this = $(this);
                var facet_name = $this.attr('data-name');
                var facet_type = $this.attr('data-type');
                var is_ignored = $this.hasClass('facetwp-ignore');

                if (null !== $this.attr('data-ui')) {
                    facet_type = $this.attr('data-ui');
                }

                // Store the facet type
                FWP.facet_type[facet_name] = facet_type;

                // Plugin hook
                if (! is_ignored) {
                    FWP.hooks.doAction('facetwp/refresh/' + facet_type, $this, facet_name);
                }
            });
        }

        buildQueryString() {
            var query_string = '';

            // Non-FacetWP URL variables
            var hash = [];
            var get_str = window.location.search.replace('?', '').split('&');
            $.each(get_str, (val) => {
                var param_name = val.split('=')[0];
                if (0 !== param_name.indexOf(FWP_JSON.prefix)) {
                    hash.push(val);
                }
            });
            hash = hash.join('&');

            // FacetWP URL variables
            var fwp_vars = Object.assign({}, FWP.facets);

            // Add pagination to the URL hash
            if (1 < FWP.paged) {
                fwp_vars['paged'] = FWP.paged;
            }

            // Add "per page" to the URL hash
            if (FWP.extras.per_page && 'default' !== FWP.extras.per_page) {
                fwp_vars['per_page'] = FWP.extras.per_page;
            }

            // Add sorting to the URL hash
            if (FWP.extras.sort && 'default' !== FWP.extras.sort) {
                fwp_vars['sort'] = FWP.extras.sort;
            }

            fwp_vars = FWP.helper.serialize(fwp_vars, FWP_JSON.prefix);

            if ('' !== hash) {
                query_string += hash;
            }
            if ('' !== fwp_vars) {
                query_string += ('' !== hash ? '&' : '') + fwp_vars;
            }

            return query_string;
        }

        setHash() {
            var query_string = FWP.buildQueryString();

            if ('' !== query_string) {
                query_string = '?' + query_string;
            }

            if (history.pushState) {
                history.pushState(null, null, window.location.pathname + query_string);
            }

            // Update FWP_HTTP.get
            FWP_HTTP.get = {};
            window.location.search.replace('?', '').split('&').forEach((el) => {
                var item = el.split('=');

                if ('' != item[0]) {
                    FWP_HTTP.get[item[0]] = item[1];
                }
            });
        }

        loadFromHash() {
            var hash = [];
            var get_str = window.location.search.replace('?', '').split('&');
            $.each(get_str, (val) => {
                var param_name = val.split('=')[0];
                if (0 === param_name.indexOf(FWP_JSON.prefix)) {
                    hash.push(val.replace(FWP_JSON.prefix, ''));
                }
            });
            hash = hash.join('&');

            // Reset facet values
            $.each(FWP.facets, (val, key) => {
                FWP.facets[key] = [];
            });

            FWP.paged = 1;
            FWP.extras.sort = 'default';

            if ('' !== hash) {
                hash = hash.split('&');
                $.each(hash, (chunk) => {
                    var obj = chunk.split('=')[0];
                    var val = chunk.split('=')[1];

                    if ('paged' === obj) {
                        FWP.paged = val;
                    }
                    else if ('per_page' === obj || 'sort' === obj) {
                        FWP.extras[obj] = val;
                    }
                    else if ('' !== val) {
                        var type = $.isset(FWP.facet_type[obj]) ? FWP.facet_type[obj] : '';
                        if ('search' === type || 'autocomplete' === type) {
                            FWP.facets[obj] = decodeURIComponent(val);
                        }
                        else {
                            FWP.facets[obj] = decodeURIComponent(val).split(',');
                        }
                    }
                });
            }
        }

        buildPostData() {
            return {
                'facets': FWP.facets,
                'frozen_facets': FWP.frozen_facets,
                'http_params': FWP_HTTP,
                'template': FWP.template,
                'extras': FWP.extras,
                'soft_refresh': FWP.soft_refresh ? 1 : 0,
                'is_bfcache': FWP.is_bfcache ? 1 : 0,
                'first_load': FWP.loaded ? 0 : 1,
                'paged': FWP.paged
            };
        }

        fetchData() {
            var endpoint = ('wp' === FWP.template) ? document.URL : FWP_JSON.ajaxurl;
            var data = {
                action: 'facetwp_refresh',
                data: FWP.buildPostData()
            };

            var settings = {
                dataType: 'text', // better JSON error handling
                done: (resp) => {
                    try {
                        var json = JSON.parse(resp);
                        FWP.render(json);
                    }
                    catch(e) {
                        var pos = resp.indexOf('{"facets');
                        if (-1 < pos) {
                            var json = JSON.parse(resp.substr(pos));
                            FWP.render(json);
                        }
                        else {
                            $('.facetwp-template').text('FacetWP was unable to auto-detect the post listing');
                            console.log(resp);
                        }
                    }
                },
                fail: (err) => {
                    console.log(err);
                }
            };

            settings = FWP.hooks.applyFilters('facetwp/ajax_settings', settings);

            $.post(endpoint, data, settings);
        }

        render(response) {
            FWP.response = response;

            // Don't render CSS-based (or empty) templates on pageload
            // The template has already been pre-loaded
            if (('wp' === FWP.template || '' === response.template) && ! FWP.loaded && ! FWP.is_bfcache) {
                var inject = false;
            }
            else {
                var inject = response.template;

                if ('wp' === FWP.template) {
                    var obj = $(response.template);
                    var $tpl = obj.find('.facetwp-template');

                    if ($tpl.len() < 1) {
                        var loop = FWP.helper.detectLoop(obj.nodes[0]);

                        if (loop) {
                            $tpl = $(loop).addClass('facetwp-template');
                        }
                    }

                    if ($tpl.len() > 0) {
                        var inject = $tpl.html();
                    }
                    else {
                        // Fallback until "loop_no_results" action is added to WP core
                        var inject = FWP_JSON['no_results_text'];
                    }
                }
            }

            if (false !== inject) {
                if (! FWP.hooks.applyFilters('facetwp/template_html', false, { 'response': response, 'html': inject })) {
                    $('.facetwp-template').html(inject);
                }
            }

            // Populate each facet box
            $.each(response.facets, (val, name) => {
                $('.facetwp-facet-' + name).html(val);
            });

            // Populate the counts
            if ($.isset(response.counts)) {
                $('.facetwp-counts').html(response.counts);
            }

            // Populate the pager
            if ($.isset(response.pager)) {
                $('.facetwp-pager').html(response.pager);
            }

            // Populate the "per page" box
            if ($.isset(response.per_page)) {
                $('.facetwp-per-page').html(response.per_page);
                if ('default' !== FWP.extras.per_page) {
                    $('.facetwp-per-page-select').val(FWP.extras.per_page);
                }
            }

            // Populate the sort box
            if ($.isset(response.sort)) {
                $('.facetwp-sort').html(response.sort);
                $('.facetwp-sort-select').val(FWP.extras.sort);
            }

            // Populate the settings object (iterate to preserve static facet settings)
            $.each(response.settings, (val, key) => {
                FWP.settings[key] = val;
            });

            // WP Playlist support
            if ('function' === typeof WPPlaylistView) {
                $('.facetwp-template .wp-playlist').each((item) => {
                    return new WPPlaylistView({ el: item });
                });
            }

            // Fire a notification event
            $().trigger('facetwp-loaded');

            // Allow final actions
            FWP.hooks.doAction('facetwp/loaded');

            // Remove the loading overlay
            FWP.toggleOverlay('off');

            // Clear the active facet
            FWP.active_facet = null;

            // Detect "back-forward" cache
            FWP.is_bfcache = true;

            // Done loading?
            FWP.loaded = true;
        }

        reset(facets) {
            FWP.parseFacets();

            var opts = {};

            if ('string' === typeof facets) {
                opts[facets] = '';
            }
            else if (Array.isArray(facets)) {
                $.each(facets, (facet_name) => {
                    opts[facet_name] = '';
                });
            }
            else if ('object' === typeof facets && !! facets) {
                opts = facets;
            }

            var reset_all = Object.keys(opts).length < 1;

            $.each(FWP.facets, (vals, facet_name) => {
                var has_reset = $.isset(opts[facet_name]);
                var selected_vals = Array.isArray(vals) ? vals : [vals];

                if (has_reset && -1 < selected_vals.indexOf(opts[facet_name])) {
                    var pos = selected_vals.indexOf(opts[facet_name]);
                    selected_vals.splice(pos, 1); // splice() is mutable!
                    FWP.facets[facet_name] = selected_vals;
                }

                if (has_reset && (selected_vals.length < 1 || '' === opts[facet_name])) {
                    delete FWP.frozen_facets[facet_name];
                }

                if (reset_all || (has_reset && '' === opts[facet_name])) {
                    FWP.facets[facet_name] = [];
                }
            });

            if (reset_all) {
                FWP.extras.per_page = 'default';
                FWP.extras.sort = 'default';
                FWP.frozen_facets = {};
            }

            FWP.hooks.doAction('facetwp/reset');
            FWP.is_reset = true;
            FWP.refresh();
        }

        toggleOverlay(which) {
            var method = ('on' === which) ? 'addClass' : 'removeClass';
            $('.facetwp-facet')[method]('is-loading');
        }

        bindEvents() {
            window.addEventListener('popstate', () => {

                // Detect browser "back-foward" cache
                if (FWP.is_bfcache) {
                    FWP.loaded = false;
                }

                if ((FWP.loaded || FWP.is_bfcache) && ! FWP.is_refresh && ! FWP.is_hash_click) {
                    FWP.is_popstate = true;
                    FWP.refresh();
                    FWP.is_popstate = false;
                }

                FWP.is_hash_click = false;
            });

            // Prevent hash clicks from triggering a refresh
            $().on('click', 'a[href^="#"]', () => {
                FWP.is_hash_click = true;
            });

            // Click on a user selection
            $().on('click', '.facetwp-selections .facetwp-selection-value', function() {
                if (FWP.is_refresh) {
                    return;
                }

                var facet_name = $(this).closest('li').attr('data-facet');
                var facet_value = $(this).attr('data-value');

                if ('' != facet_value) {
                    var obj = {};
                    obj[facet_name] = facet_value;
                    FWP.reset(obj);
                }
                else {
                    FWP.reset(facet_name);
                }
            });

            // Pagination
            $().on('click', '.facetwp-page[data-page]', function() {
                $('.facetwp-page').removeClass('active');
                $(this).addClass('active');

                FWP.paged = $(this).attr('data-page');
                FWP.soft_refresh = true;

                let facet_name = $(this).closest('.facetwp-type-pager').attr('data-name');
                FWP.scroll_target = ( 'string' == typeof facet_name) ? FWP.settings[facet_name].scroll_target : '';
                FWP.scroll_offset = ( '' != FWP.scroll_target && 'number' == typeof Number(FWP.settings[facet_name].scroll_offset) ) ? FWP.settings[facet_name].scroll_offset : 0;
                FWP.refresh();
            });

            FWP.hooks.addAction('facetwp/loaded', function() {
                try {
                    if ( !FWP.loaded && 1 < FWP.settings.pager.page ) {
                        let numbers_pager = Object.keys(FWP.settings).filter(key => FWP.settings[key].hasOwnProperty('scroll_target'));
                        let facet_name = numbers_pager[0];
                        FWP.scroll_target = ( 'string' == typeof facet_name) ? FWP.settings[facet_name].scroll_target : '';
                        FWP.scroll_offset = ( '' != FWP.scroll_target && 'number' == typeof Number(FWP.settings[facet_name].scroll_offset) ) ? FWP.settings[facet_name].scroll_offset : 0;
                    }
                    let target = document.querySelector(FWP.scroll_target);
                    let targetPos = target.getBoundingClientRect().top + Number(FWP.scroll_offset);
                    window.scrollBy({ top: targetPos, behavior: 'smooth' });
                }
                catch(e) {
                    // do nothing
                }
                FWP.scroll_target = ''; // remove scroll
                FWP.scroll_offset = ''; // remove scroll
            }, 100 );

            // Use jQuery if available for select2
            var $f = ('function' === typeof jQuery) ? jQuery : fUtil;

            // Per page
            $f(document).on('change', '.facetwp-per-page-select', function() {
                FWP.extras.per_page = $(this).val();
                FWP.soft_refresh = true;
                FWP.autoload();
            });

            // Sorting
            $f(document).on('change', '.facetwp-sort-select', function() {
                FWP.extras.sort = $(this).val();
                FWP.soft_refresh = true;
                FWP.autoload();
            });

            $f(() => {
                this.init();
            });
        }
    }

    FacetWP.prototype.helper = {
        getUrlVar: (name) => {
            var name = FWP_JSON.prefix + name;
            var url_vars = window.location.search.replace('?', '').split('&');
            for (var i = 0; i < url_vars.length; i++) {
                var item = url_vars[i].split('=');
                if (item[0] === name) {
                    return item[1];
                }
            }
            return false;
        },
        debounce: (func, wait) => {
            var timeout;
            return function(...args) {
                var boundFunc = func.bind(this, ...args);
                clearTimeout(timeout);
                timeout = setTimeout(boundFunc, wait);
            };
        },
        serialize: (obj, prefix) => {
            var str = [];
            var prefix = $.isset(prefix) ? prefix : '';
            for (var p in obj) {
                if ('' != obj[p]) { // Needs to be "!=" instead of "!=="
                    str.push(prefix + encodeURIComponent(p) + '=' + encodeURIComponent(obj[p]));
                }
            }
            return str.join('&');
        },
        escapeHtml: (text) => {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g,(m) => map[m]).trim();
        },
        detectLoop: (node) => {
            var curNode = null;
            var iterator = document.createNodeIterator(node, NodeFilter.SHOW_COMMENT, () => {
                return NodeFilter.FILTER_ACCEPT; /* IE expects a function */
            }, false);

            while (curNode = iterator.nextNode()) {
                if (8 === curNode.nodeType && 'fwp-loop' === curNode.nodeValue) {
                    return curNode.parentNode;
                }
            }

            return false;
        }
    };

    return new FacetWP();

})(fUtil);
