/**
 * @module type
 *
 * @description pulls animation type settings from theme.json or sets default settings
 *
 * theme.json settings:
 *
 * "animationType": [
 * 		{ "label": "None", "value": "none" },
 * 		{ "label": "Fade In", "value": "fade-in" }
 * ],
 */

import { __ } from '@wordpress/i18n';
import themeJson from '../../../../../theme.json';

const type = themeJson?.settings?.animationType ?? [
	{ label: __( 'None', 'tribe' ), value: 'none' },
	{ label: __( 'Fade In', 'tribe' ), value: 'fade-in' },
	{ label: __( 'Scale on Hover', 'tribe' ), value: 'scale-on-hover' },
	{ label: __( 'Pulse', 'tribe' ), value: 'pulse' },
];

export default type;
