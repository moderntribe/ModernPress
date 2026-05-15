(function($) {

    FWP.logic = FWP.logic || {};

    /* ======== IE11 .val() fix ======== */

    function pVal(el) {
        let $input = $(el);
        return $input.val() === $input.attr('placeholder') ? '' : $input.val();
    }

    /* ======== Support duplicate facets ======== */

    $('.facetwp-facet').each(function() {

        // We need useCapture, so add the event listeners manually
        // useCapture handles outer elements first (unlike event bubbling)
        // use mousedown instead of click for better dropdown support
        this.addEventListener('mousedown', function() {
            var $items = $('.facetwp-facet-' + $(this).attr('data-name'));
            if (1 < $items.len()) {
                $items.addClass('facetwp-ignore');
                $(this).removeClass('facetwp-ignore');
            }
            FWP.active_facet = $(this);
        }, true);
    });

    /* ======== Autocomplete ======== */

    FWP.hooks.addAction('facetwp/refresh/autocomplete', function($this, facet_name) {
        var val = $this.find('.facetwp-autocomplete').val() || '';
        FWP.facets[facet_name] = val;
    });

    $().on('facetwp-loaded', function() {
        $('.facetwp-autocomplete:not(.fcomplete-enabled)').each(function() {
            var el = this;
            var $facet = $(el).closest('.facetwp-facet');
            var facet_name = $facet.attr('data-name');

            var endpoint = ('wp' === FWP.template) ? document.URL : FWP_JSON.ajaxurl;
            var options = FWP.settings[facet_name];
            options.data = () => {
                return $.post(endpoint, {
                    action: 'facetwp_autocomplete_load',
                    facet_name: facet_name,
                    query: el.value,
                    data: FWP.buildPostData()
                }, {
                    done: (resp) => {
                        this.fcomplete.render(resp);
                    }
                });
            };
            options.onSelect = () => FWP.autoload();

            fComplete(el, options);
        });
    });

    $().on('keyup', '.facetwp-autocomplete', function(e) {
        if (13 === e.which && ! FWP.is_refresh) {
            FWP.autoload();
        }
    });

    $().on('click', '.facetwp-autocomplete-update', function() {
        FWP.autoload();
    });

    /* ======== Checkboxes ======== */

    FWP.hooks.addAction('facetwp/refresh/checkboxes', function($this, facet_name) {
        var selected_values = [];
        $this.find('.facetwp-checkbox.checked').each(function() {
            selected_values.push(
                $(this).attr('data-value')
            );
        });
        FWP.facets[facet_name] = selected_values;
    });

    FWP.hooks.addFilter('facetwp/selections/checkboxes', function(output, params) {
        var choices = [];
        $.each(params.selected_values, function(val) {
            var $item = params.el.find('.facetwp-checkbox[data-value="' + val + '"]');
            if ($item.len()) {
                var choice = $($item.html());
                choice.find('.facetwp-counter').remove();
                choice.find('.facetwp-expand').remove();
                choices.push({
                    value: val,
                    label: choice.text()
                });
            }
        });
        return choices;
    });

    $().on('click', '.facetwp-type-checkboxes .facetwp-expand', function(e) {
        var $wrap = $(this).closest('.facetwp-checkbox').next('.facetwp-depth');
        $wrap.toggleClass('visible');
        var content = $wrap.hasClass('visible') ? FWP_JSON['collapse'] : FWP_JSON['expand'];
        $(this).html(content);
        e.stopImmediatePropagation();
    });

    $().on('click', '.facetwp-type-checkboxes .facetwp-checkbox:not(.disabled)', function() {
        var $cb = $(this);
        var is_checked = ! $cb.hasClass('checked');
        var is_child = $cb.closest('.facetwp-depth').len() > 0;
        var is_parent = $cb.next().hasClass('facetwp-depth');

        // if a parent is clicked, deselect all of its children
        if (is_parent) {
            $cb.next('.facetwp-depth').find('.facetwp-checkbox').removeClass('checked');
        }
        // if a child is clicked, deselects all of its parents
        if (is_child) {
            $cb.parents('.facetwp-depth').each(function() {
                $(this).prev('.facetwp-checkbox').removeClass('checked');
            });
        }

        $cb.toggleClass('checked', is_checked);
        FWP.autoload();
    });

    $().on('click', '.facetwp-type-checkboxes .facetwp-toggle', function() {
        var $parent = $(this).closest('.facetwp-facet');
        $parent.find('.facetwp-toggle').toggleClass('facetwp-hidden');
        $parent.find('.facetwp-overflow').toggleClass('facetwp-hidden');
    });

    $().on('facetwp-loaded', function() {
        $('.facetwp-type-checkboxes .facetwp-overflow').each(function() {
            var num = $(this).find('.facetwp-checkbox').len();
            var $el = $(this).next('.facetwp-toggle');
            $el.text($el.text().replace('{num}', num));

            // auto-expand if a checkbox within the overflow is checked
            if (0 < $(this).find('.facetwp-checkbox.checked').len()) {
                $el.trigger('click');
            }
        });

        // hierarchy expand / collapse buttons
        $('.facetwp-type-checkboxes').each(function() {
            var $facet = $(this);
            var name = $facet.attr('data-name');

            // error handling
            if (Object.keys(FWP.settings).length < 1) {
                return;
            }

            // expand children
            if ('yes' === FWP.settings[name]['show_expanded']) {
                $facet.find('.facetwp-depth').addClass('visible');
            }

            if (1 > $facet.find('.facetwp-expand').len()) {

                // expand groups with selected items
                $facet.find('.facetwp-checkbox.checked').each(function() {
                    $(this).parents('.facetwp-depth').addClass('visible');
                });

                // add the toggle button
                $facet.find('.facetwp-depth').each(function() {
                    var which = $(this).hasClass('visible') ? 'collapse' : 'expand';
                    $(this).prev('.facetwp-checkbox').append(' <span class="facetwp-expand">' + FWP_JSON[which] + '</span>');
                });
            }
        });
    });

    /* ======== Radio ======== */

    FWP.hooks.addAction('facetwp/refresh/radio', function($this, facet_name) {
        var selected_values = [];
        $this.find('.facetwp-radio.checked').each(function() {
            var val = $(this).attr('data-value');
            if ('' !== val) {
                selected_values.push(val);
            }
        });
        FWP.facets[facet_name] = selected_values;
    });

    FWP.hooks.addFilter('facetwp/selections/radio', function(output, params) {
        var choices = [];
        $.each(params.selected_values, function(val) {
            var $item = params.el.find('.facetwp-radio[data-value="' + val + '"]');
            if ($item.len()) {
                var choice = $($item.html());
                choice.find('.facetwp-counter').remove();
                choices.push({
                    value: val,
                    label: choice.text()
                });
            }
        });
        return choices;
    });

    $().on('click', '.facetwp-type-radio .facetwp-radio:not(.disabled)', function() {
        var is_checked = $(this).hasClass('checked');
        $(this).closest('.facetwp-facet').find('.facetwp-radio').removeClass('checked');
        if (! is_checked) {
            $(this).addClass('checked');
        }
        FWP.autoload();
    });

    /* ======== Date Range ======== */

    FWP.hooks.addAction('facetwp/refresh/date_range', function($this, facet_name) {
        var minNode = $this.find('.facetwp-date-min');
        var maxNode = $this.find('.facetwp-date-max');
        var min = (minNode.len()) ? pVal(minNode.nodes[0]) : '';
        var max = (maxNode.len()) ? pVal(maxNode.nodes[0]) : '';
        FWP.facets[facet_name] = ('' !== min || '' !== max) ? [min, max] : [];
    });

    FWP.hooks.addFilter('facetwp/selections/date_range', function(output, params) {
        var $el = params.el;
        var vals = params.selected_values;
        var facet_name = $el.attr('data-name');
        var fields = FWP.settings[facet_name].fields;
        var out = '';

        if ('exact' == fields) {
            if ('' !== vals[0]) {
                out = vals[0];
            }
        }
        else if ('start_date' == fields) {
            if ('' !== vals[0]) {
                out = '[>=] ' + vals[0];
            }
        }
        else if ('end_date' == fields) {
            if ('' !== vals[1]) {
                out = '[<=] ' + vals[1];
            }
        }
        else if ('both' == fields) {
            if ('' !== vals[0] || '' !== vals[1]) {
                if ('' !== vals[0] && '' !== vals[1]) {
                    out = vals[0] + ' - ' + vals[1];
                }
                else if ('' !== vals[0]) {
                    out = '[>=] ' + vals[0];
                }
                else if ('' !== vals[1]) {
                    out = '[<=] ' + vals[1];
                }
            }
        }

        return out;
    });

    $().on('facetwp-loaded', function() {
        var $dates = $('.facetwp-type-date_range .facetwp-date:not(.ready)');

        if (0 === $dates.len()) {
            return;
        }

        $dates.each(function() {
            var $this = $(this);
            var facet_name = $this.closest('.facetwp-facet').attr('data-name');
            var settings = FWP.settings[facet_name];
            var opts = {
                onChange: function(obj) {
                    FWP.autoload();
                }
            };

            if ('' !== settings.locale) {
                opts.i18n = settings.locale;
            }

            if ('' !== settings.format) {
                opts.altFormat = settings.format;
            }

            if ('both' == settings.fields) {
                var which = $this.hasClass('facetwp-date-min') ? 'min' : 'max';
                opts.minDate = settings.range[which].minDate;
                opts.maxDate = settings.range[which].maxDate;
            }
            else {
                opts.minDate = settings.range.minDate;
                opts.maxDate = settings.range.maxDate;
            }

            opts = FWP.hooks.applyFilters('facetwp/set_options/date_range', opts, {
                'facet_name': facet_name,
                'element': $this
            });

            $this.addClass('ready'); // add class before fDate()

            if (opts.minDate.length && opts.maxDate.length) {
                new fDate(this, opts);
            } else {
                this.value = '';
                let emptyText = $this.attr('data-empty');
                $this.attr('placeholder', emptyText).attr('disabled','disabled').addClass('disabled');
            }

        });
    });

    /* ======== Dropdown ======== */

    FWP.hooks.addAction('facetwp/refresh/dropdown', function($this, facet_name) {
        var val = $this.find('.facetwp-dropdown').val();
        FWP.facets[facet_name] = val ? [val] : [];
    });

    FWP.hooks.addFilter('facetwp/selections/dropdown', function(output, params) {
        var $item = params.el.find('.facetwp-dropdown');
        if ($item.len()) {
            var dd = $item.nodes[0];
            var text = dd.options[dd.selectedIndex].text;
            return text.replace(/\(\d+\)$/, '');
        }
        return '';
    });

    // Use jQuery if available for select2
    var $f = ('function' === typeof jQuery) ? jQuery : fUtil;

    $f(document).on('change', '.facetwp-type-dropdown select', function() {
        var $facet = $(this).closest('.facetwp-facet');
        var facet_name = $facet.attr('data-name');

        if ('' !== $(this).val()) {
            FWP.frozen_facets[facet_name] = 'soft';
        }
        FWP.autoload();
    });

    /* ======== fSelect ======== */

    FWP.hooks.addAction('facetwp/refresh/fselect', function($this, facet_name) {
        var val = $this.find('select').val();
        if (null === val || '' === val) {
            val = [];
        }
        FWP.facets[facet_name] = Array.isArray(val) ? val : [val];
    });

    FWP.hooks.addFilter('facetwp/selections/fselect', function(output, params) {
        var choices = [];
        $.each(params.selected_values, (val) => {
            var $item = params.el.find('option[value="' + val + '"]');
            if ($item.len()) {
                choices.push({
                    value: val,
                    label: $item.text()
                });
            }
        });
        return choices;
    });

    FWP.hooks.addAction('facetwp/loaded', function() {
        if (null !== FWP.active_facet) {
            var facet = FWP.active_facet;
            if ('fselect' == facet.attr('data-type')) {
                var input = facet.find('.facetwp-dropdown').nodes[0];
                if (input.fselect.settings.multiple) {
                    input.fselect.open();
                }
            }
        }
    });

    $().on('facetwp-loaded', function() {
        $('.facetwp-type-fselect select:not(.fs-hidden)').each(function() {
            var facet_name = $(this).closest('.facetwp-facet').attr('data-name');
            var settings = FWP.settings[facet_name];

            settings.optionFormatter = function(label, node) {
                var counter = node.getAttribute('data-counter');
                return (counter) ? label + ' (' + counter + ')' : label;
            };

            var opts = FWP.hooks.applyFilters('facetwp/set_options/fselect', settings, {
                'facet_name': facet_name
            });

            fSelect(this, opts);
        });
    });

    $().on('fs:changed', function(e) {
        var is_facet = $(e.detail[0]).closest('.facetwp-type-fselect').len() > 0;
        if (! FWP.is_refresh && is_facet) {
            FWP.autoload();
        }
    });

    $().on('fs:closed', function() {
        FWP.active_facet = null;
    });

    /* ======== Hierarchy ======== */

    FWP.hooks.addAction('facetwp/refresh/hierarchy', function($this, facet_name) {
        var selected_values = [];
        $this.find('.facetwp-link.checked').each(function() {
            selected_values.push(
                $(this).attr('data-value')
            );
        });
        FWP.facets[facet_name] = selected_values;
    });

    FWP.hooks.addFilter('facetwp/selections/hierarchy', function(output, params) {
        var $item = params.el.find('.facetwp-link.checked');
        return $item.len() ? $item.text() : '';
    });

    $().on('click', '.facetwp-type-hierarchy .facetwp-link', function() {
        $(this).closest('.facetwp-facet').find('.facetwp-link').removeClass('checked');
        if ('' !== $(this).attr('data-value')) {
            $(this).addClass('checked');
        }
        FWP.autoload();
    });

    $().on('click', '.facetwp-type-hierarchy .facetwp-toggle', function() {
        var $parent = $(this).closest('.facetwp-facet');
        $parent.find('.facetwp-toggle').toggleClass('facetwp-hidden');
        $parent.find('.facetwp-overflow').toggleClass('facetwp-hidden');
    });

    $().on('facetwp-loaded', function() {
        $('.facetwp-type-hierarchy .facetwp-overflow').each(function() {
            var num = $(this).find('.facetwp-link').len();
            var $el = $(this).next('.facetwp-toggle');
            $el.text($el.text().replace('{num}', num));
        });
    });

    /* ======== Number Range ======== */

    FWP.hooks.addAction('facetwp/refresh/number_range', function($this, facet_name) {
        var min = $this.find('.facetwp-number-min').val() || '';
        var max = $this.find('.facetwp-number-max').val() || '';
        FWP.facets[facet_name] = ('' !== min || '' !== max) ? [min, max] : [];
    });

    FWP.hooks.addFilter('facetwp/selections/number_range', function(output, params) {
        var $el = params.el;
        var vals = params.selected_values;
        var facet_name = $el.attr('data-name');
        var fields = FWP.settings[facet_name].fields;
        var out = '';

        if ('exact' == fields) {
            if ('' !== vals[0]) {
                out = vals[0];
            }
        }
        else if ('min' == fields) {
            if ('' !== vals[0]) {
                out = '[>=] ' + vals[0];
            }
        }
        else if ('max' == fields) {
            if ('' !== vals[1]) {
                out = '[<=] ' + vals[1];
            }
        }
        else if ('both' == fields) {
            if ('' !== vals[0] || '' !== vals[1]) {
                if ('' !== vals[0] && '' !== vals[1]) {
                    out = vals[0] + ' - ' + vals[1];
                }
                else if ('' !== vals[0]) {
                    out = '[>=] ' + vals[0];
                }
                else if ('' !== vals[1]) {
                    out = '[<=] ' + vals[1];
                }
            }
        }

        return out;
    });

    $().on('keyup', '.facetwp-type-number_range .facetwp-number', function(e) {
        if (13 === e.which && ! FWP.is_refresh) {
            FWP.autoload();
        }
    });

    $().on('click', '.facetwp-type-number_range .facetwp-submit', function() {
        FWP.refresh();
    });

    /* ======== Proximity ======== */

    $().on('facetwp-loaded', async function() {
        var $locations = $('.facetwp-location');

        if ($locations.nodes.length < 1) {
            return;
        }

        if (! FWP.loaded) {
            window.FWP_MAP = window.FWP_MAP || {};


            if ( 'place-class' == FWP.settings.places ) { // check places place-class

                try {
                    const { AutocompleteSessionToken, AutocompleteSuggestion } = await google.maps.importLibrary("places");
    
                    $().on('input', '.facetwp-location', FWP.helper.debounce(function(e) {
                        const val = $(e.target).val();
                        const $facet = $(e.target).closest('.facetwp-facet');
    
                        if (!val || val.length < FWP_JSON['proximity']['minLength']) {
                            $facet.find('.location-results').addClass('facetwp-hidden');
                            return;
                        }
    
                        FWP_MAP.token ||= new AutocompleteSessionToken();
    
                        const request = {
                            input: val,
                            sessionToken: FWP_MAP.token,
                            ...FWP_JSON['proximity']['autocomplete_options']
                        };
    
                        AutocompleteSuggestion.fetchAutocompleteSuggestions(request)
                            .then(({ suggestions }) => {
                                if (suggestions && suggestions.length > 0) {
                                    let html = '';
                                    suggestions.forEach((suggestion, index) => {                                    
                                        let result = suggestion.placePrediction;
                                        const css = (0 === index) ? ' active' : '';
                                        html += '<div class="location-result' + css + '" data-place-id="' + result.placeId + '" data-index="' + index + '">';
                                        html += '<span class="result-main">' + (result.mainText?.toString() ?? '') + '</span> ';
                                        html += '<span class="result-secondary">' + (result.secondaryText?.toString() ?? '') + '</span>';
                                        html += '<span class="result-description facetwp-hidden">' + (result.text?.toString() ?? '') + '</span>';
                                        html += '</div>';
                                    });
    
                                    html += '<div class="location-attribution"><div class="powered-by-google"></div></div>';
                                    $facet.find('.location-results').html(html).removeClass('facetwp-hidden');
                                } else {
                                    $facet.find('.location-results').html('').addClass('facetwp-hidden'); // Clear results if no predictions
                                }
                            })
                            .catch((error) => {
                                console.error("Error fetching predictions:", error);
                                $facet.find('.location-results').html('<div class="location-error">Error fetching results.</div>').removeClass('facetwp-hidden'); // Display error message
                            });
    
                    }, FWP_JSON['proximity']['queryDelay']));
    
                } catch (error) {
                    console.error("Error loading Places library:", error);
                }

            } else { // places places-service

                await google.maps.importLibrary("places");

                FWP_MAP.sessionToken = new google.maps.places.AutocompleteSessionToken();
                FWP_MAP.autocompleteService = new google.maps.places.AutocompleteService();
                FWP_MAP.placesService = new google.maps.places.PlacesService(
                    document.createElement('div')
                );

                // We need FWP_JSON available to grab the queryDelay
                $().on('input', '.facetwp-location', FWP.helper.debounce(function(e) {
                    var val = $(e.target).val();
                    var $facet = $(e.target).closest('.facetwp-facet');

                    if ('' == val || val.length < FWP_JSON['proximity']['minLength']) {
                        $facet.find('.location-results').addClass('facetwp-hidden');
                        return;
                    }

                    var options = FWP_JSON['proximity']['autocomplete_options'];
                    options.sessionToken = FWP_MAP.sessionToken;
                    options.input = val;

                    FWP_MAP.autocompleteService.getPredictions(options, function(results, status) {
                        if (status === google.maps.places.PlacesServiceStatus.OK) {
                            var html = '';

                            results.forEach(function(result, index) {
                                var css = (0 === index) ? ' active' : '';
                                html += '<div class="location-result' + css + '" data-id="' + result.place_id + '" data-index="' + index + '">';
                                html += '<span class="result-main">' + result.structured_formatting.main_text + '</span> ';
                                html += '<span class="result-secondary">' + result.structured_formatting.secondary_text + '</span>';
                                html += '<span class="result-description facetwp-hidden">' + result.description + '</span>';
                                html += '</div>';
                            });

                            html += '<div class="location-attribution"><div class="powered-by-google"></div></div>';

                            $facet.find('.location-results').html(html).removeClass('facetwp-hidden');
                        }
                    });
                }, FWP_JSON['proximity']['queryDelay']));
            }
        } // end check places

        $locations.each(function(el, idx) {
            $(this).trigger('keyup');
        });
    });

    $().on('click', '.location-result', async function() {

        if ( 'place-class' == FWP.settings.places ) { // check places place-class

            const $facet = $(this).closest('.facetwp-facet');
            const placeId = $(this).attr('data-place-id');

            const { Place, AutocompleteSessionToken } = await google.maps.importLibrary("places");

            FWP_MAP.token ||= new AutocompleteSessionToken();

            // Fields parameter for Place Details request
            const fields = ['location']; // Request only the location
            const place = new Place({ id: placeId, sessionToken: FWP_MAP.token });

            place.fetchFields({ fields })
                .then(() => {
                    $facet.find('.facetwp-lat').val(place.location.lat());
                    $facet.find('.facetwp-lng').val(place.location.lng());
                    FWP.autoload();
                })
                .catch((error) => {
                    console.error("Error fetching place details:", error);
                    // Handle the error appropriately (e.g., display an error message to the user)
                });
                
            FWP_MAP.token = null; // clear session token

        } else {

            var $facet = $(this).closest('.facetwp-facet');
            var place_id = $(this).attr('data-id');

            FWP_MAP.placesService.getDetails({
                placeId: place_id,
                fields: ['geometry'],
                sessionToken: FWP_MAP.sessionToken,
            }, function(place, status) {
                if (status === google.maps.places.PlacesServiceStatus.OK) {
                    $facet.find('.facetwp-lat').val(place.geometry.location.lat());
                    $facet.find('.facetwp-lng').val(place.geometry.location.lng());
                    FWP.autoload();
                }
            });

        }

        var description = $(this).find('.result-description').text();
        $('.facetwp-location').val(description);
        $('.location-results').addClass('facetwp-hidden');
    });

    /* ======== Proximity ======== */

    $().on('click', '.facetwp-type-proximity .locate-me', function(e) {
        var $this = $(this);
        var $facet = $this.closest('.facetwp-facet');
        var $input = $facet.find('.facetwp-location');
        var $lat = $facet.find('.facetwp-lat');
        var $lng = $facet.find('.facetwp-lng');

        // reset
        if ($this.hasClass('f-reset')) {
            $lat.val('');
            $lng.val('');
            $input.val('');
            FWP.autoload();
            return;
        }

        $this.addClass('f-loading');

        // HTML5 geolocation
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) { // Success callback
                    var lat = position.coords.latitude;
                    var lng = position.coords.longitude;

                    $lat.val(lat);
                    $lng.val(lng);

                    var geocoder = new google.maps.Geocoder();
                    var latlng = { lat: parseFloat(lat), lng: parseFloat(lng) };
                    geocoder.geocode({ 'location': latlng }, function(results, status) {
                        if (status === google.maps.GeocoderStatus.OK) {
                            $input.val(results[0].formatted_address);
                        } else {
                            console.error("Geocoder failed due to: " + status);
                            $input.val('Your location'); // Fallback
                            alert("Could not determine your address."); // Inform user
                        }
                        $this.addClass('f-reset');
                        FWP.autoload();
                    });

                    $this.removeClass('f-loading');

                    FWP.hooks.doAction('facetwp/geolocation/success', {
                        'facet': $facet,
                        'position': position
                    });
                },
                function(error) { // Error callback
                    $this.removeClass('f-loading');
                    let errorMessage = "Could not get your location.";

                    switch (error.code) {
                        case error.PERMISSION_DENIED:
                            errorMessage = "User denied the request for Geolocation.";
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMessage = "Location information is unavailable.";
                            break;
                        case error.TIMEOUT:
                            errorMessage = "The request to get user location timed out.";
                            break;
                        case error.UNKNOWN_ERROR:
                            errorMessage = "An unknown error occurred.";
                            break;
                    }

                    console.error("Geolocation error: " + errorMessage, error);
                    alert(errorMessage); // Inform user
                    FWP.hooks.doAction('facetwp/geolocation/error', {
                        'facet': $facet,
                        'error': error
                    });
                }
            );
        } else {
            // Browser doesn't support Geolocation
            $this.removeClass('f-loading');
            alert("Your browser doesn't support Geolocation.");
        }
    });

    $().on('keyup', '.facetwp-location', function(e) {
        var $facet = $(this).closest('.facetwp-facet');
        var method = ('' !== $(this).val()) ? 'addClass' : 'removeClass';
        $facet.find('.locate-me')[method]('f-reset');

        if (38 === e.which || 40 === e.which || 13 === e.which) {
            var curr_index = parseInt($facet.find('.location-result.active').attr('data-index'));
            var max_index = parseInt($facet.find('.location-result').last().attr('data-index'));
        }

        if (38 === e.which) { // up
            var new_index = (0 < curr_index) ? (curr_index - 1) : max_index;
            $facet.find('.location-result.active').removeClass('active');
            $facet.find('.location-result[data-index="' + new_index + '"]').addClass('active');
        }
        else if (40 === e.which) { // down
            var new_index = (curr_index < max_index) ? (curr_index + 1) : 0;
            $facet.find('.location-result.active').removeClass('active');
            $facet.find('.location-result[data-index="' + new_index + '"]').addClass('active');
        }
        else if (13 === e.which) { // enter
            $facet.find('.location-result.active').trigger('click');
        }
    });

    var hideDropdown = function(e) {
        var $el = $(e.target);
        var $wrap = $el.closest('.facetwp-input-wrap');

        if ($wrap.len() < 1 || $el.hasClass('f-reset')) {
            $('.location-results').addClass('facetwp-hidden');
        }
    };

    $().on('click', hideDropdown);
    $().on('focusout', hideDropdown);

    $().on('focusin', '.facetwp-location', function() {
        var $facet = $(this).closest('.facetwp-facet');
        if ('' != $(this).val()) {
            $facet.find('.location-results').removeClass('facetwp-hidden');
        }
    });

    $().on('change', '.facetwp-radius', function() {
        var $facet = $(this).closest('.facetwp-facet');
        if ('' !== $facet.find('.facetwp-location').val()) {
            FWP.autoload();
        }
    });

    $().on('input', '.facetwp-radius-slider', function(e) {
        var $facet = $(this).closest('.facetwp-facet');
        $facet.find('.facetwp-radius-dist').text(e.target.value);
    });

    FWP.hooks.addAction('facetwp/refresh/proximity', function($this, facet_name) {
        var lat = $this.find('.facetwp-lat').val();
        var lng = $this.find('.facetwp-lng').val();
        var radius = $this.find('.facetwp-radius').val();
        var location = encodeURIComponent($this.find('.facetwp-location').val());
        FWP.frozen_facets[facet_name] = 'hard';
        FWP.facets[facet_name] = ('' !== lat && 'undefined' !== typeof lat) ?
            [lat, lng, radius, location] : [];
    });

    FWP.hooks.addFilter('facetwp/selections/proximity', function(label, params) {
        return FWP_JSON['proximity']['clearText'];
    });

    /* ======== Search ======== */

    FWP.logic.search = {
        delay_refresh: FWP.helper.debounce(function(facet_name) {
            FWP.frozen_facets[facet_name] = 'soft';
            FWP.autoload();
        }, 500)
    };

    FWP.hooks.addAction('facetwp/refresh/search', function($this, facet_name) {
        var $input = $this.find('.facetwp-search');
        FWP.facets[facet_name] = $input.val() || '';
        $this.find('.facetwp-icon').addClass('f-loading');
    });

    FWP.hooks.addAction('facetwp/loaded', function() {
        $('.facetwp-type-search .facetwp-icon').removeClass('f-loading');
    });

    $().on('keyup', '.facetwp-type-search .facetwp-search', function(e) {
        if (FWP.is_refresh) {
            return;
        }

        var $facet = $(this).closest('.facetwp-facet');
        var facet_name = $facet.attr('data-name');

        if ('undefined' !== typeof FWP.settings[facet_name]) {
            if ('yes' === FWP.settings[facet_name]['auto_refresh']) {
                FWP.logic.search['delay_refresh'](facet_name);
            }
            else if (13 === e.keyCode) {
                FWP.autoload();
            }
        }
    });

    $().on('click', '.facetwp-type-search .facetwp-icon', function() {
        if (! FWP.is_refresh) {
            FWP.autoload();
        }
    });

    /* ======== Slider ======== */

    FWP.hooks.addAction('facetwp/refresh/slider', function($this, facet_name) {
        FWP.facets[facet_name] = [];

        var $active = FWP.active_facet;
        var url_var = FWP.helper.getUrlVar(facet_name);

        if (null !== $active && facet_name === $active.attr('data-name')) {
            var node = $active.find('.facetwp-slider').nodes[0];
            if ('undefined' !== typeof node.noUiSlider) {
                FWP.facets[facet_name] = node.noUiSlider.get();
            }
        }
        else if (false !== url_var) {
            FWP.facets[facet_name] = url_var.replace('%2C', ',').split(',');
        }

        // prevent changes during loading
        $this.find('.facetwp-slider').attr('disabled', true);
    });

    FWP.hooks.addAction('facetwp/loaded', function() {
        $('.facetwp-type-slider .facetwp-slider').nodes.forEach(node => node.removeAttribute('disabled'));
    });

    FWP.hooks.addAction('facetwp/set_label/slider', function($this) {
        var facet_name = $this.attr('data-name');
        var min = FWP.settings[facet_name]['lower'];
        var max = FWP.settings[facet_name]['upper'];
        var format = FWP.settings[facet_name]['format'];
        var opts = {
            decimal_separator: FWP.settings[facet_name]['decimal_separator'],
            thousands_separator: FWP.settings[facet_name]['thousands_separator']
        };

        var prefix = FWP.settings[facet_name]['prefix'];
        var suffix = FWP.settings[facet_name]['suffix'];

        if ( min === max ) {
            var label = prefix + nummy(min).format(format, opts) + suffix;
        }
        else {
            var label = prefix + nummy(min).format(format, opts) + suffix + ' &mdash; ' +
                prefix + nummy(max).format(format, opts) + suffix;
        }
        $this.find('.facetwp-slider-label').html(label);
    });

    FWP.hooks.addFilter('facetwp/selections/slider', function(output, params) {
        var $item = params.el.find('.facetwp-slider-label');
        return $item.len() ? $item.text() : '';
    });

    $().on('facetwp-loaded', function() {
        $('.facetwp-type-slider .facetwp-slider').each(function() {
            var $this = $(this);
            var $parent = $this.closest('.facetwp-facet');
            var facet_name = $parent.attr('data-name');
            var opts = FWP.settings[facet_name];

            // custom slider options
            var slider_opts = FWP.hooks.applyFilters('facetwp/set_options/slider', {
                range: opts.range,
                start: opts.start,
                step: parseFloat(opts.step),
                connect: true,
                handleAttributes: opts.handle_attributes
            }, { 'facet_name': facet_name });

            if ($this.hasClass('ready')) {
                $this.nodes[0].noUiSlider.updateOptions({
                    range: slider_opts.range
                }, false);
            }
            else {

                // fail on slider already initialized
                if ('undefined' !== typeof this.noUiSlider) {
                    return;
                }

                // fail if start values are null
                if (null === slider_opts.start[0]) {
                    return;
                }

                // fail on invalid ranges
                if (parseFloat(opts.range.min) > parseFloat(opts.range.max)) {
                    FWP.settings[facet_name]['lower'] = opts.range.min;
                    FWP.settings[facet_name]['upper'] = opts.range.max;
                    FWP.hooks.doAction('facetwp/set_label/slider', $parent);
                    return;
                }

                // disable the UI if only 1 value
                if (parseFloat(opts.range.min) == parseFloat(opts.range.max)) {
                    $this.attr('data-disabled', 'true');
                }

                var slider = this;
                noUiSlider.create(slider, slider_opts);

                // Only trigger a refresh if slider handles have actually moved
                slider.noUiSlider.on('slide', function () {
                    $this.attr('data-has-moved', 'true');
                });

                slider.noUiSlider.on('update', function(values, handle) {
                    FWP.settings[facet_name]['lower'] = values[0];
                    FWP.settings[facet_name]['upper'] = values[1];
                    FWP.hooks.doAction('facetwp/set_label/slider', $parent);
                });

                // This runs after click or handle slide
                slider.noUiSlider.on('set', function() {
                    if ('true' === $this.attr('data-has-moved')) {
                        $this.attr('data-has-moved', '');
                        FWP.active_facet = $this.closest('.facetwp-facet');
                        FWP.autoload();
                    }
                });

                $this.addClass('ready');
            }
        });

        // hide reset buttons
        $('.facetwp-type-slider').each(function() {
            var name = $(this).attr('data-name');
            var $button = $(this).find('.facetwp-slider-reset');
            var method = FWP.facets[name].length ? 'removeClass' : 'addClass';
            $button[method]('facetwp-hidden');
        });
    });

    $().on('click', '.facetwp-type-slider .facetwp-slider-reset', function() {
        var facet_name = $(this).closest('.facetwp-facet').attr('data-name');
        FWP.reset(facet_name);
    });

    /* ======== Rating ======== */

    FWP.hooks.addAction('facetwp/refresh/rating', function($this, facet_name) {
        var selected_values = [];
        $this.find('.facetwp-star.selected').each(function() {
            var val = $(this).attr('data-value');
            if ('' != val) {
                selected_values.push(val);
            }
        });
        FWP.facets[facet_name] = selected_values;
    });

    $().on('mouseover', '.facetwp-star:not(.disabled)', function() {
        var $facet = $(this).closest('.facetwp-facet');

        if ($(this).hasClass('selected')) {
            $facet.find('.facetwp-star-label').text(FWP_JSON['rating']['Undo']);
        }
        else {
            var label = ('5' == $(this).attr('data-value')) ? '' : FWP_JSON['rating']['& up'];
            $facet.find('.facetwp-star-label').text(label);
            $facet.find('.facetwp-counter').text('(' + $(this).attr('data-counter') + ')');
        }
    });

    $().on('mouseout', '.facetwp-star:not(.disabled)', function() {
        var $facet = $(this).closest('.facetwp-facet');
        $facet.find('.facetwp-star-label').text('');
        $facet.find('.facetwp-counter').text('');
    });

    $().on('click', '.facetwp-star:not(.disabled)', function() {
        var $facet = $(this).closest('.facetwp-facet');
        var is_selected = $(this).hasClass('selected');
        $facet.find('.facetwp-star').removeClass('selected');
        if (! is_selected) {
            $(this).addClass('selected');
        }
        FWP.autoload();
    });

    /* ======== Sort ======== */

    FWP.hooks.addAction('facetwp/refresh/sort', function($this, facet_name) {
        var val = $this.find('select').val();
        FWP.facets[facet_name] = val ? [val] : [];
    });

    $().on('change', '.facetwp-type-sort select', function() {
        var $facet = $(this).closest('.facetwp-facet');
        var facet_name = $facet.attr('data-name');

        if ('' !== $(this).val()) {
            FWP.frozen_facets[facet_name] = 'hard';
        }
        FWP.autoload();
    });

    /* ======== Pager ======== */

    FWP.hooks.addAction('facetwp/refresh/pager', function($this, facet_name) {
        FWP.facets[facet_name] = [];
    });

    FWP.hooks.addFilter('facetwp/template_html', function(resp, params) {
        if (FWP.is_load_more) {
            FWP.is_load_more = false;

            // layout builder
            if ( 0 < $('.fwpl-layout').len() ) {
                var layout = $(params.html).find('.fwpl-layout').html();
                $('.facetwp-template .fwpl-layout').append(layout);
            }
            // other
            else {
                $('.facetwp-template').append(params.html);
            }
            return true;
        }
        return resp;
    });

    $().on('click', '.facetwp-load-more', function() {
        var loading_text = $(this).attr('data-loading');
        $(this).html(loading_text);

        FWP.is_load_more = true; // set the flag
        FWP.load_more_paged += 1; // next page
        FWP.paged = FWP.load_more_paged; // grab the next page of results
        FWP.soft_refresh = true; // don't process facets
        FWP.refresh();
    });

    $().on('facetwp-loaded', function() {
        var is_visible = (FWP.settings.pager.page < FWP.settings.pager.total_pages);
        var method = is_visible ? 'removeClass' : 'addClass';
        $('.facetwp-load-more')[method]('facetwp-hidden');
    });

    $().on('facetwp-refresh', function() {
        if (! FWP.loaded || ! FWP.is_load_more) {
            FWP.load_more_paged = 1;
        }
    });

    /* ======== Reset ======== */

    $().on('click', '.facetwp-reset', function() {
       let values = $(this).nodes[0]._facets;
        FWP.reset(values);
    });

    $().on('facetwp-loaded', function() {
        if (! FWP.loaded) {
            $('.facetwp-reset').each(function() {
                let $this = $(this);
                let mode = $this.attr('data-mode');
                let values = $this.attr('data-values');

                values = (null == values) ? Object.keys(FWP.facets) : values.split(',');

                if ('exclude' == mode) {
                    values = Object.keys(FWP.facets).filter(name => {
                        return !values.includes(name);
                    });
                }

                // store the target facets (array) within the DOM element
                $this.nodes[0]._facets = values;
            });
        }

        // hide the reset if its target facets are all empty
        $('.facetwp-hide-empty').each(function() {
            let $this = $(this);
            let $wrap = $this.closest('.facetwp-facet');
            let facets = $this.nodes[0]._facets;
            let all_empty = facets.every(val => 'undefined' === typeof FWP.facets[val] || FWP.facets[val].length < 1);
            all_empty ? $wrap.addClass('facetwp-hidden') : $wrap.removeClass('facetwp-hidden');
        });
    });

})(fUtil);
