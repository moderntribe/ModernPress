/**
 * Front-end behavior for the Filter Bar block.
 */

import { bindFlyoutEvents, updateClearAllVisibility } from './js/flyout';
import { initFancyDropdowns, syncFancyDropdowns } from './js/fancy-dropdown';
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

const initFormBehavior = ( form ) => {
	form.addEventListener( 'submit', ( event ) => {
		event.preventDefault();
		refreshResults( form );
	} );

	form.addEventListener( 'change', ( event ) => {
		const target = event.target;

		// Mobile flyout: wait for "Show results". Desktop sidebar + top: live.
		if ( ! target || isMobileFlyoutControl( target ) ) {
			return;
		}

		refreshResults( form );
	} );

	let searchTimer;
	form.addEventListener( 'input', ( event ) => {
		const target = event.target;

		if (
			! target ||
			target.type !== 'search' ||
			isMobileFlyoutControl( target )
		) {
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
		syncFancyDropdowns( form );
		updateFacetClearVisibility( form );
		refreshResults( form );
		form.querySelectorAll( SELECTORS.filterBar ).forEach(
			updateClearAllVisibility
		);
	} );

	bindFacetClears( form );
	bindPagination( form );
};

const init = () => {
	initFancyDropdowns();

	document
		.querySelectorAll( SELECTORS.filterBarSidebar )
		.forEach( ( block ) => {
			bindFlyoutEvents( block );
			block.addEventListener( 'tribe-facets-layout-change', () => {
				syncFancyDropdowns( block );
				updateFacetClearVisibility( block );
			} );
		} );

	document.querySelectorAll( SELECTORS.form ).forEach( ( form ) => {
		initFormBehavior( form );
		form.querySelectorAll( SELECTORS.filterBar ).forEach(
			updateClearAllVisibility
		);
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
