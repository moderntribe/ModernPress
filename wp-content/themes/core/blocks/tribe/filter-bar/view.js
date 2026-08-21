/**
 * Front-end behavior for the Filter Bar block.
 */

import {
	bindFlyoutEvents,
	getFocusables,
	updateClearAllVisibility,
} from './js/flyout';
import { initDropdowns, syncDropdowns } from './js/dropdown';
import { refreshResults, bindPagination, clearFacetInputs } from './js/results';
import { bindFacetClears, updateFacetClearVisibility } from './js/facet-clear';
import { SELECTORS } from './js/selectors';

/**
 * Whether the sidebar is in mobile-flyout mode (trigger visible).
 *
 * Desktop sidebar (≥992px container) hides the trigger and shows facets
 * inline — those should live-update. Mobile keeps the flyout closed until
 * "Show results".
 *
 * @param {Element} element Event target inside the filter bar.
 * @return {boolean} True when the mobile flyout owns the interaction.
 */
const isMobileFlyoutControl = ( element ) => {
	const sidebar = element.closest( SELECTORS.sidebarPosition );

	if ( ! sidebar ) {
		return false;
	}

	const mobileFacets = sidebar.querySelector( SELECTORS.mobileFacets );

	return Boolean( mobileFacets && ! mobileFacets.disabled );
};

const syncClearAll = ( form ) =>
	form
		.querySelectorAll( SELECTORS.filterBar )
		.forEach( updateClearAllVisibility );

const initFormBehavior = ( form ) => {
	form.addEventListener( 'submit', ( event ) => {
		event.preventDefault();
		refreshResults( form );
	} );

	form.addEventListener( 'change', ( event ) => {
		const target = event.target;

		syncClearAll( form );

		// Mobile flyout: wait for "Show results". Desktop sidebar + top: live.
		if ( ! target || isMobileFlyoutControl( target ) ) {
			return;
		}

		refreshResults( form );
	} );

	let searchTimer;
	form.addEventListener( 'input', ( event ) => {
		const target = event.target;

		// Match the keyword search facet specifically. A dropdown's option
		// filter is also type="search" but must never hit the server.
		if ( ! target?.matches?.( SELECTORS.searchInput ) ) {
			return;
		}

		syncClearAll( form );

		if ( isMobileFlyoutControl( target ) ) {
			return;
		}

		window.clearTimeout( searchTimer );
		searchTimer = window.setTimeout( () => refreshResults( form ), 400 );
	} );

	form.addEventListener( 'click', ( event ) => {
		const target = event.target;

		if ( ! target || ! target.closest( SELECTORS.resetControl ) ) {
			return;
		}

		event.preventDefault();
		clearFacetInputs( form );
		syncDropdowns( form );
		updateFacetClearVisibility( form );
		refreshResults( form );
		syncClearAll( form );

		// The inline reset facet just hid itself, so hand focus to the first
		// control in its own facet grid — never the wider bar, which would let
		// focus escape an open mobile flyout. The flyout's own "Clear all"
		// already returns focus to the mobile trigger.
		if ( target.closest( SELECTORS.facetReset ) ) {
			const grid = target.closest( SELECTORS.filterGrid );

			getFocusables( grid ?? form )[ 0 ]?.focus();
		}
	} );

	bindFacetClears( form );
	bindPagination( form );
};

const init = () => {
	initDropdowns();

	document
		.querySelectorAll( SELECTORS.filterBarSidebar )
		.forEach( ( block ) => {
			bindFlyoutEvents( block );
			block.addEventListener( 'tribe-facets-layout-change', () => {
				syncDropdowns( block );
				updateFacetClearVisibility( block );
			} );
		} );

	document.querySelectorAll( SELECTORS.form ).forEach( ( form ) => {
		initFormBehavior( form );
		syncClearAll( form );
	} );

	// ponytail: browser history restores via a reload rather than replaying
	// state into every control. Upgrade path is syncing inputs from the URL.
	window.addEventListener( 'popstate', ( event ) => {
		if ( event.state?.tribeFacets ) {
			window.location.reload();
		}
	} );
};

init();
