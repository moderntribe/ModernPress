/* global google, requestAnimationFrame */

/**
 * Waits for the Google Maps loader to become available.
 *
 * @param {number} [timeoutMs=15000] Maximum wait time in milliseconds.
 * @return {Promise<void>} Resolves when Google Maps is ready.
 */
export const waitForGoogleMaps = ( timeoutMs = 15000 ) =>
	new Promise( ( resolve, reject ) => {
		const isReady = () =>
			typeof google !== 'undefined' && google?.maps?.importLibrary;

		if ( isReady() ) {
			resolve();
			return;
		}

		const startedAt = Date.now();

		const check = () => {
			if ( isReady() ) {
				resolve();
				return;
			}

			if ( Date.now() - startedAt >= timeoutMs ) {
				reject( new Error( 'Google Maps failed to load.' ) );
				return;
			}

			requestAnimationFrame( check );
		};

		check();
	} );
