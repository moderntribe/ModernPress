/**
 * @module location-map
 *
 * @description Leaflet-powered location map with optional search sidebar.
 */

import { __, sprintf } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { ready } from 'utils/events';
import {
	addTileLayer,
	createMap,
	fitMapToLocations,
	focusMap,
	setActiveMarker,
	setMarkers,
} from 'utils/leaflet-map';
import { locationCard } from './templates';

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
	mobileToggle: '[data-js="location-map-mobile-toggle"]',
	loading: '[data-js="location-map-loading"]',
	locationCard: '.b-location-map__location',
};

const state = {
	map: null,
	settings: {},
	locations: [],
	locationName: '',
};

const parseJsonAttribute = ( element, attribute, fallback ) => {
	try {
		return JSON.parse( element.getAttribute( attribute ) || '' );
	} catch {
		return fallback;
	}
};

const setLoading = ( isLoading ) => {
	const overlay = document.querySelector( selectors.loading );

	if ( ! overlay ) {
		return;
	}

	overlay.hidden = ! isLoading;
	overlay.classList.toggle( 'is-loading', isLoading );
};

const hideResultMessages = ( block ) => {
	const results = block.querySelector( selectors.results );
	const noResults = block.querySelector( selectors.noResults );

	if ( results ) {
		results.hidden = true;
		results.textContent = '';
	}

	if ( noResults ) {
		noResults.hidden = true;
		noResults.textContent = '';
	}
};

const showResultsMessage = ( block, count, locationName ) => {
	const results = block.querySelector( selectors.results );

	if ( ! results ) {
		return;
	}

	const label = locationName || __( 'your location', 'tribe' );

	results.textContent = sprintf(
		/* translators: 1: location count, 2: search radius in miles, 3: searched place name */
		__( 'Found %1$d locations within %2$d miles of %3$s.', 'tribe' ),
		count,
		state.settings.searchRadius || 30,
		label
	);
	results.hidden = false;
};

const showNoResultsMessage = ( block, locationName ) => {
	const noResults = block.querySelector( selectors.noResults );

	if ( ! noResults ) {
		return;
	}

	const label = locationName || __( 'your location', 'tribe' );

	noResults.textContent = sprintf(
		/* translators: 1: search radius in miles, 2: searched place name */
		__(
			'Sorry, no locations were found within %1$d miles of %2$s.',
			'tribe'
		),
		state.settings.searchRadius || 30,
		label
	);
	noResults.hidden = false;
};

const renderLocationList = ( block, locations ) => {
	const list = block.querySelector( selectors.list );

	if ( ! list ) {
		return;
	}

	list.innerHTML = locations
		.map( ( location ) => locationCard( location ) )
		.join( '' );
};

const handleMarkerClick = ( block, index ) => {
	setActiveMarker( state.map, index );

	const cards = block.querySelectorAll( selectors.locationCard );

	cards.forEach( ( card, cardIndex ) => {
		card.classList.toggle( 'is-active', cardIndex === index );
	} );

	const card = cards[ index ];

	if ( card ) {
		card.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
	}
};

const renderLocations = ( block, locations, locationName = '' ) => {
	state.locations = locations;
	hideResultMessages( block );
	renderLocationList( block, locations );

	setMarkers( state.map, locations, {
		clusterMarkers: state.settings.clusterMarkers,
		onMarkerClick: ( _marker, index ) => handleMarkerClick( block, index ),
	} );

	if ( state.settings.fitBounds ) {
		fitMapToLocations( state.map, locations );
	}

	if ( locations.length && locationName ) {
		showResultsMessage( block, locations.length, locationName );
	} else if ( ! locations.length && locationName ) {
		showNoResultsMessage( block, locationName );
	}
};

const fetchLocations = async ( params = {} ) => {
	const response = await fetch(
		addQueryArgs( state.settings.endpointUrl, params )
	);

	if ( ! response.ok ) {
		throw new Error( 'Unable to load locations.' );
	}

	const data = await response.json();

	return Array.isArray( data ) ? data : data.locations || [];
};

const geocodeSearchQuery = async ( query ) => {
	const response = await fetch(
		addQueryArgs( state.settings.geocodeUrl, { q: query } )
	);

	if ( ! response.ok ) {
		throw new Error( 'Unable to geocode search query.' );
	}

	return response.json();
};

const loadNearbyLocations = async ( block, lat, lng, locationName = '' ) => {
	setLoading( true );

	try {
		const locations = await fetchLocations( {
			lat,
			lng,
			r: state.settings.searchRadius,
		} );

		focusMap( state.map, lat, lng, 11 );
		renderLocations( block, locations, locationName );
	} catch {
		renderLocations( block, [], locationName );
	} finally {
		setLoading( false );
	}
};

const handleSearchSubmit = async ( block, query ) => {
	const trimmedQuery = query.trim();

	if ( ! trimmedQuery ) {
		return;
	}

	setLoading( true );

	try {
		const geocoded = await geocodeSearchQuery( trimmedQuery );
		await loadNearbyLocations(
			block,
			geocoded.lat,
			geocoded.lng,
			geocoded.name || trimmedQuery
		);
	} catch {
		showNoResultsMessage( block, trimmedQuery );
	} finally {
		setLoading( false );
	}
};

const handleUseMyLocation = ( block ) => {
	if ( ! navigator.geolocation ) {
		return;
	}

	navigator.geolocation.getCurrentPosition(
		( position ) => {
			loadNearbyLocations(
				block,
				position.coords.latitude,
				position.coords.longitude
			);
		},
		() => {
			showNoResultsMessage( block );
		},
		{ timeout: 10000 }
	);
};

const handleShowOnMapClick = ( block, event ) => {
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

	const index = state.locations.findIndex(
		( location ) =>
			parseFloat( location.lat ) === lat &&
			parseFloat( location.lng ) === lng
	);

	if ( index >= 0 ) {
		setActiveMarker( state.map, index );
	}

	focusMap( state.map, lat, lng, 16 );

	const card = button.closest( selectors.locationCard );

	if ( card ) {
		block
			.querySelectorAll( selectors.locationCard )
			.forEach( ( element ) => element.classList.remove( 'is-active' ) );
		card.classList.add( 'is-active' );
	}
};

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
		} );
	}

	block.addEventListener( 'click', ( event ) =>
		handleShowOnMapClick( block, event )
	);
};

const initBlock = ( block ) => {
	const canvas = block.querySelector( selectors.canvas );

	if ( ! canvas ) {
		return;
	}

	state.settings = parseJsonAttribute( block, 'data-map-settings', {} );
	const initialLocations = parseJsonAttribute(
		block,
		'data-map-locations',
		[]
	);

	state.map = createMap( canvas, state.settings );
	addTileLayer( state.map );

	bindEvents( block );

	if ( initialLocations.length ) {
		renderLocations( block, initialLocations );
		return;
	}

	if ( state.settings.locationSource === 'endpoint' ) {
		setLoading( true );
		fetchLocations()
			.then( ( locations ) => renderLocations( block, locations ) )
			.catch( () => renderLocations( block, [] ) )
			.finally( () => setLoading( false ) );
	}
};

const init = () => {
	document.querySelectorAll( selectors.block ).forEach( initBlock );
};

ready( init );
