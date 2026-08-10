/**
 * Mobile filter flyout (sidebar layout only).
 */

import { bodyLock } from 'utils/tools.js';
import { SELECTORS } from './selectors';

export const getFocusables = ( root ) =>
	[ ...root.querySelectorAll( SELECTORS.focusable ) ].filter(
		( node ) =>
			! node.hasAttribute( 'disabled' ) &&
			node.tabIndex >= 0 &&
			node.offsetParent !== null
	);

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
	const active = dialog.ownerDocument.activeElement;

	if ( e.shiftKey && active === first ) {
		e.preventDefault();
		last.focus();
	} else if ( ! e.shiftKey && active === last ) {
		e.preventDefault();
		first.focus();
	}
};

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

	const block = openFlyoutEl.closest( SELECTORS.filterBar );

	if ( block ) {
		e.preventDefault();
		closeFlyout( block );
	}
};

const flyoutKeydown = ( e ) => {
	if ( e.key === 'Escape' ) {
		const flyout = e.currentTarget;
		const block = flyout.closest( SELECTORS.filterBar );

		if ( block ) {
			e.preventDefault();
			closeFlyout( block );
		}

		return;
	}

	trapFocus( e, e.currentTarget );
};

export const hasActiveFilters = ( block ) => {
	const activeFacets =
		[
			block.querySelector( SELECTORS.mobileFacets ),
			block.querySelector( SELECTORS.sidebarFacets ),
		].find( ( facets ) => facets && ! facets.disabled ) ?? block;
	const grid = activeFacets.querySelector( SELECTORS.filterGrid );

	if ( ! grid ) {
		return false;
	}

	const checked = [
		...grid.querySelectorAll( SELECTORS.activeFacet ),
	].filter( ( el ) => el.value !== '' );
	const searchInput = grid.querySelector( SELECTORS.searchInput );
	const hasSearchValue =
		searchInput && searchInput.value && searchInput.value.trim() !== '';

	return checked.length > 0 || hasSearchValue;
};

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
 * Copy form state between the desktop sidebar and mobile flyout variants.
 *
 * @param {HTMLFieldSetElement} source Active controls.
 * @param {HTMLFieldSetElement} target Controls about to become active.
 */
const copyFacetState = ( source, target ) => {
	const sourceControls = [ ...source.elements ];

	[ ...target.elements ].forEach( ( control ) => {
		if ( ! control.name ) {
			return;
		}

		const facetName = control.name.replace( /\[\]$/, '' );
		const matches = sourceControls.filter(
			( sourceControl ) =>
				sourceControl.name.replace( /\[\]$/, '' ) === facetName
		);
		const selectedValues = matches.flatMap( ( sourceControl ) => {
			if ( sourceControl instanceof window.HTMLSelectElement ) {
				return [ ...sourceControl.selectedOptions ].map(
					( option ) => option.value
				);
			}

			if ( sourceControl instanceof window.HTMLInputElement ) {
				if ( [ 'checkbox', 'radio' ].includes( sourceControl.type ) ) {
					return sourceControl.checked ? [ sourceControl.value ] : [];
				}

				return [ sourceControl.value ];
			}

			return [];
		} );

		if ( control instanceof window.HTMLSelectElement ) {
			[ ...control.options ].forEach( ( option ) => {
				option.selected = selectedValues.includes( option.value );
			} );

			return;
		}

		if ( control instanceof window.HTMLInputElement ) {
			if ( [ 'checkbox', 'radio' ].includes( control.type ) ) {
				control.checked = selectedValues.includes( control.value );

				return;
			}

			control.value = selectedValues[ 0 ] ?? '';
		}
	} );
};

/**
 * Enable only the responsive control set currently visible. Disabled controls
 * are omitted from FormData, preventing duplicate facet values.
 *
 * @param {Element} block Sidebar filter bar.
 */
const syncResponsiveFacets = ( block ) => {
	const trigger = block.querySelector( SELECTORS.mobileTrigger );
	const sidebarFacets = block.querySelector( SELECTORS.sidebarFacets );
	const mobileFacets = block.querySelector( SELECTORS.mobileFacets );

	if ( ! trigger || ! sidebarFacets || ! mobileFacets ) {
		return;
	}

	const isMobile = window.getComputedStyle( trigger ).display !== 'none';
	const source = isMobile ? sidebarFacets : mobileFacets;
	const target = isMobile ? mobileFacets : sidebarFacets;

	if ( ! source.disabled && target.disabled ) {
		copyFacetState( source, target );
	}

	sidebarFacets.disabled = isMobile;
	mobileFacets.disabled = ! isMobile;

	block.dispatchEvent( new CustomEvent( 'tribe-facets-layout-change' ) );
};

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
		showResultsBtn.addEventListener( 'click', () => {
			closeFlyout( block );
		} );
	}

	// Clearing itself is handled by the form-level handler in view.js.
	const clearAllBtn = block.querySelector( SELECTORS.clearAllBtn );

	if ( clearAllBtn ) {
		clearAllBtn.addEventListener( 'click', () => closeFlyout( block ) );
	}

	block.addEventListener( 'change', () => updateClearAllVisibility( block ) );

	const resizeTarget = block.closest( SELECTORS.directory ) ?? block;
	const resizeObserver = new window.ResizeObserver( () => {
		syncResponsiveFacets( block );
		updateClearAllVisibility( block );
	} );

	resizeObserver.observe( resizeTarget );
	syncResponsiveFacets( block );
	updateClearAllVisibility( block );
};
