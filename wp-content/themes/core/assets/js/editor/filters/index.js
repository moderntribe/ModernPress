/**
 * @module editor-filters
 *
 * @description Registers editor-level filters.
 */

import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

const shadowLabel = 'Shadow';
const borderAndShadowLabel = __('Border & Shadow', 'tribe');

const relabelShadowLabel = ( translation, text ) => {
	if ( text !== shadowLabel ) {
		return translation;
	}

	return borderAndShadowLabel;
};

const init = () => {
	// TODO: As of WP 6.9.4, this workaround is needed for a Gutenberg [label bug](https://github.com/WordPress/gutenberg/issues/60192).
	// The bug is not directly related to the issue in that report, but rather the fact that enabling border or shadow via theme.json breaks that label logic.
	// Revisit and remove if core fixes it or if this causes editor interference.
	addFilter(
		'i18n.gettext',
		'tribe/relabel-shadow-label',
		relabelShadowLabel
	);
};

export default init;
