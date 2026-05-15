/**
 * Nummy.js - A (very) lightweight number formatter
 * @link https://github.com/mgibbs189/nummy
 */
(function() {
    function isValid(input) {
        return !isNaN(parseFloat(input)) && isFinite(input);
    }

    function toFixed(value, precision) {
        var pow = Math.pow(10, precision);
        return (Math.round(value * pow) / pow).toFixed(precision);
    }

    class Nummy {
        constructor(value) {
            this._value = isValid(value) ? value : 0;
        }

        format(format, opts) {
            var value = this._value,
                negative = false,
                precision = 0,
                valueStr = '',
                wholeStr = '',
                decimalStr = '',
                abbr = '';

            var opts = Object.assign({}, {
                'thousands_separator': ',',
                'decimal_separator': '.'
            }, opts);

            if (-1 < format.indexOf('a')) {
                var abbrevs = ['K', 'M', 'B', 't', 'q', 'Q'];
                var exp = Math.floor(Math.log(Math.abs(value)) * Math.LOG10E); // log10 polyfill
                var nearest_exp = (exp - (exp % 3)); // nearest exponent divisible by 3

                if (3 <= exp) {
                    value = value / Math.pow(10, nearest_exp);
                    abbr += abbrevs[Math.floor(exp / 3) - 1];
                }

                format = format.replace('a', '');
            }

            // Check for decimals format
            if (-1 < format.indexOf('.')) {
                precision = format.split('.')[1].length;
            }

            value = toFixed(value, precision);
            valueStr = value.toString();

            // Handle negative number
            if (value < 0) {
                negative = true;
                value = Math.abs(value);
                valueStr = valueStr.slice(1);
            }

            wholeStr = valueStr.split('.')[0] || '';
            decimalStr = valueStr.split('.')[1] || '';

            // Handle decimals
            decimalStr = (0 < precision && '' != decimalStr) ? '.' + decimalStr : '';

            // Use thousands separators
            if (-1 < format.indexOf(',')) {
                wholeStr = wholeStr.replace(/(\d)(?=(\d{3})+(?!\d))/g, '$1,');
            }

            var output = (negative ? '-' : '') + wholeStr + decimalStr + abbr;

            output = output.replace(/\./g, '{d}');
            output = output.replace(/\,/g, '{t}');
            output = output.replace(/{d}/g, opts.decimal_separator);
            output = output.replace(/{t}/g, opts.thousands_separator);

            return output;
        }
    }

    window.nummy = function(input) {
        return new Nummy(input);
    }
})();
