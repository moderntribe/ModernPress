(function($) {
    var last_checked = null;

    if ('undefined' !== typeof FWP.hooks) {
        FWP.hooks.addAction('facetwp/loaded', function() {

            // checkbox, radio, fselect
            $('.facetwp-checkbox, .facetwp-radio, .fs-option').each(function() {
                let $el = $(this);
                if (! $el.hasClass('disabled')) {
                    $el.attr('role', 'checkbox');
                    $el.attr('aria-checked', $el.hasClass('checked') || $el.hasClass('selected') ? 'true' : 'false');
                    $el.attr('aria-label', $el.text());
                    $el.attr('tabindex', 0);
                }
            });

            // pager, show more, user selections, hierarchy
            $('.facetwp-page, .facetwp-toggle, .facetwp-selection-value, .facetwp-link').each(function() {
                let $el = $(this);
                let label = $el.text();

                if ($el.hasClass('facetwp-page')) {
                    label = FWP_JSON.a11y.label_page + ' ' + label;

                    if ($el.hasClass('next')) {
                        label = FWP_JSON.a11y.label_page_next;
                    }
                    else if ($el.hasClass('prev')) {
                        label = FWP_JSON.a11y.label_page_prev;
                    }
                }

                $el.attr('role', 'link');
                $el.attr('aria-label', label);
                $el.attr('tabindex', 0);
            });

            // dropdown, sort facet, old sort feature
            $('.facetwp-type-dropdown select, .facetwp-type-sort select, .facetwp-sort-select select').each(function() {
                $(this).attr('aria-label', $(this).find('option:selected').text());
            });

            // search, date, number range
            $('.facetwp-search, .facetwp-date, .facetwp-number').each(function() {
                $(this).attr('aria-label', $(this).attr('placeholder'));
            });

            // checkbox group
            $('.facetwp-type-checkboxes').each(function() {
                let facet_name = $(this).attr('data-name');
                $(this).attr('aria-label', FWP.settings.labels[facet_name]);
                $(this).attr('role', 'group');
            });

            // fselect
            $('.fs-wrap').each(function() {
                $(this).attr('role', 'button');
                $(this).attr('aria-haspopup', 'true');
                $(this).attr('aria-expanded', $(this).hasClass('fs-open') ? 'true' : 'false');
                $(this).attr('aria-label', $(this).find('.fs-label').html());
            });

            $('.facetwp-type-fselect .facetwp-dropdown').attr('aria-hidden', 'true');

            // pager
            $('.facetwp-pager').attr('role', 'navigation');
            $('.facetwp-page.active').attr('aria-current', 'true');

            // focus on selection
            if (null != last_checked) {
                var $el = $('.facetwp-facet [data-value="' + last_checked + '"]');
                if ($el.len()) {
                    $el.nodes[0].focus();
                }
                last_checked = null;
            }
        }, 999);
    }

    // keyboard support
    $().on('keydown', '.facetwp-checkbox, .facetwp-radio, .facetwp-link', function(e) {
        if (32 == e.keyCode || 13 == e.keyCode) {
            last_checked = $(this).attr('data-value');
            e.preventDefault();
            this.click();
        }
    });

    $().on('keydown', '.facetwp-page, .facetwp-toggle, .facetwp-selection-value', function(e) {
        if (32 == e.keyCode || 13 == e.keyCode) {
            e.preventDefault();
            this.click();
        }
    });

    // fselect - determine "aria-expanded"
    function toggleExpanded(e) {
        var $fs = $(e.detail[0]);
        $fs.attr('aria-expanded', $fs.hasClass('fs-open') ? 'true' : 'false');
    }

    $().on('fs:opened', toggleExpanded);
    $().on('fs:closed', toggleExpanded);
})(fUtil);