/**
 * @module
 * @exports ready
 * @description The core dispatcher for the dom ready event javascript.
 */

import { ready } from 'utils/events.js';
import { debounce } from 'utils/tools.js';
import masthead from './masthead';

import resize from 'common/resize.js';
import viewportDims from 'common/viewport-dims.js';

import animateOnScroll from './animate-on-scroll.js';

/**
 * @function bindEvents
 * @description Bind global event listeners here,
 */

const bindEvents = () => {
	window.addEventListener( 'resize', debounce( resize, 200 ) );
};

/**
 * @function init
 * @description The core dispatcher for init across the codebase.
 */

const init = () => {
	// set initial states

	viewportDims();

	// initialize global events

	bindEvents();

	// initialize global features

	masthead();

	// initialize animation on scroll

	animateOnScroll();

	console.info(
		'Theme: Initialized all javascript that targeted document ready.'
	);
};

/**
 * @function domReady
 * @description Export our dom ready enabled init.
 */

const domReady = () => {
	ready( init );
};

export default domReady;
