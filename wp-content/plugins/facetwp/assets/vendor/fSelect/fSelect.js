window.fSelect = (() => {

    var build = {};

    class fSelect {

        constructor(selector, options) {
            let that = this;

            var defaults = {
                placeholder: 'Select some options',
                numDisplayed: 3,
                overflowText: '{n} selected',
                searchText: 'Search',
                noResultsText: 'No results found',
                showSearch: true,
                optionFormatter: false
            };

            that.settings = Object.assign({}, defaults, options);
            build = {output: '', optgroup: 0, idx: 0};

            if ('string' === typeof selector) {
                var nodes = Array.from(document.querySelectorAll(selector));
            }
            else if (selector instanceof Node) {
                var nodes = [selector];
            }
            else if (Array.isArray(selector)) {
                var nodes = selector;
            }
            else {
                var nodes = [];
            }

            if ('undefined' === typeof window.fSelectInit) {
                window.fSelectInit = {
                    searchCache: '',
                    lastChoice: null,
                    lastFocus: null,
                    activeEl: null
                };
                that.bindEvents();
            }

            nodes.forEach((input) => {
                if (typeof input.fselect === 'object') {
                    input.fselect.destroy();
                }

                that.settings.multiple = input.matches('[multiple]');
                input.fselect = that;
                that.input = input;
                that.create();
            });
        }

        create() {
            var that = this;
            var options = that.buildOptions();
            var label = that.getDropdownLabel();
            var mode = (that.settings.multiple) ? 'multiple' : 'single';
            var searchClass = (that.settings.showSearch) ? '' : ' fs-hidden';
            var noResultsClass = (build.idx < 2) ? '' : ' fs-hidden';

            var html = `
            <div class="fs-wrap ${mode}" tabindex="0">
                <div class="fs-label-wrap">
                    <div class="fs-label">${label}</div>
                    <span class="fs-arrow"></span>
                </div>
                <div class="fs-dropdown fs-hidden">
                    <div class="fs-search${searchClass}">
                        <input type="text" placeholder="${that.settings.searchText}" />
                    </div>
                    <div class="fs-no-results${noResultsClass}">${that.settings.noResultsText}</div>
                    <div class="fs-options">${options}</div>
                </div>
            </div>
            `;

            var tpl = document.createElement('template');
            tpl.innerHTML = html;
            var wrap = tpl.content.querySelector('.fs-wrap');
            that.input.parentNode.insertBefore(wrap, that.input.nextSibling);
            that.input.classList.add('fs-hidden');

            // add a relationship link
            that.input._rel = wrap;
            wrap._rel = that.input;
        }

        destroy() {
            this.input._rel.remove();
            this.input.classList.remove('fs-hidden');
            delete this.input._rel;
        }

        reload() {
            this.destroy();
            this.create();
        }

        open() {
            var wrap = this.input._rel;
            wrap.classList.add('fs-open');
            wrap.querySelector('.fs-dropdown').classList.remove('fs-hidden');

            // don't auto-focus for touch devices
            if (! window.matchMedia("(pointer: coarse)").matches) {
                wrap.querySelector('.fs-search input').focus();
            }

            window.fSelectInit.lastChoice = this.getSelectedOptions('value');
            window.fSelectInit.activeEl = wrap;

            this.trigger('fs:opened', wrap);
        }

        close() {
            this.input._rel.classList.remove('fs-open');
            this.input._rel.querySelector('.fs-dropdown').classList.add('fs-hidden');

            window.fSelectInit.searchCache = '';
            window.fSelectInit.lastChoice = null;
            window.fSelectInit.lastFocus = null;
            window.fSelectInit.activeEl = null;

            this.trigger('fs:closed', this.input._rel);
        }

        buildOptions(parent) {
            var that = this;
            var parent = parent || that.input;

            Array.from(parent.children).forEach((node) => {
                if ('optgroup' === node.nodeName.toLowerCase()) {
                    var opt = `<div class="fs-optgroup-label" data-group="${build.optgroup}">${node.label}</div>`;
                    build.output += opt;
                    that.buildOptions(node);
                    build.optgroup++;
                }
                else {
                    var val = node.value;

                    // skip the first choice in multi-select mode
                    if (0 === build.idx && '' === val && that.settings.multiple) {
                        build.idx++;
                        return;
                    }

                    var classes = ['fs-option', 'g' + build.optgroup];

                    // append existing classes
                    node.className.split(' ').forEach((className) => {
                        if ('' !== className) {
                            classes.push(className);
                        }
                    });

                    if (node.matches('[disabled]')) classes.push('disabled');
                    if (node.matches('[selected]')) classes.push('selected');
                    classes = classes.join(' ');

                    if ('function' === typeof that.settings.optionFormatter) {
                        node.label = that.settings.optionFormatter(node.label, node);
                    }

                    var opt = `<div class="${classes}" data-value="${val}" data-idx="${build.idx}" tabindex="-1"><span class="fs-checkbox"><i></i></span><div class="fs-option-label">${node.label}</div></div>`;

                    build.output += opt;
                    build.idx++;
                }
            });

            return build.output;
        }

        getSelectedOptions(field, context) {
            var context = context || this.input;
            return Array.from(context.selectedOptions).map((opt) => {
                return (field) ? opt[field] : opt;
            });
        }

        getAdjacentSibling(which) {
            var that = this;
            var which = which || 'next';
            var sibling = window.fSelectInit.lastFocus;
            var selector = '.fs-option:not(.fs-hidden):not(.disabled)';

            if (sibling) {
                sibling = sibling[which + 'ElementSibling'];

                while (sibling) {
                    if (sibling.matches(selector)) break;
                    sibling = sibling[which + 'ElementSibling'];
                }

                return sibling;
            }
            else if ('next' == which) {
                sibling = that.input._rel.querySelector(selector);
            }

            return sibling;
        }

        getDropdownLabel() {
            var that = this;
            var labelText = that.getSelectedOptions('text');

            if (labelText.length < 1) {
                labelText = that.settings.placeholder;
            }
            else if (labelText.length > that.settings.numDisplayed) {
                labelText = that.settings.overflowText.replace('{n}', labelText.length);
            }
            else {
                labelText = labelText.join(', ');
            }

            return labelText;
        }

        debounce(func, wait) {
            var timeout;
            return (...args) => {
                var boundFunc = func.bind(this, ...args);
                clearTimeout(timeout);
                timeout = setTimeout(boundFunc, wait);
            }
        }

        trigger(eventName, ...args) {
            document.dispatchEvent(new CustomEvent(eventName, {detail: [...args]}));
        }

        on(eventName, elementSelector, handler) {
            document.addEventListener(eventName, function(e) {
                // loop parent nodes from the target to the delegation node
                for (var target = e.target; target && target != this; target = target.parentNode) {
                    if (target.matches(elementSelector)) {
                        handler.call(target, e);
                        break;
                    }
                }
            }, false);
        }

        bindEvents() {
            var that = this;
            var optionSelector = '.fs-option:not(.fs-hidden):not(.disabled)';
            var unaccented = (str) => str.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

            // debounce search for better performance
            that.on('keyup', '.fs-search input', that.debounce(function(e) {
                var wrap = e.target.closest('.fs-wrap');
                var options = wrap._rel.options;

                var matchOperators = /[|\\{}()[\]^$+*?.]/g;
                var keywords = e.target.value.replace(matchOperators, '\\$&');
                keywords = unaccented(keywords);

                // if the searchCache already has a prefixed version of this search
                // then don't un-hide the existing exclusions
                if (0 !== keywords.indexOf(window.fSelectInit.searchCache)) {
                    wrap.querySelectorAll('.fs-option, .fs-optgroup-label').forEach((node) => node.classList.remove('fs-hidden'));
                }

                window.fSelectInit.searchCache = keywords;

                for (var i = 0; i < options.length; i++) {
                    if ('' === options[i].value) continue;

                    var needle = new RegExp(keywords, 'gi');
                    var haystack = unaccented(options[i].text);

                    if (null === haystack.match(needle)) {
                        wrap.querySelector('.fs-option[data-idx="' + i + '"]').classList.add('fs-hidden');
                    }
                }

                // hide optgroups if no choices
                wrap.querySelectorAll('.fs-optgroup-label').forEach((node) => {
                    var group = node.getAttribute('data-group');
                    var container = node.closest('.fs-options');
                    var count = container.querySelectorAll('.fs-option.g' + group + ':not(.fs-hidden)').length;

                    if (count < 1) {
                        node.classList.add('fs-hidden');
                    }
                });

                // toggle the noResultsText div
                if (wrap.querySelectorAll('.fs-option:not(.fs-hidden').length) {
                    wrap.querySelector('.fs-no-results').classList.add('fs-hidden');
                }
                else {
                    wrap.querySelector('.fs-no-results').classList.remove('fs-hidden');
                }

            }, 100));

            that.on('click', optionSelector, function(e) {
                var wrap = this.closest('.fs-wrap');
                var value = this.getAttribute('data-value');
                var input = wrap._rel;
                var isMultiple = wrap.classList.contains('multiple');

                if (!isMultiple) {
                    input.value = value;
                    wrap.querySelectorAll('.fs-option.selected').forEach((node) => node.classList.remove('selected'));
                }
                else {
                    var idx = parseInt(this.getAttribute('data-idx'));
                    input.options[idx].selected = !this.classList.contains('selected');
                }

                this.classList.toggle('selected');

                var label = input.fselect.getDropdownLabel();
                wrap.querySelector('.fs-label').innerHTML = label;

                // fire a change event
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.fselect.trigger('fs:changed', wrap);

                if (!isMultiple) {
                    input.fselect.close();
                }

                e.stopImmediatePropagation();
            });

            that.on('keydown', '*', function(e) {
                var wrap = this.closest('.fs-wrap');

                if (!wrap) return;

                if (-1 < [38, 40, 27].indexOf(e.which)) {
                    e.preventDefault();
                }

                if (32 == e.which || 13 == e.which) { // space, enter
                    if (e.target.closest('.fs-search')) {
                        // preserve spaces for search
                    }
                    else if (e.target.matches(optionSelector)) {
                        e.target.click();
                        e.preventDefault();
                    }
                    else {
                        wrap.querySelector('.fs-label').click();
                        e.preventDefault();
                    }
                }
                else if (38 == e.which) { // up
                    var sibling = wrap._rel.fselect.getAdjacentSibling('previous');
                    window.fSelectInit.lastFocus = sibling; // stop at the search box

                    if (sibling) {
                        sibling.focus();
                    }
                    else {
                        wrap.querySelector('.fs-search input').focus();
                    }
                }
                else if (40 == e.which) { // down
                    var sibling = wrap._rel.fselect.getAdjacentSibling('next');

                    if (sibling) {
                        sibling.focus();
                        window.fSelectInit.lastFocus = sibling; // stop at the bottom
                    }
                }
                else if (9 == e.which || 27 == e.which) { // tab, esc
                    wrap._rel.fselect.close();
                }
            });

            that.on('click', '*', function(e) {
                var wrap = this.closest('.fs-wrap');
                var lastActive = window.fSelectInit.activeEl;

                if (wrap) {
                    var labelWrap = this.closest('.fs-label-wrap');

                    if (labelWrap) {
                        if (lastActive) {
                            lastActive._rel.fselect.close();
                        }
                        if (wrap !== lastActive) {
                            wrap._rel.fselect.open();
                        }
                    }
                }
                else {
                    if (lastActive) {
                        lastActive._rel.fselect.close();
                    }
                }
            });
        }
    }

    var $ = (selector, options) => new fSelect(selector, options);

    return $;

})();

if ('undefined' !== typeof fUtil) {
    fUtil.fn.fSelect = function(opts) {
        this.each(function() { // no arrow function to preserve "this"
            fSelect(this, opts);
        });
        return this;
    };
}
