/**
 * AJAX results refresh for a faceted directory.
 *
 * Facet controls are real form inputs, so the client already holds the correct
 * facet state after a change. Only the results grid needs to come back from the
 * server.
 */

import { SELECTORS } from './selectors';

const PAGE_PARAM = 'facet_page';

const getGridConfig = ( grid ) => {
	let postTypes = [];

	try {
		postTypes = JSON.parse( grid.dataset.postTypes || '[]' );
	} catch {
		postTypes = [];
	}

	return {
		postTypes,
		postsPerPage: grid.dataset.postsPerPage || '12',
		showPagination: grid.dataset.showPagination === 'true',
	};
};

/**
 * Clear every facet control within a root (whole form, or a single facet).
 *
 * form.reset() would restore the server-rendered selections, since those are
 * checked/selected attributes in the markup.
 *
 * @param {Element} root Form or facet wrapper.
 */
export const clearFacetInputs = ( root ) => {
	root.querySelectorAll( SELECTORS.checkboxesRadios ).forEach( ( input ) => {
		input.checked = false;
	} );

	root.querySelectorAll( SELECTORS.textInputs ).forEach( ( input ) => {
		input.value = '';
	} );

	root.querySelectorAll( SELECTORS.selects ).forEach( ( select ) => {
		[ ...select.options ].forEach( ( option ) => {
			option.selected = false;
		} );

		if ( ! select.multiple ) {
			select.value = '';
		}
	} );
};

/**
 * Facet params only, for the address bar.
 *
 * @param {HTMLFormElement} form  Directory form.
 * @param {number}          paged Page number.
 * @return {URLSearchParams} Params representing the current filter state.
 */
export const getFacetParams = ( form, paged = 1 ) => {
	const params = new URLSearchParams();

	new FormData( form ).forEach( ( value, key ) => {
		if ( typeof value !== 'string' || value.trim() === '' ) {
			return;
		}
		params.append( key, value );
	} );

	if ( paged > 1 ) {
		params.set( PAGE_PARAM, String( paged ) );
	}

	return params;
};

const buildRequestUrl = ( form, grid, paged ) => {
	const params = getFacetParams( form, paged );
	const config = getGridConfig( grid );

	config.postTypes.forEach( ( type ) =>
		params.append( 'post_types[]', type )
	);
	params.set( 'posts_per_page', config.postsPerPage );
	params.set( 'show_pagination', config.showPagination ? '1' : '0' );
	params.set( 'path', window.location.pathname );

	return `${ form.dataset.endpoint }?${ params.toString() }`;
};

const announce = ( form, found ) => {
	const region = form.querySelector( SELECTORS.liveRegion );

	if ( ! region ) {
		return;
	}

	region.textContent =
		found === 1 ? '1 result found' : `${ found } results found`;
};

const pushUrl = ( form, paged ) => {
	const params = getFacetParams( form, paged ).toString();
	const url = params
		? `${ window.location.pathname }?${ params }`
		: window.location.pathname;

	window.history.pushState( { tribeFacets: true }, '', url );
};

export const refreshResults = async ( form, paged = 1 ) => {
	const grid = form.querySelector( SELECTORS.directoryGrid );

	if ( ! grid || ! form.dataset.endpoint ) {
		return;
	}

	grid.setAttribute( 'aria-busy', 'true' );

	try {
		const response = await fetch( buildRequestUrl( form, grid, paged ), {
			headers: { Accept: 'application/json' },
		} );

		if ( ! response.ok ) {
			throw new Error( `Request failed: ${ response.status }` );
		}

		const data = await response.json();

		grid.innerHTML = data.html;
		announce( form, data.found );
		pushUrl( form, paged );
	} catch {
		// Fall back to a normal navigation so the user still gets results.
		form.submit();
	} finally {
		grid.setAttribute( 'aria-busy', 'false' );
	}
};

/**
 * Upgrade server-rendered pagination links to AJAX requests.
 *
 * @param {HTMLFormElement} form Directory form.
 */
export const bindPagination = ( form ) => {
	form.addEventListener( 'click', ( event ) => {
		const link = event.target.closest( SELECTORS.paginationLink );

		if ( ! link ) {
			return;
		}

		event.preventDefault();

		const paged = Number(
			new URL( link.href, window.location.origin ).searchParams.get(
				PAGE_PARAM
			) || 1
		);

		refreshResults( form, paged );

		const grid = form.querySelector( SELECTORS.directoryGrid );

		if ( grid ) {
			grid.scrollIntoView( { behavior: 'smooth', block: 'start' } );
		}
	} );
};
