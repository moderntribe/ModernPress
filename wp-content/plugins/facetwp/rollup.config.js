import terser from '@rollup/plugin-terser';
import multiEntry from '@rollup/plugin-multi-entry';
import buble from '@rollup/plugin-buble';

export default [{
    input: [
        'assets/vendor/fUtil/fUtil.js',
        'assets/js/src/event-manager.js',
        'assets/js/src/front.js',
        'assets/js/src/front-facets.js'
    ],
    output: {
        file: 'assets/js/dist/front.min.js',
        format: 'iife'
    },
    plugins: [
        multiEntry(),
        terser()
    ]
},
{
    input: 'assets/js/src/admin.js',
    output: {
        file: 'assets/js/dist/admin.min.js',
        format: 'iife'
    },
    plugins: [
        buble( { transforms: { asyncAwait: false } } )
    ]
},
{
    input: 'assets/vendor/fDate/fDate.js',
    output: {
        file: 'assets/vendor/fDate/fDate.min.js',
        format: 'iife'
    },
    plugins: [
        buble(),
        terser()
    ]
},
{
    input: 'assets/vendor/nummy/nummy.js',
    output: {
        file: 'assets/vendor/nummy/nummy.min.js',
        format: 'iife'
    },
    plugins: [
        buble(),
        terser()
    ]
}]
