window.fTip = (() => {

    var qs = (selector) => document.querySelector(selector);

    class fTip {

        constructor(selector, options) {
            let that = this;

            var defaults = {
                content: (node) => node.getAttribute('title')
            };

            that.settings = Object.assign({}, defaults, options);

            var inputs = [];

            if ('string' === typeof selector) {
                var inputs = Array.from(document.querySelectorAll(selector));
            }
            else if (selector instanceof Node) {
                var inputs = [selector];
            }
            else if (Array.isArray(selector)) {
                var inputs = selector;
            }

            if (null === qs('.ftip-wrap')) {
                that.buildHtml();
                that.bindEvents();
            }

            inputs.forEach(function(input) {
                that.input = input;
                input.ftip = that;
                input.classList.add('ftip-enabled');
            });
        }

        open() {
            let input = this.input;
            let wrap = qs('.ftip-wrap');

            wrap.innerHTML = this.settings.content(input);
            wrap.classList.add('active');

            let wrapBounds = wrap.getBoundingClientRect();
            let inputBounds = input.getBoundingClientRect();
            let top = window.pageYOffset + inputBounds.top;
            let left = window.pageXOffset + inputBounds.right;
            let centered = ((inputBounds.height - wrapBounds.height) / 2);

            wrap.style.top = (top + centered) + 'px';
            wrap.style.left = (left + 10) + 'px';
        }

        buildHtml() {
            let html = '<div class="ftip-wrap"></div>';
            document.body.insertAdjacentHTML('beforeend', html);
        }

        bindEvents() {
            var that = this;
            let wrap = qs('.ftip-wrap');

            var delayHandler = () => {
                that.delay = setTimeout(() => {
                    wrap.classList.remove('active');
                }, 250);
            };

            that.on('mouseover', '.ftip-enabled', function(e) {
                clearTimeout(that.delay);
                this.ftip.open();
            });

            that.on('mouseout', '.ftip-enabled', delayHandler);
            that.on('mouseout', '.ftip-wrap', delayHandler);
            that.on('mouseover', '.ftip-wrap', () => {
                clearTimeout(that.delay);
            });
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
    }

    var $ = (selector, options) => new fTip(selector, options);

    return $;
})();