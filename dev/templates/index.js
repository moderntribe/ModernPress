const { join } = require( 'path' );

module.exports = {
	blockTemplatesPath: join( __dirname, 'block' ),
	defaultValues: {
		attributes: {
			exampleTextControl: {
				type: 'string',
				default: '',
			},
		},
		dashicon: 'block-default',
		supports: {
			html: false,
			alignCustom: [ 'wide', 'grid', 'full' ],
			spacing: {
				margin: true,
				padding: true,
			},
		},
		textdomain: 'tribe',
		viewScript: 'file:./view.js',
	},
	variants: {
		static: {},
		dynamic: {
			render: 'file:./render.php',
		},
	},
};
