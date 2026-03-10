import { __ } from '@wordpress/i18n';
import createWPControls from 'utils/create-wp-controls';

const settings = {
	attributes: {
		useBalancedText: {
			type: 'boolean',
		},
		useHangingPunctuation: {
			type: 'boolean',
		},
		useDivTag: {
			type: 'boolean',
		},
	},
	blocks: [ 'core/heading' ],
	controls: [
		{
			applyClass: 'has-balanced-text',
			attribute: 'useBalancedText',
			defaultValue: false,
			helpText: __(
				'Distribute text evenly across multiple lines.',
				'tribe'
			),
			label: __( 'Use Balanced Text', 'tribe' ),
			type: 'toggle',
		},
		{
			applyClass: 'has-hanging-punctuation',
			attribute: 'useHangingPunctuation',
			defaultValue: false,
			helpText: __(
				'Align opening punctuation mark outside text block.',
				'tribe'
			),
			label: __( 'Use Hanging Punctuation', 'tribe' ),
			type: 'toggle',
		},
		{
			applyClass: 'is-decorative-heading',
			attribute: 'useDivTag',
			defaultValue: false,
			label: __( 'Decorative Heading', 'tribe' ),
			helpText: __(
				'Uses <div> instead of heading tags for accessibility.',
				'tribe'
			),
			type: 'toggle',
		},
	],
};

createWPControls( settings );
