const wordpress = require( '@wordpress/eslint-plugin' );

module.exports = [
	...wordpress.configs.recommended,
	{
		rules: {
			'no-console': 0,
			'import/no-unresolved': 0,
			'import/no-extraneous-dependencies': 0,
		},
	},
	{
		ignores: [ 'wp-content/themes/core/dist/' ],
	},
];
