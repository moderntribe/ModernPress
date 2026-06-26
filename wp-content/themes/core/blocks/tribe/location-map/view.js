/**
 * @module location-map
 *
 * @description Google Maps-powered location map with optional search sidebar.
 */

/* global google, requestAnimationFrame */

import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { ready } from 'utils/events';
import {
	createMap,
	fitMapToLocations,
	focusMap,
	invalidateMapSize,
	setActiveMarker,
	setMarkers,
} from './js/google-maps-map';
import { locationCard } from './templates';
import { initSearchAutocomplete } from './js/search-autocomplete';
import { getAreaSearchLabel, getAreaSearchParams } from './js/search-resolver';

/** @type {Object<string, string>} DOM selectors used by the block script. */
const selectors = {
	block: '.b-location-map',
	canvas: '[data-js="location-map-canvas"]',
	form: '[data-js="location-map-form"]',
	searchInput: '[data-js="location-map-search"]',
	useLocationButton: '[data-js="location-map-use-location"]',
	locations: '[data-js="location-map-locations"]',
	list: '[data-js="location-map-list"]',
	results: '[data-js="location-map-results"]',
	noResults: '[data-js="location-map-no-results"]',
	error: '[data-js="location-map-error"]',
	mobileToggle: '[data-js="location-map-mobile-toggle"]',
	loading: '[data-js="location-map-loading"]',
	locationCard: '.b-location-map__location',
};

/**
 * @typedef {Object} LocationMapSettings
 * @property {string}  [locationSource]       Location data source mode.
 * @property {string}  [endpointUrl]          REST endpoint for location data.
 * @property {string}  [geocodeUrl]           REST endpoint for geocoding searches.
 * @property {number}  [searchRadius]         Search radius in miles.
 * @property {boolean} [fitBounds]            Whether to fit the map to markers.
 * @property {boolean} [clusterMarkers]       Whether to cluster overlapping markers.
 * @property {string}  [mapId]                Google Maps Map ID for Advanced Markers.
 * @property {number}  [autocompleteMinChars] Minimum characters before autocomplete activates.
 * @property {number}  [autocompleteDebounce] Autocomplete debounce delay in milliseconds.
 * @property {boolean} [autocompleteEnabled]  Whether Google Places autocomplete is enabled.
 * @property {string}  [searchCountry]        ISO country code for Places restrictions.
 * @property {boolean} [showSearch]           Whether the search field is visible.
 */

/**
 * @typedef {Object} BlockState
 * @property {Object|null}                                  map       Google Map instance.
 * @property {LocationMapSettings}                          settings  Block settings from SSR.
 * @property {import('./js/google-maps-map').MapLocation[]} locations Normalized location data.
 */

/** @type {WeakMap<HTMLElement, BlockState>} Per-block runtime state. */
const blockStates = new WeakMap();

/** @type {WeakMap<HTMLElement, HTMLElement>} Block wrapper to loading overlay. */
const loadingOverlays = new WeakMap();

/**
 * Returns runtime state for a block instance, creating it when needed.
 *
 * @param {HTMLElement} block Block wrapper element.
 * @return {BlockState} Block runtime state.
 */
const getBlockState = ( block ) => {
	if ( ! blockStates.has( block ) ) {
		blockStates.set( block, {
			map: null,
			settings: {},
			locations: [],
		} );
	}

	return blockStates.get( block );
};

/**
 * Parses a JSON-encoded data attribute from the block wrapper.
 *
 * @param {HTMLElement} element   Block wrapper element.
 * @param {string}      attribute Data attribute name.
 * @param {*}           fallback  Value returned when parsing fails.
 * @return {*} Parsed attribute value.
 */
const parseJsonAttribute = ( element, attribute, fallback ) => {
	try {
		return JSON.parse( element.getAttribute( attribute ) || '' );
	} catch {
		return fallback;
	}
};

/**
 * Returns the loading overlay for a block instance.
 *
 * @param {HTMLElement} block Block wrapper element.
 * @return {HTMLElement|null} Loading overlay element.
 */
const getLoadingOverlay = ( block ) => {
	if ( loadingOverlays.has( block ) ) {
		return loadingOverlays.get( block ) ?? null;
	}

	const overlay = block.querySelector( selectors.loading );

	if ( overlay ) {
		loadingOverlays.set( block, overlay );
	}

	return overlay;
};

/**
 * Moves the overlay to the document body so fixed positioning covers the viewport.
 *
 * @param {HTMLElement} overlay Loading overlay element.
 * @param {HTMLElement} block   Block wrapper element.
 */
const mountLoadingOverlay = ( overlay, block ) => {
	loadingOverlays.set( block, overlay );

	if ( overlay.parentElement !== document.body ) {
		document.body.appendChild( overlay );
	}
};

/**
 * Toggles the loading overlay used during async requests.
 *
 * @param {HTMLElement} block     Block wrapper element.
 * @param {boolean}     isLoading Whether loading UI should be visible.
 */
const setLoading = ( block, isLoading ) => {
	const overlay = getLoadingOverlay( block );

	if ( ! overlay ) {
		return;
	}

	if ( isLoading ) {
		mountLoadingOverlay( overlay, block );
	}

	overlay.hidden = ! isLoading;
	overlay.classList.toggle( 'is-loading', isLoading );
	overlay.setAttribute( 'aria-hidden', isLoading ? 'false' : 'true' );
};

/**
 * Sets visibility of a message element.
 *
 * @param {HTMLElement|null} element Message element.
 * @param {boolean}          visible Whether the message should be visible.
 */
const setMessageVisibility = ( element, visible ) => {
	if ( ! element ) {
		return;
	}

	element.hidden = ! visible;
	element.classList.toggle( 'hidden', ! visible );
};

/**
 * Ensures the location list panel is visible on narrow layouts.
 *
 * @param {HTMLElement} block Block wrapper element.
 */
const openMobileLocationList = ( block ) => {
	const locations = block.querySelector( selectors.locations );

	if ( ! locations || locations.classList.contains( 'is-mobile-open' ) ) {
		return;
	}

	locations.classList.add( 'is-mobile-open' );

	const mobileToggle = block.querySelector( selectors.mobileToggle );
	const label = mobileToggle?.querySelector(
		'.b-location-map__locations-mobile-toggle-text'
	);

	if ( label && mobileToggle ) {
		label.textContent = mobileToggle.getAttribute( 'data-on-text' ) || '';
	}
};

/**
 * Clears any visible search result messages.
 *
 * @param {HTMLElement} block Block wrapper element.
 */
const hideResultMessages = ( block ) => {
	const results = block.querySelector( selectors.results );
	const noResults = block.querySelector( selectors.noResults );
	const error = block.querySelector( selectors.error );

	[ results, noResults, error ].forEach( ( element ) => {
		if ( element ) {
			element.textContent = '';
		}
	} );

	setMessageVisibility( results, false );
	setMessageVisibility( noResults, false );
	setMessageVisibility( error, false );
};

/**
 * Displays the successful search results message.
 *
 * @param {HTMLElement}                                   block        Block wrapper element.
 * @param {number}                                        count        Number of locations found.
 * @param {string}                                        locationName Searched place name.
 * @param {import('./js/search-resolver').LocationSearch} [search]     Resolved search strategy.
 */
const showResultsMessage = (
	block,
	count,
	locationName,
	search = { mode: 'radius' }
) => {
	const results = block.querySelector( selectors.results );

	if ( ! results ) {
		return;
	}

	const { settings } = getBlockState( block );
	const label = locationName || getAreaSearchLabel( search );

	if ( search.mode === 'area' ) {
		if ( search.scope === 'zip' ) {
			results.textContent = sprintf(
				/* translators: 1: location count, 2: ZIP code */
				__( 'Found %1$d locations in ZIP code %2$s.', 'tribe' ),
				count,
				label
			);
		} else {
			results.textContent = sprintf(
				/* translators: 1: location count, 2: searched place name */
				__( 'Found %1$d locations in %2$s.', 'tribe' ),
				count,
				label
			);
		}
	} else {
		results.textContent = sprintf(
			/* translators: 1: location count, 2: search radius in miles, 3: searched place name */
			__( 'Found %1$d locations within %2$d miles of %3$s.', 'tribe' ),
			count,
			settings.searchRadius || 30,
			label
		);
	}

	openMobileLocationList( block );
	setMessageVisibility( results, true );
};

/**
 * Displays the empty search results message.
 *
 * @param {HTMLElement}                                   block             Block wrapper element.
 * @param {string}                                        [locationName=''] Searched place name.
 * @param {import('./js/search-resolver').LocationSearch} [search]          Resolved search strategy.
 */
const showNoResultsMessage = (
	block,
	locationName = '',
	search = { mode: 'radius' }
) => {
	const noResults = block.querySelector( selectors.noResults );

	if ( ! noResults ) {
		return;
	}

	const { settings } = getBlockState( block );
	const label = locationName || getAreaSearchLabel( search );

	if ( search.mode === 'area' ) {
		noResults.textContent = sprintf(
			/* translators: %s: searched place name */
			__( 'Sorry, no locations were found in %s.', 'tribe' ),
			label
		);
	} else {
		noResults.textContent = sprintf(
			/* translators: 1: search radius in miles, 2: searched place name */
			__(
				'Sorry, no locations were found within %1$d miles of %2$s.',
				'tribe'
			),
			settings.searchRadius || 30,
			label
		);
	}

	openMobileLocationList( block );
	setMessageVisibility( noResults, true );
};

/**
 * Displays a general error message in the sidebar.
 *
 * @param {HTMLElement} block   Block wrapper element.
 * @param {string}      message Error message text.
 */
const showErrorMessage = ( block, message ) => {
	const error = block.querySelector( selectors.error );

	if ( ! error ) {
		return;
	}

	error.textContent = message;
	openMobileLocationList( block );
	setMessageVisibility( error, true );
};

/**
 * Renders the sidebar location list markup.
 *
 * @param {HTMLElement}                                  block     Block wrapper element.
 * @param {import('./js/google-maps-map').MapLocation[]} locations Normalized location data.
 */
const renderLocationList = ( block, locations ) => {
	const list = block.querySelector( selectors.list );

	if ( ! list ) {
		return;
	}

	list.innerHTML = locations
		.map( ( location ) => locationCard( location ) )
		.join( '' );
};

/**
 * Syncs the active marker and list card when a map pin is clicked.
 *
 * @param {HTMLElement} block Block wrapper element.
 * @param {number}      index Active location index.
 */
const handleMarkerClick = ( block, index ) => {
	const { map } = getBlockState( block );

	setActiveMarker( map, index );

	const cards = block.querySelectorAll( selectors.locationCard );

	cards.forEach( ( card, cardIndex ) => {
		card.classList.toggle( 'is-active', cardIndex === index );
	} );

	const card = cards[ index ];

	if ( card ) {
		card.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
	}
};

/**
 * Updates the map markers, list, and optional search status message.
 *
 * @param {HTMLElement}                                   block             Block wrapper element.
 * @param {import('./js/google-maps-map').MapLocation[]}  locations         Normalized location data.
 * @param {string}                                        [locationName=''] Label for search result messaging.
 * @param {{ lat: number, lng: number }|null}             [searchCenter]    Map center when a search returns no locations.
 * @param {import('./js/search-resolver').LocationSearch} [search]          Resolved search strategy.
 */
const renderLocations = async (
	block,
	locations,
	locationName = '',
	searchCenter = null,
	search = { mode: 'radius' }
) => {
	const blockState = getBlockState( block );

	blockState.locations = locations;
	hideResultMessages( block );
	renderLocationList( block, locations );

	await setMarkers( blockState.map, locations, {
		clusterMarkers: blockState.settings.clusterMarkers,
		onMarkerClick: ( _marker, index ) => handleMarkerClick( block, index ),
	} );

	if ( locations.length ) {
		if ( blockState.settings.fitBounds ) {
			fitMapToLocations( blockState.map, locations );
		} else if ( searchCenter ) {
			focusMap( blockState.map, searchCenter.lat, searchCenter.lng, 11 );
		}
	} else if ( searchCenter ) {
		focusMap( blockState.map, searchCenter.lat, searchCenter.lng, 11 );
	}

	if ( locations.length && locationName ) {
		showResultsMessage( block, locations.length, locationName, search );
	} else if ( ! locations.length && locationName ) {
		showNoResultsMessage( block, locationName, search );
	}

	invalidateMapSize( blockState.map );
};

/**
 * Fetches normalized locations from the configured REST endpoint.
 *
 * @param {HTMLElement}                   block       Block wrapper element.
 * @param {Object<string, string|number>} [params={}] Query parameters.
 * @return {Promise<import('./js/google-maps-map').MapLocation[]>} Location results.
 */
const fetchLocations = async ( block, params = {} ) => {
	const { settings } = getBlockState( block );
	const response = await fetch(
		addQueryArgs( settings.endpointUrl, params )
	);

	if ( ! response.ok ) {
		throw new Error( 'Unable to load locations.' );
	}

	const data = await response.json();

	return Array.isArray( data ) ? data : data.locations || [];
};

/**
 * Parses a failed geocode response into a throwable error.
 *
 * @param {Response} response Fetch response object.
 * @return {Promise<Error>} Error with status and optional REST code.
 */
const parseGeocodeError = async ( response ) => {
	const error = new Error( 'Unable to geocode search query.' );
	error.status = response.status;

	try {
		const data = await response.json();
		error.code = data?.code;
		error.message = data?.message || error.message;
	} catch {
		// Response body was not JSON.
	}

	return error;
};

/**
 * Geocodes a free-text search query through the server-side proxy.
 *
 * @param {HTMLElement} block Block wrapper element.
 * @param {string}      query User-entered search text.
 * @return {Promise<{ lat: number, lng: number, name?: string }>} Geocoded coordinates.
 */
const geocodeSearchQuery = async ( block, query ) => {
	const { settings } = getBlockState( block );
	const response = await fetch(
		addQueryArgs( settings.geocodeUrl, { q: query } )
	);

	if ( ! response.ok ) {
		throw await parseGeocodeError( response );
	}

	return response.json();
};

/**
 * Loads locations for a search and updates the map UI.
 *
 * @param {HTMLElement}                                   block                   Block wrapper element.
 * @param {Object}                                        searchContext           Search coordinates and strategy.
 * @param {number}                                        searchContext.lat       Search latitude.
 * @param {number}                                        searchContext.lng       Search longitude.
 * @param {string}                                        [searchContext.name=''] Label for search result messaging.
 * @param {import('./js/search-resolver').LocationSearch} [searchContext.search]  Resolved search strategy.
 */
const loadLocationsForSearch = async (
	block,
	{ lat, lng, name = '', search = { mode: 'radius' } }
) => {
	const blockState = getBlockState( block );

	setLoading( block, true );

	try {
		const params =
			search.mode === 'area'
				? getAreaSearchParams( search )
				: {
						lat,
						lng,
						r: blockState.settings.searchRadius,
				  };

		const locations = await fetchLocations( block, params );

		await renderLocations( block, locations, name, { lat, lng }, search );
	} catch {
		await renderLocations( block, [] );
		showErrorMessage(
			block,
			__( 'Unable to load locations. Please try again.', 'tribe' )
		);
	} finally {
		setLoading( block, false );
	}
};

/**
 * Handles sidebar search form submission.
 *
 * @param {HTMLElement} block Block wrapper element.
 * @param {string}      query Raw search input value.
 */
const handleSearchSubmit = async ( block, query ) => {
	const trimmedQuery = query.trim();

	if ( ! trimmedQuery ) {
		return;
	}

	setLoading( block, true );
	hideResultMessages( block );

	try {
		const geocoded = await geocodeSearchQuery( block, trimmedQuery );
		setLoading( block, false );
		await loadLocationsForSearch( block, {
			lat: geocoded.lat,
			lng: geocoded.lng,
			name: geocoded.name || trimmedQuery,
			search: geocoded.search || { mode: 'radius' },
		} );
	} catch ( error ) {
		setLoading( block, false );

		if (
			error?.code === 'tribe_geocode_not_found' ||
			error?.status === 422
		) {
			showNoResultsMessage( block, trimmedQuery );
			return;
		}

		showErrorMessage(
			block,
			error?.message ||
				__(
					'Unable to search for that location. Please try again.',
					'tribe'
				)
		);
	}
};

/**
 * Uses the browser geolocation API to search near the visitor.
 *
 * @param {HTMLElement} block Block wrapper element.
 */
const handleUseMyLocation = ( block ) => {
	if ( ! navigator.geolocation ) {
		showErrorMessage(
			block,
			__( 'Your browser does not support location access.', 'tribe' )
		);
		return;
	}

	hideResultMessages( block );

	navigator.geolocation.getCurrentPosition(
		( position ) => {
			loadLocationsForSearch( block, {
				lat: position.coords.latitude,
				lng: position.coords.longitude,
				name: __( 'your location', 'tribe' ),
			} );
		},
		() => {
			showErrorMessage(
				block,
				__(
					'Unable to access your location. Please check browser permissions or search by address.',
					'tribe'
				)
			);
		},
		{ timeout: 10000 }
	);
};

/**
 * Focuses the map when a list item's "Show on map" action is clicked.
 *
 * @param {HTMLElement} block Block wrapper element.
 * @param {Event}       event Click event.
 */
const handleShowOnMapClick = ( block, event ) => {
	const { map, locations } = getBlockState( block );
	const button = event.target.closest(
		'[data-js="location-map-show-on-map"]'
	);

	if ( ! button ) {
		return;
	}

	const lat = parseFloat( button.getAttribute( 'data-lat' ) );
	const lng = parseFloat( button.getAttribute( 'data-lng' ) );

	if ( Number.isNaN( lat ) || Number.isNaN( lng ) ) {
		return;
	}

	const index = locations.findIndex(
		( location ) =>
			parseFloat( location.lat ) === lat &&
			parseFloat( location.lng ) === lng
	);

	if ( index >= 0 ) {
		setActiveMarker( map, index );
	}

	focusMap( map, lat, lng, 16 );

	const card = button.closest( selectors.locationCard );

	if ( card ) {
		block
			.querySelectorAll( selectors.locationCard )
			.forEach( ( element ) => element.classList.remove( 'is-active' ) );
		card.classList.add( 'is-active' );
	}
};

/**
 * Binds interactive events for a single block instance.
 *
 * @param {HTMLElement} block Block wrapper element.
 */
const bindEvents = ( block ) => {
	const form = block.querySelector( selectors.form );
	const searchInput = block.querySelector( selectors.searchInput );
	const useLocationButton = block.querySelector(
		selectors.useLocationButton
	);
	const mobileToggle = block.querySelector( selectors.mobileToggle );

	if ( form && searchInput ) {
		form.addEventListener( 'submit', ( event ) => {
			event.preventDefault();
			handleSearchSubmit( block, searchInput.value );
		} );
	}

	if ( useLocationButton ) {
		useLocationButton.addEventListener( 'click', () =>
			handleUseMyLocation( block )
		);
	}

	if ( mobileToggle ) {
		mobileToggle.addEventListener( 'click', () => {
			const locations = block.querySelector( selectors.locations );
			const isOpen = locations?.classList.toggle( 'is-mobile-open' );
			const label = mobileToggle.querySelector(
				'.b-location-map__locations-mobile-toggle-text'
			);

			if ( label ) {
				label.textContent = isOpen
					? mobileToggle.getAttribute( 'data-on-text' )
					: mobileToggle.getAttribute( 'data-off-text' );
			}

			invalidateMapSize( getBlockState( block ).map );
		} );
	}

	block.addEventListener( 'click', ( event ) =>
		handleShowOnMapClick( block, event )
	);
};

/**
 * Initializes map rendering and data loading for one block instance.
 *
 * @param {HTMLElement} block Block wrapper element.
 */
const initBlock = async ( block ) => {
	const canvas = block.querySelector( selectors.canvas );

	if ( ! canvas || typeof google === 'undefined' ) {
		return;
	}

	const blockState = getBlockState( block );

	blockState.settings = parseJsonAttribute( block, 'data-map-settings', {} );

	blockState.map = await createMap( canvas, blockState.settings );

	if ( ! blockState.map ) {
		return;
	}

	const initialLocations = parseJsonAttribute(
		block,
		'data-map-locations',
		[]
	);

	bindEvents( block );

	const searchInput = block.querySelector( selectors.searchInput );

	if ( blockState.settings.showSearch && searchInput ) {
		if ( blockState.settings.autocompleteEnabled !== false ) {
			initSearchAutocomplete(
				block,
				searchInput,
				blockState.map,
				blockState.settings,
				( place ) =>
					loadLocationsForSearch( block, {
						lat: place.lat,
						lng: place.lng,
						name: place.name,
						search: place.search || { mode: 'radius' },
					} )
			);
		}
	}

	if ( initialLocations.length ) {
		await renderLocations( block, initialLocations );
		requestAnimationFrame( () => invalidateMapSize( blockState.map ) );
		return;
	}

	if ( blockState.settings.locationSource === 'endpoint' ) {
		setLoading( block, true );
		fetchLocations( block )
			.then( ( locations ) => renderLocations( block, locations ) )
			.catch( () => {
				renderLocations( block, [] );
				showErrorMessage(
					block,
					__( 'Unable to load locations. Please try again.', 'tribe' )
				);
			} )
			.finally( () => setLoading( block, false ) );
	}

	requestAnimationFrame( () => invalidateMapSize( blockState.map ) );
};

/**
 * Initializes every location map block present on the page.
 */
const init = () => {
	if ( typeof google === 'undefined' ) {
		return;
	}

	document.querySelectorAll( selectors.block ).forEach( ( block ) => {
		initBlock( block ).catch( () => {} );
	} );
};

ready( init );
