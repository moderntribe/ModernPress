/**
 * Scripts specific to this block
 */

import imagesLoaded from 'imagesloaded';

const el = {
	marquees: document.querySelectorAll(
		'.wp-block-tribe-logo-marquee .logo-list'
	),
};

const state = {
	rate: 150,
	clonesCreated: false,
};

/**
 * @function setAnimationProperties
 *
 * @description set animation properties for the marquee
 *
 * @param {*} marquee
 */
const setAnimationProperties = ( marquee ) => {
	// get width of wrapper
	const wrapperWidth = marquee.offsetWidth;

	// get rate from data attribute if it exists
	const marqueeRate = marquee.getAttribute( 'data-marquee-speed' );

	if ( marqueeRate ) {
		state.rate = parseInt( marqueeRate );
	}

	// calulate duration based on width and rate
	const duration = wrapperWidth / state.rate;

	// set animation properties
	marquee.style.setProperty( '--animation-duration', `${ duration }s` );
	marquee.style.setProperty(
		'animation',
		'marquee-scroll-x var(--animation-duration) linear infinite'
	);
};

/**
 * @function createClones
 *
 * @description creates cloned elements and hides them from screen readers
 *
 * @param {*} wrapper
 */
const createClones = ( wrapper ) => {
	const elements = wrapper.querySelectorAll( '.gallery-item:not(.cloned)' );

	if ( elements ) {
		elements.forEach( ( element ) => {
			const clone = element.cloneNode( true );
			clone.setAttribute( 'aria-hidden', true );
			clone.classList.add( 'cloned' );

			wrapper.append( clone );
		} );
	}
};

/**
 * @function checkWrapperWidth
 *
 * @description determines if we need to add more elements to the wrapper to fill the screen by checking the max-content wrapper against the window width
 *
 * @param {*} marquee
 */
const checkWrapperWidth = ( marquee ) => {
	// we need at least 2 "widths" of elements to create the effect properly
	if ( marquee.getBoundingClientRect().width < window.innerWidth * 2 ) {
		state.clonesCreated = true;

		createClones( marquee );

		checkWrapperWidth( marquee );
	}
	// on mobile, it's possible for the logos to be wide enough to take up 2 widths without clones being created
	else if (
		marquee.getBoundingClientRect().width >= window.innerWidth * 2 &&
		! state.clonesCreated
	) {
		state.clonesCreated = true;
	}
};

/**
 * @function init
 *
 * @description loops through all logo farm blocks to initialize the elements
 */
const init = () => {
	if ( ! el.marquees ) {
		return;
	}

	el.marquees.forEach( ( marquee ) => {
		// use images loaded to detemine when we should initalize the script
		imagesLoaded( marquee, () => {
			checkWrapperWidth( marquee );
			setAnimationProperties( marquee );
		} );
	} );
};

init();
