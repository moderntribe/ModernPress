/**
 * @module
 * @description Some event functions for use in other modules
 */

const ready = ( fn ) => {
	if ( document.readyState !== 'loading' ) {
		fn();
	} else if ( document.addEventListener ) {
		document.addEventListener( 'DOMContentLoaded', fn );
	} else {
		document.attachEvent( 'onreadystatechange', () => {
			if ( document.readyState !== 'loading' ) {
				fn();
			}
		} );
	}
};

/**
 * @function triggerCustomEvent
 * @description Trigger a custom event
 * @param {string} type   The event type
 * @param {Node}   el     The element on which to emit the event
 * @param {*}      detail Any details to pass along with the event
 */
const triggerCustomEvent = ( type, el = document, detail = {} ) => {
	// Event type is required
	if ( ! type ) {
		return;
	}

	// Create new event

	const event = new CustomEvent( type, {
		bubbles: true,
		cancelable: true,
		detail,
	} );

	// Dispatch event
	return el.dispatchEvent( event );
};

export { ready, triggerCustomEvent };
