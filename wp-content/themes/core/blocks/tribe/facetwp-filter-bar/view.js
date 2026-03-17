/**
 * @module facetwp-filter-bar
 *
 * Front-end behavior for the FacetWP Filter Bar block:
 *
 * - flyout.js   — Mobile sidebar: "Search & Refine" trigger, flyout dialog, focus trap, "Clear all".
 * - dropdown-actions.js — Top layout: fSelect dropdown action bar (Clear filter / Filter) and keyboard support.
 */

import { bindFlyoutEvents, updateClearAllVisibility } from './js/flyout';
import { initDropdownActions } from './js/dropdown-actions';

/**
 * @function init
 *
 * @description Bootstraps flyout for each sidebar filter bar and dropdown actions on facetwp-loaded.
 */
const init = () => {
	const sidebarBlocks = document.querySelectorAll(
		'.b-facetwp-filter-bar[data-filter-bar-position="sidebar"]'
	);

	sidebarBlocks.forEach( ( block ) => {
		bindFlyoutEvents( block );
	} );

	document.addEventListener( 'facetwp-loaded', () => {
		initDropdownActions();
		sidebarBlocks.forEach( updateClearAllVisibility );
	} );
};

init();
