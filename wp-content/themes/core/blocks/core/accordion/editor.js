import { __ } from '@wordpress/i18n';
import createWPControls from 'utils/create-wp-controls';

const settings = {
	attributes: {
		enableFaqSchema: {
			type: 'boolean',
			default: false,
		},
	},
	blocks: [ 'core/accordion' ],
	controls: [
		{
			attribute: 'enableFaqSchema',
			defaultValue: false,
			helpText: __(
				"Generate FAQPage structured data from this accordion's questions and answers.",
				'tribe'
			),
			label: __( 'Enable FAQ Schema', 'tribe' ),
			panel: __( 'Structured Data', 'tribe' ),
			type: 'toggle',
		},
	],
};

createWPControls( settings );
