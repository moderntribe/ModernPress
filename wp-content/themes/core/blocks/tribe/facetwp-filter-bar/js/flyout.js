/**
 * Mobile filter flyout (sidebar layout only).
 *
 * Handles the "Search & Refine" trigger bar, full-screen flyout dialog,
 * focus trap, close/show-results, and "Clear all" visibility.
 */

import { bodyLock } from 'utils/tools';

const FOCUSABLE_SELECTOR =
	'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';

const SELECTORS = {
	block: '.b-facetwp-filter-bar',
	grid: '.b-facetwp-filter-bar__grid',
	flyout: '[data-js="facetwp-filter-flyout"]',
	trigger: '[data-js="facetwp-filter-open"]',
	closeBtn: '[data-js="facetwp-filter-close"]',
	showResultsBtn: '[data-js="facetwp-filter-show-results"]',
	clearWrap: '[data-js="facetwp-filter-clear-wrap"]',
	clearAllBtn: '[data-js="facetwp-filter-clear-all"]',
	activeFacet: '[aria-checked="true"], .fs-option.selected',
	searchInput:
		'.facetwp-type-search input[type="text"], .facetwp-type-search input[type="search"]',
};

/**
 * @function getFocusables
 *
 * @description Gets focusable elements within a root.
 *
 * @param {Element} root Container to search for focusable elements.
 * @return {Element[]} Focusable elements within root.
 */
export const getFocusables = ( root ) =>
	[ ...root.querySelectorAll( FOCUSABLE_SELECTOR ) ].filter(
		( node ) =>
			! node.hasAttribute( 'disabled' ) && node.offsetParent !== null
	);

/**
 * @function trapFocus
 *
 * @description Traps focus within dialog on Tab and closes on Escape.
 *
 * @param {KeyboardEvent} e      Keyboard event.
 * @param {Element}       dialog Dialog element to trap focus within.
 */
const trapFocus = ( e, dialog ) => {
	if ( e.key !== 'Tab' ) {
		return;
	}

	const focusables = getFocusables( dialog );

	if ( focusables.length === 0 ) {
		return;
	}

	const first = focusables[ 0 ];
	const last = focusables[ focusables.length - 1 ];
	const doc = dialog.ownerDocument;
	const active = doc.activeElement;

	if ( e.shiftKey && active === first ) {
		e.preventDefault();
		last.focus();
	} else if ( ! e.shiftKey && active === last ) {
		e.preventDefault();
		first.focus();
	}
};

/**
 * @function openFlyout
 *
 * @description Opens the filter flyout for a sidebar block.
 *
 * @param {Element} block Filter bar block element.
 */
const openFlyout = ( block ) => {
	const flyout = block.querySelector( SELECTORS.flyout );
	const trigger = block.querySelector( SELECTORS.trigger );
	const closeBtn = block.querySelector( SELECTORS.closeBtn );

	if ( ! flyout || ! trigger || ! closeBtn ) {
		return;
	}

	trigger.setAttribute( 'aria-expanded', 'true' );
	flyout.setAttribute( 'aria-hidden', 'false' );
	flyout.classList.add( 'is-open' );
	bodyLock( true );
	closeBtn.focus();
	flyout.addEventListener( 'keydown', flyoutKeydown );
	document.addEventListener( 'keydown', handleDocumentKeydown );
};

/**
 * @function closeFlyout
 *
 * @description Closes the filter flyout for a sidebar block.
 *
 * @param {Element} block Filter bar block element.
 */
const closeFlyout = ( block ) => {
	const flyout = block.querySelector( SELECTORS.flyout );
	const trigger = block.querySelector( SELECTORS.trigger );

	if ( ! flyout || ! trigger ) {
		return;
	}

	trigger.setAttribute( 'aria-expanded', 'false' );
	flyout.setAttribute( 'aria-hidden', 'true' );
	flyout.classList.remove( 'is-open' );
	bodyLock( false );
	trigger.focus();
	flyout.removeEventListener( 'keydown', flyoutKeydown );
	document.removeEventListener( 'keydown', handleDocumentKeydown );
};

/**
 * Document-level keydown: closes open flyout on Escape so it works even when
 * focus is still on the trigger (outside the flyout).
 *
 * @param {KeyboardEvent} e Keyboard event.
 */
const handleDocumentKeydown = ( e ) => {
	if ( e.key !== 'Escape' ) {
		return;
	}

	const openFlyoutEl = document.querySelector(
		`${ SELECTORS.flyout }.is-open`
	);

	if ( ! openFlyoutEl ) {
		return;
	}

	const block = openFlyoutEl.closest( SELECTORS.block );

	if ( block ) {
		e.preventDefault();
		closeFlyout( block );
	}
};

/**
 * @function flyoutKeydown
 *
 * @description Keydown handler for the flyout (Escape + focus trap).
 *
 * @param {KeyboardEvent} e Keyboard event.
 */
const flyoutKeydown = ( e ) => {
	if ( e.key === 'Escape' ) {
		const flyout = e.currentTarget;
		const block = flyout.closest( SELECTORS.block );

		if ( block ) {
			e.preventDefault();
			closeFlyout( block );
		}

		return;
	}

	trapFocus( e, e.currentTarget );
};

/**
 * @function hasActiveFilters
 *
 * @description Whether the block has any active facet selections.
 *
 * @param {Element} block Filter bar block element.
 * @return {boolean} Whether any facet has an active selection.
 */
export const hasActiveFilters = ( block ) => {
	const grid = block.querySelector( SELECTORS.grid );

	if ( ! grid ) {
		return false;
	}

	const checked = grid.querySelectorAll( SELECTORS.activeFacet );
	const searchInput = grid.querySelector( SELECTORS.searchInput );
	const hasSearchValue =
		searchInput && searchInput.value && searchInput.value.trim() !== '';
	return checked.length > 0 || hasSearchValue;
};

/**
 * @function updateClearAllVisibility
 *
 * @description Shows or hides the "Clear all" wrap based on active filters.
 *
 * @param {Element} block Filter bar block element.
 */
export const updateClearAllVisibility = ( block ) => {
	const wrap = block.querySelector( SELECTORS.clearWrap );

	if ( ! wrap ) {
		return;
	}

	if ( ! hasActiveFilters( block ) ) {
		wrap.setAttribute( 'hidden', '' );

		return;
	}

	wrap.removeAttribute( 'hidden' );
};

/**
 * @function onFacetWPUpdate
 *
 * @description Updates the clear all visibility on FacetWP update.
 *
 * @param {Element} block Filter bar block element.
 */
const onFacetWPUpdate = ( block ) => updateClearAllVisibility( block );

/**
 * @function bindFlyoutEvents
 *
 * @description Binds open/close/clear events for the mobile flyout on a sidebar block.
 *
 * @param {Element} block Filter bar block element.
 */
export const bindFlyoutEvents = ( block ) => {
	const trigger = block.querySelector( SELECTORS.trigger );

	if ( trigger ) {
		trigger.addEventListener( 'click', () => openFlyout( block ) );
	}

	const closeBtn = block.querySelector( SELECTORS.closeBtn );

	if ( closeBtn ) {
		closeBtn.addEventListener( 'click', () => closeFlyout( block ) );
	}

	const showResultsBtn = block.querySelector( SELECTORS.showResultsBtn );

	if ( showResultsBtn ) {
		showResultsBtn.addEventListener( 'click', () => closeFlyout( block ) );
	}

	const clearAllBtn = block.querySelector( SELECTORS.clearAllBtn );

	if ( clearAllBtn ) {
		clearAllBtn.addEventListener( 'click', () => {
			if ( typeof window.FWP !== 'undefined' ) {
				window.FWP.reset();
			}
			closeFlyout( block );
		} );
	}

	document.addEventListener( 'facetwp-loaded', () =>
		onFacetWPUpdate( block )
	);

	document.addEventListener( 'facetwp-refresh', () =>
		onFacetWPUpdate( block )
	);

	onFacetWPUpdate( block );
};
