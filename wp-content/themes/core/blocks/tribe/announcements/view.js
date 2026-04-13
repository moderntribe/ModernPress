/**
 * @module announcements
 *
 * @description Announcements Block
 */

const STORAGE_KEY = 'MT:Announcements';
const classes = {
	BLOCK: 'b-announcement',
	DISMISS: 'b-announcement__dismiss',
};
const el = {
	blocks: null,
};

/**
 * @function handleDismissClick
 *
 * @description Handles the click event on the dismiss button of an announcement block
 *
 * @param {Event} event - The click event
 */
const handleDismissClick = ( event ) => {
	const block = event.currentTarget.closest( `.${ classes.BLOCK }` );

	if ( ! block ) {
		return false;
	}

	const annoucementId = block.getAttribute( 'data-announcement-id' );

	if ( ! annoucementId ) {
		return false;
	}

	const announcementStorage = window.localStorage.getItem( STORAGE_KEY );

	window.localStorage.setItem(
		STORAGE_KEY,
		JSON.stringify( {
			...JSON.parse( announcementStorage || '{}' ),
			[ annoucementId ]: true,
		} )
	);

	block.style.display = 'none';
};

/**
 * @function updateDismissWidthOffsets
 *
 * @description Updates the CSS variable for the dismiss button width offset on all announcement blocks, called on window resize
 */
const updateDismissWidthOffsets = () => {
	el.blocks.forEach( ( block ) => {
		const dismissButton = block.querySelector( `.${ classes.DISMISS }` );

		if ( dismissButton ) {
			setupDismissWidthOffset( block, dismissButton );
		}
	} );
};

/**
 * @function setupDismissWidthOffset
 *
 * @description Sets up a CSS variable on the announcement block to offset the content based on the width of the dismiss button
 *
 * @param {HTMLElement} block
 * @param {HTMLElement} dismissButton
 */
const setupDismissWidthOffset = ( block, dismissButton ) => {
	if ( ! block || ! dismissButton ) {
		return;
	}

	// get width of dismiss button to use as CSS offset for the announcement block
	const dimensions = dismissButton.getBoundingClientRect();

	// set CSS variable on block to be used as offset for the announcement block
	block.style.setProperty(
		'--announcement-dismiss-width',
		`${ dimensions.width }px`
	);
};

/**
 * @function bindEvents
 *
 * @description Binds event listeners to the dismiss buttons of each announcement block
 */
const bindEvents = () => {
	if ( ! el.blocks.length ) {
		return;
	}

	el.blocks.forEach( ( block ) => {
		const dismissButton = block.querySelector( `.${ classes.DISMISS }` );

		if ( dismissButton ) {
			// Find width of dismiss button so we can set CSS offset
			setupDismissWidthOffset( block, dismissButton );

			// Bind click event to dismiss button
			dismissButton.addEventListener( 'click', handleDismissClick );
		}
	} );

	document.addEventListener(
		'modern_tribe/resize_executed',
		updateDismissWidthOffsets
	);
};

/**
 * @function determineVisibility
 *
 * @description Determines the visibility of each announcement block based on localStorage
 */
const determineVisibility = () => {
	if ( ! el.blocks.length ) {
		return;
	}

	el.blocks.forEach( ( block ) => {
		const annoucementId = block.getAttribute( 'data-announcement-id' );

		if ( ! annoucementId ) {
			return;
		}

		const announcementStorage = window.localStorage.getItem( STORAGE_KEY );

		if (
			announcementStorage &&
			JSON.parse( announcementStorage )[ annoucementId ]
		) {
			return;
		}

		block.style.display = 'flex';
	} );
};

/**
 * @function cacheElements
 *
 * @description Caches the necessary DOM elements for the announcements block
 */
const cacheElements = () => {
	el.blocks = document.querySelectorAll( `.${ classes.BLOCK }` );
};

/**
 * @function init
 *
 * @description Initializes the announcements block functionality
 */
const init = () => {
	cacheElements();
	determineVisibility();
	bindEvents();
};

init();
