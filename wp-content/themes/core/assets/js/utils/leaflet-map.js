import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';

const OSM_TILE_URL = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';

const OSM_TILE_ATTRIBUTION =
	'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

const DEFAULT_ICON = L.divIcon( {
	className: 'b-location-map__marker',
	html: '<span class="b-location-map__marker-pin"></span>',
	iconSize: [ 24, 32 ],
	iconAnchor: [ 12, 32 ],
} );

const ACTIVE_ICON = L.divIcon( {
	className: 'b-location-map__marker is-active',
	html: '<span class="b-location-map__marker-pin"></span>',
	iconSize: [ 24, 32 ],
	iconAnchor: [ 12, 32 ],
} );

/**
 * @param {HTMLElement} container Map container element.
 * @param {Object}      settings  Map settings from block attributes.
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
 * @param {import('leaflet').Map} map
 */
export const addTileLayer = ( map ) => {
	L.tileLayer( OSM_TILE_URL, {
		attribution: OSM_TILE_ATTRIBUTION,
		maxZoom: 19,
	} ).addTo( map );
};

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
 * @param {import('leaflet').Map}                                                            map
 * @param {MapLocation[]}                                                                    locations
 * @param {Object}                                                                           options
 * @param {boolean}                                                                          options.clusterMarkers
 * @param {(marker: import('leaflet').Marker, index: number, location: MapLocation) => void} [options.onMarkerClick]
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
 * @param {import('leaflet').Map} map
 * @param {MapLocation[]}         locations
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
 * @param {import('leaflet').Map} map
 * @param {number}                index
 */
export const setActiveMarker = ( map, index ) => {
	const markers = map.__locationMapMarkers || [];

	markers.forEach( ( marker, markerIndex ) => {
		marker.setIcon( markerIndex === index ? ACTIVE_ICON : DEFAULT_ICON );
	} );
};

/**
 * @param {import('leaflet').Map} map
 * @param {number}                lat
 * @param {number}                lng
 * @param {number}                [zoom=14]
 */
export const focusMap = ( map, lat, lng, zoom = 14 ) => {
	map.setView( [ lat, lng ], zoom );
};
