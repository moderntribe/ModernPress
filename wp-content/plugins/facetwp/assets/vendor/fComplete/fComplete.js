window.fComplete = (() => {

    class fComplete {

        constructor(selector, options) {
            let that = this;

            var defaults = {
                data: [],
                minChars: 3,
                maxResults: 10,
                searchDelay: 200,
                loadingText: 'Loading...',
                minCharsText: 'Enter {n} or more characters',
                noResultsText: 'No results',
                beforeRender: null,
                onSelect: null
            };

            that.settings = Object.assign({}, defaults, options);
            that.settings.minChars = Math.max(1, that.settings.minChars);
            that.settings.maxResults = Math.max(1, that.settings.maxResults);
            that.settings.searchDelay = Math.max(0, that.settings.searchDelay);

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

            if ('undefined' === typeof window.fCompleteInit) {
                window.fCompleteInit = {
                    lastFocus: null,
                    eventsBound: true
                };
                that.bindEvents();
            }

            nodes.forEach((input) => {
                that.input = input;
                input.fcomplete = that;
                input.classList.add('fcomplete-enabled');
                that.create();
            });
        }

        create() {
            var that = this;

            var html = `
            <div class="fcomplete-wrap fcomplete-hidden">
                <div class="fcomplete-status"></div>
                <div class="fcomplete-results"></div>
            </div>
            `;

            var rect = that.input.getBoundingClientRect();

            var tpl = document.createElement('template');
            tpl.innerHTML = html;
            var wrap = tpl.content.querySelector('.fcomplete-wrap');
            wrap.style.minWidth = rect.width + 'px';
            that.input.parentNode.insertBefore(wrap, that.input.nextSibling);

            // add a relationship link
            that.input._rel = wrap;
            wrap._rel = that.input;
        }

        destroy() {
            this.input._rel.remove();
            delete this.input._rel;
        }

        reload() {
            this.destroy();
            this.create();
        }

        open() {
            this.input._rel.classList.remove('fcomplete-hidden');
        }

        close() {
            window.fCompleteInit.lastFocus = null;
            this.input._rel.classList.add('fcomplete-hidden');
        }

        setStatus(text) {
            var text = text.replace('{n}', this.settings.minChars);
            var node = this.input._rel.querySelector('.fcomplete-status');
            node.innerHTML = text;

            var method = (text) ? 'remove' : 'add';
            node.classList[method]('fcomplete-hidden');
        }

        render(data) {
            var data = (this.settings.beforeRender) ? this.settings.beforeRender(data) : data;
            var wrap = this.input._rel;

            if (data.length) {
                var html = '';
                var len = Math.min(data.length, this.settings.maxResults);

                for (var i = 0; i < len; i++) {
                    html += `<div class="fcomplete-result" data-value="${data[i].value}" tabindex="-1">${data[i].label}</div>`;
                }

                wrap.querySelector('.fcomplete-results').innerHTML = html;
                this.setStatus('');
            }
            else {
                wrap.querySelector('.fcomplete-results').innerHTML = '';
                this.setStatus(this.settings.noResultsText);
            }

            this.input.fcomplete.open();
        }

        getAdjacentSibling(which) {
            var that = this;
            var which = which || 'next';
            var sibling = window.fCompleteInit.lastFocus;
            var selector = '.fcomplete-result';

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
            let that = this;

            that.on('click', '*', function(e) {
                var wrap = this.closest('.fcomplete-wrap');
                var isInput = this.classList.contains('fcomplete-enabled');

                if (isInput) {
                    var input = this;
                    var settings = input.fcomplete.settings;
                    var status = (settings.minChars > input.value.length) ? settings.minCharsText : '';
                    input.fcomplete.setStatus(status);
                    this.fcomplete.open();
                }
                else if (!wrap && !isInput) {
                    document.querySelectorAll('.fcomplete-wrap').forEach((node) => node.classList.add('fcomplete-hidden'));
                }
            });

            that.on('click', '.fcomplete-result', function(e) {
                var wrap = e.target.closest('.fcomplete-wrap');
                var input = wrap._rel;
                input.value = e.target.getAttribute('data-value');

                if (typeof input.fcomplete.settings.onSelect === 'function') {
                    input.fcomplete.settings.onSelect();
                }

                input.fcomplete.close();
            });
            that.on('keydown', '*', function(e) {
                var wrap = this.closest('.fcomplete-wrap');
                var isInput = this.classList.contains('fcomplete-enabled');

                if (!wrap && !isInput) return;

                var input = (wrap) ? wrap._rel : this;
                wrap = (wrap) ? wrap : input._rel;

                if (-1 < [13, 38, 40, 27].indexOf(e.which)) {
                    e.preventDefault();
                }

                if (13 == e.which) { // enter
                    if (this.classList.contains('fcomplete-result')) {
                        this.click();
                    }
                }
                else if (38 == e.which) { // up
                    if (this.classList.contains('fcomplete-result')) {
                        var sibling = wrap._rel.fcomplete.getAdjacentSibling('previous');
                        window.fCompleteInit.lastFocus = sibling; // stop at the search box
                        (sibling) ? sibling.focus() : input.focus();
                    }
                }
                else if (40 == e.which) { // down
                    if (this === input) {
                        var firstResult = wrap.querySelector('.fcomplete-result');

                        if (firstResult) {
                            firstResult.focus();
                            window.fCompleteInit.lastFocus = firstResult;
                        }
                    }
                    else if (this.classList.contains('fcomplete-result')) {
                        var sibling = wrap._rel.fcomplete.getAdjacentSibling('next');

                        if (sibling) {
                            sibling.focus();
                            window.fCompleteInit.lastFocus = sibling; // stop at the bottom
                        }
                    }
                }
                else if (9 == e.which || 27 == e.which) { // tab, esc
                    wrap._rel.fcomplete.close();
                }
            });

            that.on('keyup', '.fcomplete-enabled', that.debounce(function(e) {
                if (-1 < [13, 38, 40, 27].indexOf(e.which)) {
                    return;
                }

                var input = e.target;
                var settings = input.fcomplete.settings;

                if (settings.minChars  <= input.value.length) {
                    if (Array.isArray(settings.data)) {
                        input.fcomplete.render(settings.data);
                    }
                    else if (typeof settings.data === 'function') {
                        input.fcomplete.setStatus(settings.loadingText);
                        settings.data();
                    }
                }
                else {
                    input.fcomplete.render([]);
                    input.fcomplete.setStatus(settings.minCharsText);
                }
            }, that.settings.searchDelay));
        }
    }

    var $ = (selector, options) => new fComplete(selector, options);

    return $;

})();