/**
 * @module location-map/js/search-resolver
 *
 * @description Resolves whether a geocoded place should use radius or area search.
 */

/** @type {string[]} Google place types that should use radius search. */
const RADIUS_TYPES = [
	'street_address',
	'premise',
	'subpremise',
	'route',
	'street_number',
	'establishment',
	'point_of_interest',
	'intersection',
	'plus_code',
	'floor',
	'room',
];

/** @type {Record<string, string>} Full state name => abbreviation. */
const US_STATE_CODES = {
	alabama: 'AL',
	alaska: 'AK',
	arizona: 'AZ',
	arkansas: 'AR',
	california: 'CA',
	colorado: 'CO',
	connecticut: 'CT',
	delaware: 'DE',
	'district of columbia': 'DC',
	florida: 'FL',
	georgia: 'GA',
	hawaii: 'HI',
	idaho: 'ID',
	illinois: 'IL',
	indiana: 'IN',
	iowa: 'IA',
	kansas: 'KS',
	kentucky: 'KY',
	louisiana: 'LA',
	maine: 'ME',
	maryland: 'MD',
	massachusetts: 'MA',
	michigan: 'MI',
	minnesota: 'MN',
	mississippi: 'MS',
	missouri: 'MO',
	montana: 'MT',
	nebraska: 'NE',
	nevada: 'NV',
	'new hampshire': 'NH',
	'new jersey': 'NJ',
	'new mexico': 'NM',
	'new york': 'NY',
	'north carolina': 'NC',
	'north dakota': 'ND',
	ohio: 'OH',
	oklahoma: 'OK',
	oregon: 'OR',
	pennsylvania: 'PA',
	'rhode island': 'RI',
	'south carolina': 'SC',
	'south dakota': 'SD',
	tennessee: 'TN',
	texas: 'TX',
	utah: 'UT',
	vermont: 'VT',
	virginia: 'VA',
	washington: 'WA',
	'west virginia': 'WV',
	wisconsin: 'WI',
	wyoming: 'WY',
};

/**
 * @typedef {Object} AreaSearch
 * @property {'area'}               mode    Area-based search mode.
 * @property {'state'|'city'|'zip'} scope   Search scope.
 * @property {string}               [state] Normalized state abbreviation.
 * @property {string}               [city]  City name.
 * @property {string}               [zip]   ZIP code.
 */

/**
 * @typedef {Object} RadiusSearch
 * @property {'radius'} mode Radius-based search mode.
 */

/**
 * @typedef {AreaSearch|RadiusSearch} LocationSearch
 */

/**
 * @param {string} state Raw state value.
 * @return {string} Normalized state abbreviation.
 */
export const normalizeStateCode = ( state ) => {
	const trimmed = String( state || '' ).trim();

	if ( ! trimmed ) {
		return '';
	}

	const upper = trimmed.toUpperCase();

	if ( upper.length === 2 ) {
		return upper;
	}

	return US_STATE_CODES[ trimmed.toLowerCase() ] || upper;
};

/**
 * @param {string} zip Raw ZIP value.
 * @return {string} Normalized 5-digit ZIP.
 */
export const normalizeZip = ( zip ) => {
	const digits = String( zip || '' ).replace( /\D/g, '' );

	return digits.length >= 5 ? digits.slice( 0, 5 ) : digits;
};

/**
 * @param {string[]} types Google place types.
 * @param {string}   type  Type to check for.
 * @return {boolean} Whether the type is present.
 */
const hasType = ( types, type ) => types.includes( type );

/**
 * @param {Object[]} components Google address components.
 * @return {{ state: string, city: string, zip: string }} Parsed address parts.
 */
export const parseAddressComponents = ( components = [] ) => {
	const parsed = {
		state: '',
		city: '',
		zip: '',
	};

	components.forEach( ( component ) => {
		const types = component?.types || [];

		if (
			hasType( types, 'administrative_area_level_1' ) &&
			! parsed.state
		) {
			parsed.state = normalizeStateCode(
				component.short_name || component.long_name || ''
			);
		}

		if ( hasType( types, 'locality' ) && ! parsed.city ) {
			parsed.city = String( component.long_name || '' ).trim();
		}

		if ( hasType( types, 'sublocality' ) && ! parsed.city ) {
			parsed.city = String( component.long_name || '' ).trim();
		}

		if ( hasType( types, 'postal_code' ) && ! parsed.zip ) {
			parsed.zip = normalizeZip( component.long_name || '' );
		}
	} );

	return parsed;
};

/**
 * @param {string[]} types Google place types.
 * @return {boolean} Whether radius search should be used.
 */
const shouldUseRadiusSearch = ( types ) =>
	types.some( ( type ) => RADIUS_TYPES.includes( type ) );

/**
 * @param {{ types?: string[], address_components?: Object[] }} place Google place or geocode result.
 * @return {LocationSearch} Resolved search strategy.
 */
export const resolveLocationSearch = ( place ) => {
	const types = Array.isArray( place?.types ) ? place.types : [];

	if ( shouldUseRadiusSearch( types ) ) {
		return { mode: 'radius' };
	}

	const components = parseAddressComponents( place?.address_components );

	if ( hasType( types, 'postal_code' ) && components.zip ) {
		return {
			mode: 'area',
			scope: 'zip',
			zip: components.zip,
			state: components.state,
		};
	}

	if (
		( hasType( types, 'locality' ) || hasType( types, 'sublocality' ) ) &&
		components.city
	) {
		return {
			mode: 'area',
			scope: 'city',
			city: components.city,
			state: components.state,
		};
	}

	if ( hasType( types, 'administrative_area_level_1' ) && components.state ) {
		return {
			mode: 'area',
			scope: 'state',
			state: components.state,
		};
	}

	return { mode: 'radius' };
};

/**
 * @param {LocationSearch} search Resolved search strategy.
 * @return {Object<string, string>} REST query params for area search.
 */
export const getAreaSearchParams = ( search ) => {
	if ( search.mode !== 'area' ) {
		return {};
	}

	if ( search.scope === 'zip' && search.zip ) {
		return { zip: search.zip };
	}

	if ( search.scope === 'city' && search.city && search.state ) {
		return {
			city: search.city,
			state: search.state,
		};
	}

	if ( search.scope === 'state' && search.state ) {
		return { state: search.state };
	}

	return {};
};

/**
 * @param {LocationSearch} search       Resolved search strategy.
 * @param {string}         fallbackName Display label fallback.
 * @return {string} Human-readable area label.
 */
export const getAreaSearchLabel = ( search, fallbackName = '' ) => {
	if ( search.mode !== 'area' ) {
		return fallbackName;
	}

	if ( search.scope === 'zip' && search.zip ) {
		return search.zip;
	}

	if ( search.scope === 'city' && search.city ) {
		return search.state
			? `${ search.city }, ${ search.state }`
			: search.city;
	}

	if ( search.scope === 'state' && search.state ) {
		return search.state;
	}

	return fallbackName;
};
