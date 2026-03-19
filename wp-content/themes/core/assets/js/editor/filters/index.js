/**
 * @module editor-filters
 *
 * @description Registers editor-level UI workarounds.
 */

import { __ } from '@wordpress/i18n';

const panelHeadingSelector = 'h2.components-heading';
const shadowHeading = 'Shadow';
const borderAndShadowHeading = __( 'Border & Shadow', 'tribe' );

const relabelShadowPanelHeading = () => {
	const headings = document.querySelectorAll( panelHeadingSelector );

	headings.forEach( ( heading ) => {
		if ( heading.textContent?.trim() !== shadowHeading ) {
			return;
		}

		heading.textContent = borderAndShadowHeading;
	} );
};

const init = () => {
	// TODO: As of WP 6.9.4, this workaround is needed for a Gutenberg label bug.
	// Ref: https://github.com/WordPress/gutenberg/issues/60192
	// Revisit and remove if core fixes it or if this causes editor interference.
	relabelShadowPanelHeading();

	const observer = new MutationObserver( relabelShadowPanelHeading );
	observer.observe( document.body, { childList: true, subtree: true } );
};

export default init;
