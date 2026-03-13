window.fUtil = (() => {

    class fUtil {

        constructor(selector) {
            if (typeof selector === 'string' || selector instanceof String) { // string
                var selector = selector.replace(':selected', ':checked');

                if ('' === selector) {
                    this.nodes = [];
                }
                else if (this.isValidSelector(selector)) {
                    this.nodes = Array.from(document.querySelectorAll(selector));
                }
                else {
                    var tpl = document.createElement('template');
                    tpl.innerHTML = selector;
                    this.nodes = [tpl.content];
                }
            }
            else if (Array.isArray(selector)) { // array of nodes
                this.nodes = selector;
            }
            else if (typeof selector === 'object' && selector.nodeName) { // node
                this.nodes = [selector];
            }
            else if (typeof selector === 'function') { // function
                this.ready(selector);
            }
            else if (selector === window) { // window
                this.nodes = [window];
            }
            else { // document
                this.nodes = [document];
            }

            // custom plugins
            $.each($.fn, (handler, method) => {
                this[method] = handler;
            });
        }

        static isset(input) {
            return typeof input !== 'undefined';
        }

        static post(url, data, settings) {
            var settings = Object.assign({}, {
                dataType: 'json',
                contentType: 'application/json',
                headers: {},
                done: () => {},
                fail: () => {}
            }, settings);

            settings.headers['Content-Type'] = settings.contentType;

            data = ('application/json' === settings.contentType) ? JSON.stringify(data) : $.toEncoded(data);

            fetch(url, {
                method: 'POST',
                headers: settings.headers,
                body: data
            })
            .then(resp => {
                if (resp.ok) {
                    return resp[settings.dataType]();
                }

                return settings.fail(resp.status + ' - ' + resp.statusText);
            })
            .then(result => settings.done(result))
            .catch(err => settings.fail(err));
        }

        static toEncoded(obj, prefix, out) {
            var out = out || [];
            var prefix = prefix || '';

            if (Array.isArray(obj)) {
                if (obj.length) {
                    obj.forEach((val) => {
                        $.toEncoded(val, prefix + '[]', out);
                    });
                }
                else {
                    $.toEncoded('', prefix, out);
                }
            }
            else if (typeof obj === 'object' && obj !== null) {
                Object.keys(obj).forEach((key) => {
                    var new_prefix = prefix ? prefix + '[' + key + ']' : key;
                    $.toEncoded(obj[key], new_prefix, out);
                });
            }
            else {
                out.push(encodeURIComponent(prefix) + '=' + encodeURIComponent(obj));
            }

            return out.join('&');
        }

        static forEach(items, callback) {
            if (typeof items === 'object' && items !== null) {
                if (Array.isArray(items)) {
                    items.forEach((val, key) => callback.bind(val)(val, key));
                }
                else {
                    Object.keys(items).forEach(key => {
                        var val = items[key];
                        callback.bind(val)(val, key);
                    });
                }
            }

            return items;
        }

        isValidSelector(string) {
            try {
                document.createDocumentFragment().querySelector(string);
            }
            catch(err) {
                return false;
            }
            return true;
        }

        clone() {
            return $(this.nodes);
        }

        len() {
            return this.nodes.length;
        }

        each(callback) {
            this.nodes.forEach((node, key) => {
                let func = callback.bind(node); // set "this"
                func(node, key);
            });

            return this;
        }

        ready(callback) {
            if (typeof callback !== 'function') return;

            if (document.readyState === 'complete') {
                return callback();
            }

            document.addEventListener('DOMContentLoaded', callback, false);
        }

        addClass(className) {
            this.each(node => node.classList.add(className));
            return this;
        }

        removeClass(className) {
            this.each(node => node.classList.remove(className));
            return this;
        }

        hasClass(className) {
            return $.isset(this.nodes.find(node => node.classList.contains(className)));
        }

        toggleClass(className) {
            this.each(node => node.classList.toggle(className));
            return this;
        }

        is(selector) {
            for (let i = 0; i < this.len(); i++) { // forEach prevents loop exiting
                if (this.nodes[i].matches(selector)) {
                    return true;
                }
            }
            return false;
        }

        find(selector) {
            var selector = selector.replace(':selected', ':checked');
            let nodes = [];
            let clone = this.clone();

            clone.each(node => {
                nodes = nodes.concat(Array.from(node.querySelectorAll(selector)));
            });

            clone.nodes = nodes;
            return clone;
        }

        first() {
            let clone = this.clone();
            if (clone.len()) {
                clone.nodes = this.nodes.slice(0, 1);
            }
            return clone;
        }

        last() {
            let clone = this.clone();
            if (clone.len()) {
                clone.nodes = this.nodes.slice(-1);
            }
            return clone;
        }

        prev(selector) {
            let nodes = [];
            let clone = this.clone();

            clone.each(node => {
                let sibling = node.previousElementSibling;

                while (sibling) {
                    if (!$.isset(selector) || sibling.matches(selector)) break;
                    sibling = sibling.previousElementSibling;
                }

                if (sibling) {
                    nodes.push(sibling);
                }
            });

            clone.nodes = nodes;
            return clone;
        }

        next(selector) {
            let nodes = [];
            let clone = this.clone();

            clone.each(node => {
                let sibling = node.nextElementSibling;

                while (sibling) {
                    if (!$.isset(selector) || sibling.matches(selector)) break;
                    sibling = sibling.nextElementSibling;
                }

                if (sibling) {
                    nodes.push(sibling);
                }
            });

            clone.nodes = nodes;
            return clone;
        }

        prepend(html) {
            this.each(node => node.insertAdjacentHTML('afterbegin', html));
            return this;
        }

        append(html) {
            this.each(node => node.insertAdjacentHTML('beforeend', html));
            return this;
        }

        parents(selector) {
            let parents = [];
            let clone = this.clone();

            clone.each(node => {
                let parent = node.parentNode;
                while (parent && parent !== document) {
                    if (parent.matches(selector)) parents.push(parent);
                    parent = parent.parentNode;
                }
            });

            clone.nodes = [...new Set(parents)]; // remove dupes
            return clone;
        }

        closest(selector) {
            let nodes = [];
            let clone = this.clone();

            clone.each(node => {
                let closest = node.closest(selector);

                if (closest) {
                    nodes.push(closest);
                }
            });

            clone.nodes = nodes;
            return clone;
        }

        remove() {
            this.each(node => node.remove());
            return this;
        }

        on(eventName, selector, callback) {
            if (!$.isset(selector)) return;
            if (!$.isset(callback)) {
                var callback = selector;
                var selector = null;
            }

            // Reusable callback
            var checkForMatch = (e) => {
                if (null === selector || e.target.matches(selector)) {
                    callback.bind(e.target)(e);
                }
                else if (e.target.closest(selector)) {
                    var $this = e.target.closest(selector);
                    callback.bind($this)(e);
                }
            };

            this.each(node => {

                // Attach a unique ID to each node
                if (!$.isset(node._id)) {
                    node._id = $.event.count;
                    $.event.store[$.event.count] = node;
                    $.event.count++;
                }

                var id = node._id;

                // Store the raw callback, needed for .off()
                checkForMatch._str = callback.toString();

                if (!$.isset($.event.map[id])) {
                    $.event.map[id] = {};
                }
                if (!$.isset($.event.map[id][eventName])) {
                    $.event.map[id][eventName] = {};
                }
                if (!$.isset($.event.map[id][eventName][selector])) {
                    $.event.map[id][eventName][selector] = [];
                }

                // Use $.event.map to store event references
                // removeEventListener needs named callbacks, so we're creating
                // one for every handler
                let length = $.event.map[id][eventName][selector].push(checkForMatch);

                node.addEventListener(eventName, $.event.map[id][eventName][selector][length - 1]);
            });

            return this;
        }

        off(eventName, selector, callback) {
            if (!$.isset(callback)) {
                var callback = selector;
                var selector = null;
            }

            this.each(node => {
                var id = node._id;

                $.each($.event.map[id], (selectors, theEventName) => {
                    $.each(selectors, (callbacks, theSelector) => {
                        $.each(callbacks, (theCallback, index) => {
                            if (
                                (!eventName || theEventName === eventName) &&
                                (!selector || theSelector === selector) &&
                                (!callback || theCallback._str === callback.toString())
                            ) {
                                node.removeEventListener(theEventName, $.event.map[id][theEventName][theSelector][index]);
                                delete $.event.map[id][theEventName][theSelector][index];
                            }
                        });
                    });
                });
            });

            return this;
        }

        trigger(eventName, extraData) {
            this.each(node => node.dispatchEvent(new CustomEvent(eventName, {
                detail: extraData,
                bubbles: true
            })));
            return this;
        }

        attr(attributeName, value) {
            if (!$.isset(value)) {
                return (this.len()) ? this.nodes[0].getAttribute(attributeName) : null;
            }

            this.each(node => node.setAttribute(attributeName, value));
            return this;

        }

        data(key, value) {
            if (!$.isset(value)) {
                return (this.len()) ? this.nodes[0]._fdata[key] : null;
            }

            this.each(node => node._fdata[key] = value);
            return this;
        }

        html(htmlString) {
            if (!$.isset(htmlString)) {
                return (this.len()) ? this.nodes[0].innerHTML : null;
            }

            this.each(node => node.innerHTML = htmlString);
            return this;
        }

        text(textString) {
            if (!$.isset(textString)) {
                return (this.len()) ? this.nodes[0].textContent : null;
            }
            else {
                this.each(node => node.textContent = textString);
                return this;
            }
        }

        val(value) {
            if (!$.isset(value)) {
                if (this.len()) {
                    var field = this.nodes[0];
                    if (field.nodeName.toLowerCase() === 'select' && field.multiple) {
                        return [...field.options].filter((x) => x.selected).map((x) => x.value);
                    }
                    return field.value;
                }
                return null;
            }
            else {
                this.each(node => node.value = value);
                return this;
            }
        }
    }

    var $ = selector => new fUtil(selector);

    // Set object methods
    $.fn = {};
    $.post = fUtil.post;
    $.isset = fUtil.isset;
    $.each = fUtil.forEach;
    $.toEncoded = fUtil.toEncoded;
    $.event = {map: {}, store: [], count: 0};
    return $;
})();
