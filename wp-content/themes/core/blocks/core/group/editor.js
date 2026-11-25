import { __ } from '@wordpress/i18n';
import createWPControls from 'utils/create-wp-controls';

const settings = {
	attributes: {
		hasTransparentBackground: {
			type: 'boolean',
		},
		isSticky: {
			type: 'boolean',
		},
	},
	blocks: [ 'core/group' ],
	controls: [
		{
			applyClass: 'has-transparent-background',
			attribute: 'hasTransparentBackground',
			defaultValue: false,
			helpText: __(
				'If a theme is applied, this setting allows you to have that theme enabled for elements contained in the block, but remove the background color. This is useful for content sections on top of images.',
				'tribe'
			),
			label: __( 'Has Transparent Background', 'tribe' ),
			type: 'toggle',
		},
		{
			applyClass: 'tribe-is-sticky',
			attribute: 'isSticky',
			defaultValue: false,
			helpText: __(
				'Sticks this Group block below the masthead on scroll.',
				'tribe'
			),
			label: __( 'Stick Group on Scroll', 'tribe' ),
			type: 'toggle',
		},
	],
};

createWPControls( settings );
