/**
 * fSelect dropdown actions (top filter bar layout).
 *
 * Injects "Clear filter" / "Filter" buttons into FacetWP fSelect dropdowns
 * and handles keyboard navigation within the action toolbar.
 *
 * @see https://facetwp.com/help-center/developers/javascript-reference/
 */

const el = {};

/**
 * @function facetDropdownActions
 *
 * @description Returns the HTML markup for the facet dropdown action bar (Clear filter + Filter buttons).
 *
 * @param {string} facet Facet name/slug.
 * @return {string} HTML string for the actions toolbar.
 */
const facetDropdownActions = (
	facet
) => `<div class="fs-actions is-style-white" role="toolbar" aria-label="Filter actions">
	<button type="button" class="a-btn-link" data-js="facet-clear-filter" data-facet="${ facet }">Clear filter</button>
	<button type="button" class="a-btn" data-js="facet-close">Filter</button>
</div>`;

/**
 * @function handleCloseFacet
 *
 * @description Closes the fSelect dropdown via FacetWP's fUtil.
 *
 * @param {Event} e Click event.
 */
const handleCloseFacet = ( e ) => {
	const facet = e.currentTarget.closest( '.facetwp-facet' );
	const fselectEl = facet.querySelector( '.facetwp-dropdown' );
	window.fUtil( fselectEl ).nodes[ 0 ].fselect.close();
};

/**
 * @function handleClearFacet
 *
 * @description Clears selections for a single facet via FWP.reset( facetName ).
 *
 * @param {Event} e Click event.
 */
const handleClearFacet = ( e ) => {
	const facetName = e.currentTarget.getAttribute( 'data-facet' );
	window.FWP.reset( facetName );
};

/**
 * @function handleActionsKeydown
 *
 * @description Handles keyboard support for the dropdown action bar (toolbar).
 *
 * @param {KeyboardEvent} e Keyboard event.
 */
const handleActionsKeydown = ( e ) => {
	const toolbar = e.currentTarget;
	const buttons = [ ...toolbar.querySelectorAll( 'button' ) ];
	const currentIndex = buttons.indexOf( e.target );

	if ( currentIndex === -1 ) {
		return;
	}

	switch ( e.key ) {
		case 'ArrowUp': {
			if ( currentIndex === 0 ) {
				const facet = toolbar.closest( '.facetwp-facet' );
				const optionsContainer = facet?.querySelector( '.fs-options' );
				const options = optionsContainer
					? [ ...optionsContainer.querySelectorAll( '.fs-option' ) ]
					: [];
				const lastOption = options[ options.length - 1 ];
				if ( lastOption ) {
					e.preventDefault();
					e.stopPropagation();
					lastOption.focus();
				}
			} else {
				e.preventDefault();
				e.stopPropagation();
				buttons[ currentIndex - 1 ].focus();
			}
			break;
		}

		case 'ArrowLeft':
		case 'ArrowRight': {
			e.preventDefault();
			e.stopPropagation();
			const direction = e.key === 'ArrowRight' ? 1 : -1;
			const nextIndex =
				( currentIndex + direction + buttons.length ) % buttons.length;
			buttons[ nextIndex ].focus();
			break;
		}

		case 'Enter':
		case ' ': {
			e.stopPropagation();
			break;
		}

		default:
			break;
	}
};

/**
 * @function handleDropdownKeydown
 *
 * @description Handles Arrow Down on the last fs-option moves focus to the first action button.
 *
 * @param {KeyboardEvent} e Keyboard event.
 */
const handleDropdownKeydown = ( e ) => {
	if ( e.key !== 'ArrowDown' ) {
		return;
	}

	const facet = e.currentTarget.closest( '.facetwp-facet' );
	const optionsContainer = facet?.querySelector( '.fs-options' );
	const actionsBar = facet?.querySelector( '.fs-actions' );

	if ( ! optionsContainer || ! actionsBar ) {
		return;
	}

	const options = [ ...optionsContainer.querySelectorAll( '.fs-option' ) ];
	const actionButtons = [ ...actionsBar.querySelectorAll( 'button' ) ];
	const activeEl = facet.ownerDocument.activeElement;
	const lastOption = options[ options.length - 1 ];

	if ( activeEl === lastOption && actionButtons[ 0 ] ) {
		e.preventDefault();
		e.stopPropagation();
		actionButtons[ 0 ].focus();
	}
};

/**
 * @function bindEvents
 *
 * @description Binds events to each fSelect facet's clear/close and keyboard handlers.
 */
const bindEvents = () => {
	if ( ! el.facets ) {
		return;
	}
	el.facets.forEach( ( facet ) => {
		const clearFacet = facet.querySelector(
			'[data-js="facet-clear-filter"]'
		);
		if ( clearFacet ) {
			clearFacet.addEventListener( 'click', handleClearFacet );
		}

		const closeFacet = facet.querySelector( '[data-js="facet-close"]' );
		if ( closeFacet ) {
			closeFacet.addEventListener( 'click', handleCloseFacet );
		}

		const actionsBar = facet.querySelector( '.fs-actions' );
		if ( actionsBar ) {
			actionsBar.addEventListener( 'keydown', handleActionsKeydown );
		}

		const dropdown = facet.querySelector( '.fs-dropdown' );
		if ( dropdown ) {
			dropdown.addEventListener( 'keydown', handleDropdownKeydown );
		}
	} );
};

/**
 * @function createDropdownButtons
 *
 * @description Injects the action bar markup into each fSelect dropdown.
 */
const createDropdownButtons = () => {
	if ( ! el.facets ) {
		return;
	}
	el.facets.forEach( ( facet ) => {
		const facetName = facet.getAttribute( 'data-name' );
		const dropdownActionsEl = facetDropdownActions( facetName );
		const dropdown = facet.querySelector( '.fs-dropdown' );

		if ( dropdown ) {
			dropdown.innerHTML += dropdownActionsEl;
		}
	} );
};

/**
 * @function cacheElements
 *
 * @description Caches filter bar and fSelect facet elements for dropdown actions.
 */
const cacheElements = () => {
	el.filters = document.querySelector( '.b-facetwp-filter-bar' );
	el.facets = document.querySelectorAll(
		'.facetwp-facet.facetwp-type-fselect'
	);
};

/**
 * @function initDropdownActions
 *
 * @description Initializes fSelect dropdown actions: caches elements, injects buttons, binds events.
 * Call this on the facetwp-loaded event.
 */
export const initDropdownActions = () => {
	cacheElements();

	const actions = el.filters?.querySelector( '.fs-actions' );

	if ( ! actions && el.filters && el.facets?.length ) {
		createDropdownButtons();
		bindEvents();
	}
};
