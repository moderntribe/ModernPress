/**
 * Per-facet clear controls.
 *
 * Radios in particular can't be deselected by clicking, so each facet gets its
 * own clear button. Buttons ship hidden and only appear where selections exist.
 */

import { clearFacetInputs } from './results';
import { syncFancyDropdowns } from './fancy-dropdown';
import { SELECTORS } from './selectors';

const hasSelections = ( facet ) =>
	[ ...facet.querySelectorAll( SELECTORS.activeFacet ) ].some(
		( control ) => control.value !== ''
	);

/**
 * @param {Element|Document} root Container to update within.
 */
export const updateFacetClearVisibility = ( root = document ) => {
	root.querySelectorAll( SELECTORS.facetClear ).forEach( ( button ) => {
		const facet = button.closest( SELECTORS.facet );

		if ( facet ) {
			button.hidden = ! hasSelections( facet );
		}
	} );
};

/**
 * @param {HTMLFormElement} form Directory form.
 */
export const bindFacetClears = ( form ) => {
	form.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( SELECTORS.facetClear );
		const facet = button?.closest( SELECTORS.facet );

		if ( ! facet ) {
			return;
		}

		event.preventDefault();
		clearFacetInputs( facet );
		syncFancyDropdowns( facet );

		// Let the form-level change handler decide whether to refresh now
		// (top / desktop sidebar) or wait for "Show results" (mobile flyout).
		facet.dispatchEvent( new Event( 'change', { bubbles: true } ) );

		// This button is about to hide itself, so hand focus back to the facet
		// instead of letting it fall to the document.
		if ( button.hidden ) {
			const target =
				facet.querySelector( SELECTORS.fancyTrigger ) ??
				facet.querySelector( SELECTORS.focusable );

			target?.focus();
		}
	} );

	form.addEventListener( 'change', () => updateFacetClearVisibility( form ) );

	updateFacetClearVisibility( form );
};
