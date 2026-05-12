/**
 * @module
 * @exports masthead
 * @description global masthead and navigation scripts for mobile and desktop.
 */

import { HEADER_BREAKPOINT } from 'config/options.js';
import globalState from 'config/state.js';
import { triggerCustomEvent } from 'utils/events.js';

const el = {
	body: document.body,
	header: document.querySelector( '.site-header' ),
	navigation: document.querySelector( '.site-header__navigation' ),
};

const state = {
	elementsCloned: false,
};

/**
 * @function insertClonedNode
 *
 * @description appends or prepends a cloned node to the wrapper container
 * @param {HTMLElement} wrapper
 * @param {HTMLElement} node
 * @param {boolean}     prepend
 */
const insertClonedNode = ( wrapper, node, prepend = true ) => {
	node.classList.add( 'cloned-mobile-element' );

	if ( ! prepend ) {
		wrapper.append( node );
		return;
	}

	wrapper.prepend( node );
};

/**
 * @function createMobileMenu
 *
 * @description inserts a cloned element to the designated wrapper and triggers a custom event
 */
const createMobileMenu = () => {
	/**
	 * Duplicate nodes for the mobile nav.
	 *
	 * USAGE: duplicate elements in the order you need them to stack. If you're prepending, duplicate the bottom element
	 * first. Order will be bottom to top. If you're appending, duplicate top item 1st.
	 */
	insertClonedNode( el.navigation, wrapCloneElement( el.search ) );
	insertClonedNode( el.navigation, wrapCloneElement( el.cta ) );
	insertClonedNode( el.navigation, wrapCloneElement( el.utilityNav ), false );

	triggerCustomEvent( 'modern_tribe/cloned_elements_attached' );
};

/**
 * @function wrapCloneElement
 * @description wrap cloned element in a <li> tag for a11y and semantic HTML compliance
 * @param {HTMLElement} clone
 * @return {HTMLElement|void} returns cloned element wrapped in a <li> tag
 */
const wrapCloneElement = ( clone ) => {
	if ( ! clone ) {
		return;
	}

	const listItem = document.createElement( 'li' );
	listItem.appendChild( clone );
	return listItem;
};

/**
 * @function cloneElements
 *
 * @description create a clone of various elements to use within the mobile menu.
 */
const cloneElements = () => {
	el.cta = el.header.querySelector( '.site-header__cta' ).cloneNode( true );
	el.search = el.header
		.querySelector( '[data-js="masthead-search-overlay"]' )
		.cloneNode( true );
	el.utilityNav = el.header
		.querySelector( '.site-header__utility-menu' )
		.cloneNode( true );
	state.elementsCloned = true;
};

/**
 * @function handleResize
 *
 * @description run any function that need to be executed from the resize event.
 */
const handleResize = () => {
	if ( window.innerWidth < HEADER_BREAKPOINT && ! state.elementsCloned ) {
		cloneElements();
		createMobileMenu();
	}

	getHeaderHeight();
};

/**
 * @function handleKeyboardEvents
 *
 * @description trigger custom events from `keyup` keyboard interactions.
 * @param {event} e
 */
const handleKeyboardEvents = ( e ) => {
	if ( e.key === 'Escape' ) {
		triggerCustomEvent( 'modern_tribe/close_on_escape' );
	}
};

/**
 * @function bindEvents
 * @description Binds event listeners for handling resize and keyboard interactions.
 */
const bindEvents = () => {
	document.addEventListener( 'modern_tribe/resize_executed', handleResize );
	el.header.addEventListener( 'keyup', handleKeyboardEvents );
};

/**
 * @function getHeaderHeight
 *
 * @description since the masthead is a dynamic height, we need to be able to offset the mobile menu using a CSS
 *     property
 */
const getHeaderHeight = () => {
	globalState.headerHeight = el.header.offsetHeight;
	el.body.style.setProperty(
		'--header-height',
		globalState.headerHeight + 'px'
	);
};

/**
 * @function init
 * @description Initializes the masthead by setting up elements, creating the mobile menu, and binding events.
 */
const init = () => {
	if ( ! el.header || ! el.navigation ) {
		return;
	}

	if ( window.innerWidth < HEADER_BREAKPOINT ) {
		cloneElements();
		createMobileMenu();
	}

	getHeaderHeight();
	bindEvents();
};

export default init;
