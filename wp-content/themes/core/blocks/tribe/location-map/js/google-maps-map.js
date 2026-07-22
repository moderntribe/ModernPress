/**
 * @module location-map/js/google-maps-map
 *
 * @description Google Maps helpers for the location map block.
 */

import { MarkerClusterer } from '@googlemaps/markerclusterer';

/**
 * @typedef {Object} MapSettings
 * @property {{ lat: number, lng: number }} [defaultCenter] Default map center.
 * @property {number}                       [defaultZoom]   Default zoom level.
 * @property {string}                       [mapId]         Google Maps Map ID for Advanced Markers.
 */

/**
 * @typedef {Object} MapLocation
 * @property {number|string} id              Location post ID.
 * @property {string}        title           Location title.
 * @property {number}        lat             Latitude coordinate.
 * @property {number}        lng             Longitude coordinate.
 * @property {string}        [address]       Formatted street address.
 * @property {string}        [phone]         Contact phone number.
 * @property {string}        [email]         Contact email address.
 * @property {string}        [hours]         Display hours text.
 * @property {string}        [url]           Permalink URL.
 * @property {string}        [directionsUrl] Google Maps directions URL.
 */

/**
 * @typedef {Object} SetMarkersOptions
 * @property {boolean}                                                        clusterMarkers  Whether to cluster overlapping markers.
 * @property {(marker: Object, index: number, location: MapLocation) => void} [onMarkerClick] Marker click callback.
 */

/** @return {Object|undefined} Google Maps global. */
const getGoogle = () => window.google;

/**
 * Returns whether a location has finite latitude and longitude values.
 *
 * @param {MapLocation} location Normalized location data.
 * @return {boolean} Whether coordinates are valid.
 */
export const hasValidCoordinates = ( location ) => {
	const lat = Number( location?.lat );
	const lng = Number( location?.lng );

	return Number.isFinite( lat ) && Number.isFinite( lng );
};

/**
 * Creates marker pin DOM content.
 *
 * @param {boolean} [isActive=false] Whether the marker is active.
 * @return {HTMLElement} Marker content element.
 */
const createMarkerContent = ( isActive = false ) => {
	const wrapper = document.createElement( 'div' );
	wrapper.className = `b-location-map__marker${
		isActive ? ' is-active' : ''
	}`;

	const pin = document.createElement( 'span' );
	pin.className = 'b-location-map__marker-pin';
	wrapper.appendChild( pin );

	return wrapper;
};

/**
 * Creates cluster marker DOM content.
 *
 * @param {number} count Number of markers in the cluster.
 * @return {HTMLElement} Cluster content element.
 */
const createClusterContent = ( count ) => {
	const wrapper = document.createElement( 'div' );
	wrapper.className = 'b-location-map__cluster';
	wrapper.textContent = String( count );

	return wrapper;
};

/**
 * Creates a Google Map instance inside the block canvas.
 *
 * @param {HTMLElement} container Map container element.
 * @param {MapSettings} settings  Map settings from block attributes.
 * @return {Promise<Object|null>} Google Map instance.
 */
export const createMap = async ( container, settings ) => {
	const google = getGoogle();

	if ( ! google?.maps?.importLibrary ) {
		return null;
	}

	const { Map } = await google.maps.importLibrary( 'maps' );
	const center = settings.defaultCenter || { lat: 39.10015, lng: -94.58327 };

	const map = new Map( container, {
		center,
		zoom: settings.defaultZoom || 11,
		mapId: settings.mapId || 'DEMO_MAP_ID',
		disableDefaultUI: true,
		scrollwheel: false,
		gestureHandling: 'cooperative',
	} );

	map.__locationMapMarkers = [];
	map.__locationMapClusterer = null;

	return map;
};

/**
 * Renders location markers and optionally clusters them.
 *
 * @param {Object|null}       map       Google Map instance.
 * @param {MapLocation[]}     locations Normalized location data.
 * @param {SetMarkersOptions} [options] Marker rendering options.
 * @return {Promise<Object[]>} Created marker instances.
 */
export const setMarkers = async ( map, locations, options = {} ) => {
	if ( ! map ) {
		return [];
	}

	const google = getGoogle();

	if ( ! google?.maps?.importLibrary ) {
		return [];
	}

	const { AdvancedMarkerElement } =
		await google.maps.importLibrary( 'marker' );

	if ( map.__locationMapClusterer ) {
		map.__locationMapClusterer.clearMarkers();
		map.__locationMapClusterer = null;
	}

	map.__locationMapMarkers.forEach( ( marker ) => {
		marker.map = null;
	} );
	map.__locationMapMarkers = [];

	const { clusterMarkers = true, onMarkerClick } = options;
	const markers = [];

	locations.forEach( ( location, index ) => {
		if ( ! hasValidCoordinates( location ) ) {
			return;
		}

		const marker = new AdvancedMarkerElement( {
			map: clusterMarkers && locations.length > 1 ? null : map,
			position: {
				lat: Number( location.lat ),
				lng: Number( location.lng ),
			},
			title: location.title,
			content: createMarkerContent( false ),
		} );

		marker.addListener( 'click', () => {
			if ( typeof onMarkerClick === 'function' ) {
				onMarkerClick( marker, index, location );
			}
		} );

		markers.push( marker );
	} );

	map.__locationMapMarkers = markers;

	if ( clusterMarkers && markers.length > 1 ) {
		map.__locationMapClusterer = new MarkerClusterer( {
			markers,
			map,
			renderer: {
				render: ( { count, position } ) =>
					new AdvancedMarkerElement( {
						position,
						content: createClusterContent( count ),
					} ),
			},
		} );
	} else {
		markers.forEach( ( marker ) => {
			marker.map = map;
		} );
	}

	return markers;
};

/**
 * Fits the map viewport to the provided locations.
 *
 * @param {Object|null}   map       Google Map instance.
 * @param {MapLocation[]} locations Normalized location data.
 */
export const fitMapToLocations = ( map, locations ) => {
	const google = getGoogle();
	const validLocations = locations.filter( hasValidCoordinates );

	if ( ! map || ! google?.maps || ! validLocations.length ) {
		return;
	}

	if ( validLocations.length === 1 ) {
		map.setCenter( {
			lat: Number( validLocations[ 0 ].lat ),
			lng: Number( validLocations[ 0 ].lng ),
		} );
		map.setZoom( 14 );
		return;
	}

	const bounds = new google.maps.LatLngBounds();

	validLocations.forEach( ( location ) => {
		bounds.extend( {
			lat: Number( location.lat ),
			lng: Number( location.lng ),
		} );
	} );

	map.fitBounds( bounds, 40 );
};

/**
 * Updates marker icons to reflect the active list item.
 *
 * @param {Object|null} map   Google Map instance.
 * @param {number}      index Active location index.
 */
export const setActiveMarker = ( map, index ) => {
	const markers = map?.__locationMapMarkers || [];

	markers.forEach( ( marker, markerIndex ) => {
		marker.content = createMarkerContent( markerIndex === index );
	} );
};

/**
 * Centers the map on a specific coordinate.
 *
 * @param {Object|null} map       Google Map instance.
 * @param {number}      lat       Target latitude.
 * @param {number}      lng       Target longitude.
 * @param {number}      [zoom=14] Target zoom level.
 */
export const focusMap = ( map, lat, lng, zoom = 14 ) => {
	if ( ! map ) {
		return;
	}

	map.setCenter( { lat, lng } );
	map.setZoom( zoom );
};

/**
 * Recalculates map dimensions after container size changes.
 *
 * @param {Object|null} map Google Map instance.
 */
export const invalidateMapSize = ( map ) => {
	const google = getGoogle();

	if ( map && google?.maps?.event ) {
		google.maps.event.trigger( map, 'resize' );
	}
};
