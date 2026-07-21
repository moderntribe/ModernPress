/**
 * @module
 * @description Some handy test for common issues.
 */

const isJson = ( str ) => {
	try {
		JSON.parse( str );
		// eslint-disable-next-line no-unused-vars
	} catch ( e ) {
		return false;
	}

	return true;
};

const canLocalStore = () => {
	let mod;
	let result = false;

	try {
		mod = new Date();
		window.localStorage.setItem( mod, mod.toString() );

		result = window.localStorage.getItem( mod ) === mod.toString();
		window.localStorage.removeItem( mod );
		return result;
		// eslint-disable-next-line no-unused-vars
	} catch ( e ) {
		return result;
	}
};

const isElementVisible = ( el ) => {
	return !! (
		el.offsetWidth ||
		el.offsetHeight ||
		el.getClientRects().length
	);
};

const isElementHidden = ( el ) => {
	return ! isElementVisible( el );
};

export { isJson, canLocalStore, isElementVisible, isElementHidden };
