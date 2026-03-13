window.fDate = (() => {

    var qs = (selector) => document.querySelector(selector);
    var isset = (input) => 'undefined' !== typeof input;
    var ymd = (...args) => {
        var d = new Date(...args);
        var zeroed = (num) => (num > 9) ? num : '0' + num;
        // toJSON() produces unexpected results due to timezones
        return d.getFullYear() + '-' + zeroed(d.getMonth() + 1) + '-' + zeroed(d.getDate());
    };

    class fDate {

        constructor(selector, options) {
            let that = this;

            var defaults = {
                i18n: {
                    weekdays_short: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                    months_short: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                    clearText: 'Clear',
                    firstDayOfWeek: 0
                },
                minDate: '',
                maxDate: '',
                altFormat: '',
                onChange: null
            };

            that.settings = Object.assign({}, defaults, options);

            if ('string' === typeof selector) {
                var inputs = document.querySelectorAll(selector);
            }
            else if (selector instanceof Node) {
                var inputs = [selector];
            }
            else {
                var inputs = selector;
            }

            if (inputs.length) {
                inputs.forEach(function(input) {
                    input.setAttribute('readonly', 'readonly');

                    if ('' !== that.settings.altFormat) {
                        that.el = input;

                        let altInput = input.cloneNode();
                        altInput.classList.add('fdate-alt-input');
                        altInput.value = that.getAltDate();
                        altInput._input = input;

                        input._altInput = altInput;
                        input.setAttribute('type', 'hidden');
                        input.parentNode.insertBefore(altInput, input.nextSibling); // append()
                    }

                    input.classList.add('fdate-input');
                    input._input = input;
                    input.fdate = {
                        settings: that.settings,
                        refresh() {
                            input.click();
                        },
                        open() {
                            input.click();
                        },
                        close() {
                            that.setCalVisibility('hide');
                        },
                        clear() {
                            input.value = '';

                            if (isset(input._altInput)) {
                                input._altInput.value = '';
                            }

                            that.triggerEvent('onChange');
                        },
                        destroy() {
                            input.classList.remove('fdate-input');
                            delete input._altInput;
                            delete input._input;
                            delete input.fdate;
                        }
                    };
                });
            }

            if (null === qs('.fdate-wrap')) {
                this.initCalendar();
                this.bindEvents();
            }
        }

        initCalendar() {
            var html = `
            <div class="fdate-wrap">
                <div class="fdate-nav">
                    <div class="fdate-nav-prev">&lt;</div>
                    <div class="fdate-nav-label" tabindex="-1"></div>
                    <div class="fdate-nav-next">&gt;</div>
                </div>
                <div class="fdate-grid"></div>
                <div class="fdate-clear" tabindex="-1">${this.settings.i18n.clearText}</div>
            </div>
            `;

            document.body.insertAdjacentHTML('beforeend', html);
        }

        setInput(input) {
            this.el = input;
            this.mode = 'day';
            this.settings = input.fdate.settings;

            this.setDateBounds();

            // valid YYYY-MM-DD?
            if (null !== input.value.match(/^\d{4}-\d{2}-\d{2}$/)) {
                var date_str = input.value;
            }
            // use the min date or today, whichever is higher
            else {
                var today = ymd();
                var date_str = (this.min.str < today) ? today : this.min.str;
            }

            // rewind the calendar if beyond the maxDate
            if (date_str < this.max.str) {
                var temp_date = new Date(date_str + 'T00:00');
                this.year = temp_date.getFullYear();
                this.month = temp_date.getMonth();
            }
            else {
                this.year = this.max.year;
                this.month = this.max.month;
            }
        }

        setDateBounds() {
            let min = this.settings.minDate || '1000-01-01';
            let max = this.settings.maxDate || '3000-01-01';
            let minDate = new Date(min + 'T00:00');
            let maxDate = new Date(max + 'T00:00');

            this.min = {
                year: minDate.getFullYear(),
                month: minDate.getMonth(),
                str: min
            };

            this.max = {
                year: maxDate.getFullYear(),
                month: maxDate.getMonth(),
                str: max
            };
        }

        isInBounds(val) {
            if ('year' == this.mode) {
                let year = parseInt(val);

                if (year < this.min.year || year > this.max.year) {
                    return false;
                }
            }
            else if ('month' == this.mode) {
                let month = parseInt(val);
                let valStr = ymd(this.year, month).substr(0, 7);
                let monthMin = this.min.str.substr(0, 7);
                let monthMax = this.max.str.substr(0, 7);

                if (valStr < monthMin || valStr > monthMax) {
                    return false;
                }
            }
            else if ('day' == this.mode) {
                if (val < this.min.str || val > this.max.str) {
                    return false;
                }
            }

            return true;
        }

        isNavAllowed(type) {
            if ('year' == this.mode) {
                let decade = parseInt(this.year.toString().substr(0, 3) + '0');

                return ('next' == type) ?
                    decade < parseInt(this.max.str.substr(0, 4)) :
                    decade > parseInt(this.min.str.substr(0, 4));
            }
            else if ('month' == this.mode) {
                return ('next' == type) ?
                    ymd(this.year + 1, 0, 0) < this.max.str :
                    ymd(this.year, 0) > this.min.str;
            }
            else if ('day' == this.mode) {
                return ('next' == type) ?
                    ymd(this.year, this.month + 1, 0) < this.max.str :
                    ymd(this.year, this.month) > this.min.str;
            }
        }

        setDisplay(which) {
            var that = this;

            this.mode = which;

            qs('.fdate-grid').classList.remove('grid-day');

            // show or hide the nav arrows
            qs('.fdate-nav-prev').classList.add('disabled');
            qs('.fdate-nav-next').classList.add('disabled');

            if (that.isNavAllowed('prev')) {
                qs('.fdate-nav-prev').classList.remove('disabled');
            }

            if ( that.isNavAllowed('next')) {
                qs('.fdate-nav-next').classList.remove('disabled');
            }

            // month
            if ('month' == which) {
                var output = '';
                this.settings.i18n.months_short.forEach(function(item, index) {
                    var css = that.isInBounds(index) ? ' inner' : ' disabled';
                    output += '<div class="fdate-month' + css + '" data-value="' + index + '" tabindex="-1">' + item + '</div>';
                });

                qs('.fdate-grid').innerHTML = output;
                qs('.fdate-nav-label').innerHTML = this.year;
            }
            // year
            else if ('year' == which) {
                var output = '';
                var decade = parseInt(this.year.toString().substr(0, 3) + '0');
                for (var i = 0; i < 10; i++) {
                    var css = that.isInBounds(decade + i) ? ' inner' : ' disabled';
                    output += '<div class="fdate-year' + css + '" data-value="' + (decade + i) + '" tabindex="-1">' + (decade + i) + '</div>';
                }

                qs('.fdate-grid').innerHTML = output;

                var prefix = this.year.toString().substr(0, 3);
                var decade = prefix + '0 - ' + prefix + '9';
                qs('.fdate-nav-label').innerHTML = decade;
            }
            // day
            else {
                qs('.fdate-grid').classList.add('grid-day');

                var output = '';
                var days = this.generateDays(this.year, this.month);
                days.forEach(function(item) {
                    output += '<div class="fdate-day' + item.class + '" data-value="' + item.value + '" tabindex="-1">' + item.text + '</div>';
                });

                qs('.fdate-grid').innerHTML = output;
                qs('.fdate-nav-label').innerHTML = this.settings.i18n.months[this.month] + ' ' + this.year;
            }
        }

        generateDays(year, month) {
            let that = this;
            var output = [];
            let i18n = that.settings.i18n;
            let weekdays = i18n.weekdays_short;
            let firstDayOfWeek = i18n.firstDayOfWeek; // 0 = Sunday
            let firstDayNum = new Date(year, month).getDay(); // between 0 and 6
            let offset = firstDayNum - firstDayOfWeek;
            offset = (offset < 0) ? 7 + offset : offset; // negative offset (e.g. August 2021)
            let num_days = new Date(year, month + 1, 0).getDate();
            let today = ymd();

            // shift weekdays according to firstDayOfWeek
            if (0 < firstDayOfWeek) {
                let temp = JSON.parse(JSON.stringify(weekdays));
                let append = temp.splice(0, firstDayOfWeek);
                weekdays = temp.concat(append);
            }

            // get weekdays
            weekdays.forEach(function(item) {
                output.push({
                    text: item,
                    value: '',
                    class: ' weekday'
                });
            });

            // get days from the previous month
            if (0 < offset) {
                let year_prev = (0 == month) ? year - 1 : year;
                let month_prev = (0 == month) ? 11 : month - 1;
                let num_days_prev = new Date(year_prev, month_prev + 1, 0).getDate();

                for (var i = (num_days_prev - offset + 1); i <= num_days_prev; i++) {
                    var val = ymd(year_prev, month_prev, i);
                    var css = that.isInBounds(val) ? '' : ' disabled';
                    output.push({
                        text: i,
                        value: val,
                        class: css
                    });
                }
            }

            // get days from the current month
            for (var i = 1; i <= num_days; i++) {
                var val = ymd(year, month, i);

                if ( that.isInBounds(val)) {
                    var css = ' inner';
                    css += (val == today) ? ' today' : '';
                    css += (val == this.el.value) ? ' selected' : '';
                }
                else {
                    var css = ' disabled';
                }

                output.push({
                    text: i,
                    value: val,
                    class: css
                });
            }

            // get days from the next month
            let year_next = (11 == month) ? year + 1 : year;
            let month_next = (11 == month) ? 0 : month + 1;
            let num_filler = 42 - num_days - offset;

            for (var i = 1; i <= num_filler; i++) {
                var val = ymd(year_next, month_next, i);
                var css = that.isInBounds(val) ? '' : ' disabled';
                output.push({
                    text: i,
                    value: val,
                    class: css
                });
            }

            return output;
        }

        adjustDate(increment, unit) {
            var temp_year = ('year' == unit) ? this.year + increment : this.year;
            var temp_month = ('month' == unit) ? this.month + increment : this.month;
            var temp_date = new Date(temp_year, temp_month);

            this.year = temp_date.getFullYear();
            this.month = temp_date.getMonth();
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

        getAltDate() {
            let that = this;

            if ('' === that.el.value) {
                return '';
            }

            let date_array = that.el.value.split('-');
            let format_array = that.settings.altFormat.split('');
            let output = '';

            let escaped = false;
            format_array.forEach(function(token) {
                if ('\\' === token) {
                    escaped = true;
                    return;
                }

                output += escaped ? token : that.parseDateToken(token, date_array);
                escaped = false;
            });

            return output;
        }

        parseDateToken(token, date_array) {
            let i18n = this.settings.i18n;

            let tokens = {
                'd': () => date_array[2],
                'j': () => parseInt(date_array[2]),
                'm': () => date_array[1],
                'n': () => parseInt(date_array[1]),
                'F': () => i18n.months[parseInt(date_array[1]) - 1],
                'M': () => i18n.months_short[parseInt(date_array[1]) - 1],
                'y': () => date_array[0].substring(2),
                'Y': () => date_array[0]
            };

            return isset(tokens[token]) ? tokens[token]() : token;
        }

        setPosition(input) {
            let wrap = qs('.fdate-wrap');
            let inputBounds = input.getBoundingClientRect();
            let calendarWidth = wrap.getBoundingClientRect().width;
            let calendarHeight = wrap.getBoundingClientRect().height;
            let distanceFromRight = document.body.clientWidth - inputBounds.left;
            let distanceFromBottom = document.body.clientHeight - inputBounds.bottom;
            let showOnTop = (distanceFromBottom < calendarHeight && inputBounds.top > calendarHeight);
            let showOnLeft = (distanceFromRight < calendarWidth && inputBounds.left > calendarWidth);

            let top = window.pageYOffset + inputBounds.top + (!showOnTop ? input.offsetHeight + 2 : -calendarHeight - 2);
            let left = window.pageXOffset + inputBounds.left;
            let right = window.pageXOffset + inputBounds.right - calendarWidth;
            let pixels = showOnLeft ? right : left;

            wrap.style.position = 'absolute';
            wrap.style.top = top + 'px';
            wrap.style.left = pixels + 'px';
        }

        setCalVisibility(which) {
            var wrap = qs('.fdate-wrap');

            if ('hide' === which) {
                if (wrap.classList.contains('opened')) {
                    wrap.classList.remove('opened');
                }
            }
            else {
                if (! wrap.classList.contains('opened')) {
                    wrap.classList.add('opened');
                }
            }
        }

        triggerEvent(name) {
            if (typeof this.settings[name] === 'function') {
                this.settings[name](this);
            }
        }

        bindEvents() {
            var that = this;

            that.on('click', '.fdate-day:not(.disabled):not(.weekday)', function(e) {
                that.el.value = e.target.getAttribute('data-value');

                if (isset(that.el._altInput)) {
                    that.el._altInput.value = that.getAltDate();
                }

                that.triggerEvent('onChange');
                that.setCalVisibility('hide');
                e.stopImmediatePropagation(); // important
            });

            that.on('click', '.fdate-month:not(.disabled)', function(e) {
                that.month = parseInt(e.target.getAttribute('data-value'));
                that.setDisplay('day');
                e.stopImmediatePropagation(); // important
            });

            that.on('click', '.fdate-year:not(.disabled)', function(e) {
                that.year = parseInt(e.target.getAttribute('data-value'));
                that.setDisplay('month');
                e.stopImmediatePropagation(); // important
            });

            that.on('click', '.fdate-nav-prev:not(.disabled)', function() {
                var incr = ('year' == that.mode) ? -10 : -1;
                var unit = ('day' == that.mode) ? 'month' : 'year';

                that.adjustDate(incr, unit);
                that.setDisplay(that.mode);
            });

            that.on('click', '.fdate-nav-next:not(.disabled)', function() {
                var incr = ('year' == that.mode) ? 10 : 1;
                var unit = ('day' == that.mode) ? 'month' : 'year';

                that.adjustDate(incr, unit);
                that.setDisplay(that.mode);
            });

            that.on('click', '.fdate-nav-label', function() {
                if ('day' == that.mode) {
                    that.setDisplay('month');
                }
                else if ('month' == that.mode) {
                    that.setDisplay('year');
                }
                else if ('year' == that.mode) {
                    that.setDisplay('day');
                }
            });

            that.on('click', '.fdate-clear', function() {
                that.el.fdate.clear();
            });

            that.on('click', '*', function(e) {
                var is_input = e.target.classList.contains('fdate-input') || e.target.classList.contains('fdate-alt-input');
                var is_cal = (null !== e.target.closest('.fdate-wrap'));
                var is_clear = e.target.classList.contains('fdate-clear');

                if (is_input || (is_cal && ! is_clear)) {
                    that.setCalVisibility('show');

                    // set position and render calendar
                    if (is_input) {
                        let visibleInput = e.target._altInput || e.target;
                        that.setInput(e.target._input);
                        that.setDisplay('day');
                        that.setPosition(visibleInput);
                    }
                }
                else {
                    that.setCalVisibility('hide');
                }
            });

            // a11y support
            window.addEventListener('keyup', function(e) {
                if ('Tab' === e.key) {
                    if (e.target.classList.contains('fdate-input') || e.target.classList.contains('fdate-alt-input')) {
                        e.target._input.click();
                    }
                    else {
                        that.setCalVisibility('hide');
                    }
                }
            });

            window.addEventListener('keydown', function(e) {
                if ('Enter' === e.key) {
                    if (e.target.closest('.fdate-grid')) {
                        qs('.fdate-nav-label').focus();
                    }
                    if (e.target.closest('.fdate-wrap')) {
                        e.target.click();
                    }
                }
                else if ('Escape' === e.key) {
                    if (e.target.closest('.fdate-wrap') || e.target.classList.contains('fdate-input') || e.target.classList.contains('fdate-alt-input')) {
                        that.el.fdate.close();
                    }
                }
                else if ('ArrowUp' === e.key) {
                    if (e.target.classList.contains('fdate-input') || e.target.classList.contains('fdate-alt-input')) { // from input
                        qs('.fdate-clear').focus();
                        e.preventDefault();
                    }
                    else if (e.target.classList.contains('fdate-nav-label')) {
                        that.el.focus();
                        e.preventDefault();
                    }
                    else if (e.target.classList.contains('fdate-clear')) {
                        let days = document.querySelectorAll('.fdate-day.inner');
                        let item = (days.length) ? days[days.length - 1] : qs('.fdate-nav-label');
                        item.focus();

                        e.preventDefault();
                    }
                    else if (e.target.closest('.fdate-grid')) {
                        let offset = ('day' === that.mode) ? -7 : -4;
                        let el = that.getSibling(e.target, offset);

                        if (el) {
                            el.focus();
                        }
                        else {
                            qs('.fdate-nav-label').focus();
                        }
                        e.preventDefault();
                    }
                }
                else if ('ArrowDown' === e.key) {
                    if (e.target.classList.contains('fdate-input') || e.target.classList.contains('fdate-alt-input')) { // from input
                        let selected = qs('.fdate-grid .selected');
                        let today = qs('.fdate-grid .today');

                        if (selected) {
                            selected.focus();
                        }
                        else if (today) {
                            today.focus();
                        }
                        else {
                            qs('.fdate-nav-label').focus();
                        }

                        e.preventDefault();
                    }
                    else if (e.target.classList.contains('fdate-nav-label')) { // from nav
                        qs('.fdate-grid .inner').focus();
                        e.preventDefault();
                    }
                    else if (e.target.classList.contains('fdate-clear')) {
                        that.el.focus();
                        e.preventDefault();
                    }
                    else if (e.target.closest('.fdate-grid')) { // from grid
                        let offset = ('day' === that.mode) ? 7 : 4;
                        let el = that.getSibling(e.target, offset);

                        if (el) {
                            el.focus();
                        }
                        else {
                            qs('.fdate-clear').focus();
                        }
                        e.preventDefault();
                    }
                }
                else if ('ArrowLeft' === e.key) {
                    if (e.target.classList.contains('fdate-nav-label')) { // into the past
                        qs('.fdate-nav-prev').click();
                        e.preventDefault();
                    }
                    if (e.target.closest('.fdate-grid')) { // previous grid item
                        let prev = e.target.previousElementSibling;
                        if (prev && prev.classList.contains('inner')) {
                            prev.focus();
                        }
                        else {
                            let days = document.querySelectorAll('.fdate-day.inner');
                            days[days.length - 1].focus(); // last valid day of month
                        }
                        e.preventDefault();
                    }
                }
                else if ('ArrowRight' === e.key) {
                    if (e.target.classList.contains('fdate-nav-label')) { // into the future
                        qs('.fdate-nav-next').click();
                        e.preventDefault();
                    }
                    if (e.target.closest('.fdate-grid')) { // next grid item
                        let next = e.target.nextElementSibling;
                        if (next && next.classList.contains('inner')) {
                            next.focus();
                        }
                        else {
                            qs('.fdate-day.inner').focus(); // first valid day of month
                        }
                        e.preventDefault();
                    }
                }
            });
        }

        getSibling(orig, offset) {
            let el = orig;
            for (var i = 0; i < Math.abs(offset); i++) {
                el = (0 < offset) ? el.nextElementSibling : el.previousElementSibling;
                if (null === el || !el.classList.contains('inner')) {
                    return null;
                }
            }

            return el;
        }
    }

    return fDate;
})();