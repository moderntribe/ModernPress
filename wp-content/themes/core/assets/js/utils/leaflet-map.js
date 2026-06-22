/**
 * @module utils/leaflet-map
 *
 * @description Shared Leaflet helpers for the location map block.
 */

import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

/** @see https://operations.osmfoundation.org/policies/tiles/ */
const OSM_TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

const OSM_TILE_ATTRIBUTION =
	'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

/** Default marker icon shown for each location pin. */
const DEFAULT_ICON = L.divIcon( {
	className: 'b-location-map__marker',
	html: '<span class="b-location-map__marker-pin"></span>',
	iconSize: [ 24, 32 ],
	iconAnchor: [ 12, 32 ],
} );

/** Marker icon for the currently selected location. */
const ACTIVE_ICON = L.divIcon( {
	className: 'b-location-map__marker is-active',
	html: '<span class="b-location-map__marker-pin"></span>',
	iconSize: [ 24, 32 ],
	iconAnchor: [ 12, 32 ],
} );

/**
 * @typedef {Object} MapSettings
 * @property {{ lat: number, lng: number }} [defaultCenter] Default map center.
 * @property {number}                       [defaultZoom]   Default zoom level.
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
 * @property {boolean}                                                                          clusterMarkers  Whether to cluster overlapping markers.
 * @property {(marker: import('leaflet').Marker, index: number, location: MapLocation) => void} [onMarkerClick] Marker click callback.
 */

/**
 * Creates a Leaflet map instance inside the block canvas.
 *
 * @param {HTMLElement} container Map container element.
 * @param {MapSettings} settings  Map settings from block attributes.
 * @return {import('leaflet').Map} Leaflet map instance.
 */
export const createMap = ( container, settings ) => {
	const center = settings.defaultCenter || { lat: 39.10015, lng: -94.58327 };

	return L.map( container, {
		center: [ center.lat, center.lng ],
		zoom: settings.defaultZoom || 11,
		scrollWheelZoom: false,
	} );
};

/**
 * Adds the OpenStreetMap raster tile layer required by the location map.
 *
 * @param {import('leaflet').Map} map Leaflet map instance.
 */
export const addTileLayer = ( map ) => {
	L.tileLayer( OSM_TILE_URL, {
		attribution: OSM_TILE_ATTRIBUTION,
		maxZoom: 19,
	} ).addTo( map );
};

/**
 * Renders location markers and optionally clusters them.
 *
 * @param {import('leaflet').Map} map       Leaflet map instance.
 * @param {MapLocation[]}         locations Normalized location data.
 * @param {SetMarkersOptions}     [options] Marker rendering options.
 * @return {import('leaflet').Marker[]} Created marker instances.
 */
export const setMarkers = ( map, locations, options = {} ) => {
	const { clusterMarkers = true, onMarkerClick } = options;
	const markers = [];

	const clearTarget = map.__locationMapLayer;

	if ( clearTarget ) {
		map.removeLayer( clearTarget );
	}

	locations.forEach( ( location, index ) => {
		if ( ! location?.lat || ! location?.lng ) {
			return;
		}

		const marker = L.marker( [ location.lat, location.lng ], {
			icon: DEFAULT_ICON,
			title: location.title,
		} );

		marker.on( 'click', () => {
			if ( typeof onMarkerClick === 'function' ) {
				onMarkerClick( marker, index, location );
			}
		} );

		markers.push( marker );
	} );

	let layer;

	if ( clusterMarkers && markers.length > 1 ) {
		layer = L.markerClusterGroup();
		markers.forEach( ( marker ) => layer.addLayer( marker ) );
	} else {
		layer = L.layerGroup( markers );
	}

	layer.addTo( map );
	map.__locationMapLayer = layer;
	map.__locationMapMarkers = markers;

	return markers;
};

/**
 * Fits the map viewport to the provided locations.
 *
 * @param {import('leaflet').Map} map       Leaflet map instance.
 * @param {MapLocation[]}         locations Normalized location data.
 */
export const fitMapToLocations = ( map, locations ) => {
	const validLocations = locations.filter(
		( location ) => location?.lat && location?.lng
	);

	if ( ! validLocations.length ) {
		return;
	}

	if ( validLocations.length === 1 ) {
		map.setView( [ validLocations[ 0 ].lat, validLocations[ 0 ].lng ], 14 );
		return;
	}

	const bounds = L.latLngBounds(
		validLocations.map( ( location ) => [ location.lat, location.lng ] )
	);

	map.fitBounds( bounds, { padding: [ 40, 40 ] } );
};

/**
 * Updates marker icons to reflect the active list item.
 *
 * @param {import('leaflet').Map} map   Leaflet map instance.
 * @param {number}                index Active location index.
 */
export const setActiveMarker = ( map, index ) => {
	const markers = map.__locationMapMarkers || [];

	markers.forEach( ( marker, markerIndex ) => {
		marker.setIcon( markerIndex === index ? ACTIVE_ICON : DEFAULT_ICON );
	} );
};

/**
 * Centers the map on a specific coordinate.
 *
 * @param {import('leaflet').Map} map       Leaflet map instance.
 * @param {number}                lat       Target latitude.
 * @param {number}                lng       Target longitude.
 * @param {number}                [zoom=14] Target zoom level.
 */
export const focusMap = ( map, lat, lng, zoom = 14 ) => {
	map.setView( [ lat, lng ], zoom );
};
