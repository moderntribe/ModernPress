/**
 * @module comparison-table
 *
 * @description Comparison Table block front-end enhancements.
 */

const SELECTOR = '[data-js="comparison-table"]';

/**
 * Adds accessibility attributes to the horizontally scrollable table region.
 *
 * @param {HTMLElement} container Scroll wrapper element for the desktop table.
 */
const enhanceScrollContainer = ( container ) => {
	container.setAttribute( 'role', 'region' );
	container.setAttribute( 'aria-label', 'Comparison table' );
};

/**
 * Initializes front-end enhancements for all comparison tables on the page.
 */
const init = () => {
	document.querySelectorAll( SELECTOR ).forEach( enhanceScrollContainer );
};

init();
