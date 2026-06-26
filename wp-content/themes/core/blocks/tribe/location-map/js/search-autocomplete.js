/**
 * @module location-map/js/search-autocomplete
 *
 * @description Debounced Google Places autocomplete for the location map search field.
 */

/* global google */

import { autocompleteSuggestions } from '../templates';
import { resolveLocationSearch } from './search-resolver';

/** @type {Object<string, string>} DOM selectors used by autocomplete. */
const selectors = {
	list: '[data-js="location-map-autocomplete"]',
	item: '[data-js="location-map-autocomplete-item"]',
};

/**
 * @typedef {Object} SearchAutocompleteSettings
 * @property {number} [autocompleteMinChars] Minimum characters before suggestions load.
 * @property {number} [autocompleteDebounce] Debounce delay in milliseconds.
 * @property {string} [searchCountry]        ISO country code for Places restrictions.
 */

/**
 * @typedef {Object} PlaceSelection
 * @property {number}                                     lat    Selected place latitude.
 * @property {number}                                     lng    Selected place longitude.
 * @property {string}                                     name   Display label for the selected place.
 * @property {import('./search-resolver').LocationSearch} search Resolved search strategy.
 */

/**
 * @typedef {Object} AutocompleteState
 * @property {HTMLElement|null}                              list            Suggestion list element.
 * @property {number|null}                                   debounceTimer   Active debounce timer ID.
 * @property {Object|null}                                   service         AutocompleteService instance.
 * @property {Object|null}                                   placesService   PlacesService instance.
 * @property {boolean}                                       listenersBound  Whether event listeners are bound.
 * @property {number}                                        activeIndex     Highlighted suggestion index (-1 when none).
 * @property {(place: PlaceSelection) => void|Promise<void>} onPlaceSelected Place selection callback.
 */

/** @type {WeakMap<HTMLElement, AutocompleteState>} Per-block autocomplete state. */
const autocompleteStates = new WeakMap();

/**
 * Returns autocomplete runtime state for a block instance.
 *
 * @param {HTMLElement} block Block wrapper element.
 * @return {AutocompleteState} Autocomplete runtime state.
 */
const getAutocompleteState = ( block ) => {
	if ( ! autocompleteStates.has( block ) ) {
		autocompleteStates.set( block, {
			list: null,
			debounceTimer: null,
			service: null,
			placesService: null,
			listenersBound: false,
			activeIndex: -1,
			onPlaceSelected: () => {},
		} );
	}

	return autocompleteStates.get( block );
};

/**
 * Returns suggestion option buttons in the current list.
 *
 * @param {AutocompleteState} state Autocomplete runtime state.
 * @return {HTMLElement[]} Suggestion option buttons.
 */
const getOptionButtons = ( state ) =>
	state.list
		? Array.from( state.list.querySelectorAll( selectors.item ) )
		: [];

/**
 * Returns whether the suggestion list is visible with options.
 *
 * @param {AutocompleteState} state Autocomplete runtime state.
 * @return {boolean} Whether suggestions are open.
 */
const isListOpen = ( state ) =>
	Boolean(
		state.list && ! state.list.hidden && getOptionButtons( state ).length
	);

/**
 * Clears the highlighted suggestion without closing the list.
 *
 * @param {AutocompleteState} state       Autocomplete runtime state.
 * @param {HTMLElement}       searchInput Search input element.
 */
const clearActiveOption = ( state, searchInput ) => {
	getOptionButtons( state ).forEach( ( button ) => {
		button.setAttribute( 'aria-selected', 'false' );
		button.classList.remove( 'is-active' );
	} );

	state.activeIndex = -1;
	searchInput.removeAttribute( 'aria-activedescendant' );
};

/**
 * Highlights a suggestion for keyboard navigation.
 *
 * @param {AutocompleteState} state       Autocomplete runtime state.
 * @param {HTMLElement}       searchInput Search input element.
 * @param {number}            index       Zero-based option index.
 */
const setActiveOption = ( state, searchInput, index ) => {
	const options = getOptionButtons( state );

	if ( ! options.length || index < 0 || index >= options.length ) {
		clearActiveOption( state, searchInput );
		return;
	}

	options.forEach( ( button, optionIndex ) => {
		const isActive = optionIndex === index;

		button.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );
		button.classList.toggle( 'is-active', isActive );

		if ( isActive ) {
			searchInput.setAttribute( 'aria-activedescendant', button.id );
			button.scrollIntoView( { block: 'nearest' } );
		}
	} );

	state.activeIndex = index;
};

/**
 * Hides the suggestion list and clears keyboard highlight state.
 *
 * @param {AutocompleteState} state       Autocomplete runtime state.
 * @param {HTMLElement}       searchInput Search input element.
 */
const hideSuggestions = ( state, searchInput ) => {
	if ( ! state.list ) {
		return;
	}

	state.list.hidden = true;
	state.list.innerHTML = '';
	state.activeIndex = -1;

	if ( searchInput ) {
		searchInput.setAttribute( 'aria-expanded', 'false' );
		searchInput.removeAttribute( 'aria-activedescendant' );
	}
};

/**
 * Returns normalized autocomplete settings.
 *
 * @param {SearchAutocompleteSettings} settings Block settings from SSR.
 * @return {{ minChars: number, debounceMs: number }} Normalized autocomplete settings.
 */
const getAutocompleteSettings = ( settings ) => {
	const minChars = Number( settings.autocompleteMinChars );
	const debounceMs = Number( settings.autocompleteDebounce );

	return {
		minChars: Number.isFinite( minChars ) && minChars > 0 ? minChars : 3,
		debounceMs:
			Number.isFinite( debounceMs ) && debounceMs >= 0 ? debounceMs : 500,
		searchCountry:
			typeof settings.searchCountry === 'string' &&
			settings.searchCountry.trim() !== ''
				? settings.searchCountry.trim().toLowerCase()
				: 'us',
	};
};

/**
 * Lazily initializes Google Places services for a block instance.
 *
 * @param {AutocompleteState} state Autocomplete runtime state.
 * @param {Object}            map   Google Map instance.
 * @return {Promise<{ service: Object, placesService: Object }|null>} Places services.
 */
const getServices = async ( state, map ) => {
	if ( typeof google === 'undefined' || ! google.maps?.importLibrary ) {
		return null;
	}

	if ( state.service && state.placesService ) {
		return {
			service: state.service,
			placesService: state.placesService,
		};
	}

	const placesLibrary = await google.maps.importLibrary( 'places' );

	if ( ! map ) {
		return null;
	}

	state.service = new placesLibrary.AutocompleteService();
	state.placesService = new placesLibrary.PlacesService( map );

	return {
		service: state.service,
		placesService: state.placesService,
	};
};

/**
 * Resolves a place ID into coordinates and a display label.
 *
 * @param {Object} placesService PlacesService instance.
 * @param {string} placeId       Google Places place ID.
 * @param {string} fallbackName  Label used when the place has no name.
 * @return {Promise<PlaceSelection|null>} Resolved place coordinates.
 */
const resolvePlaceById = ( placesService, placeId, fallbackName ) =>
	new Promise( ( resolve ) => {
		if ( ! placeId ) {
			resolve( null );
			return;
		}

		placesService.getDetails(
			{
				placeId,
				fields: [
					'geometry',
					'name',
					'formatted_address',
					'types',
					'address_components',
				],
			},
			( place, status ) => {
				const location = place?.geometry?.location;

				if ( status !== 'OK' || ! location ) {
					resolve( null );
					return;
				}

				resolve( {
					lat: location.lat(),
					lng: location.lng(),
					name: place.name || fallbackName || '',
					search: resolveLocationSearch( place ),
				} );
			}
		);
	} );

/**
 * Renders autocomplete suggestions below the search input.
 *
 * @param {AutocompleteState} state       Autocomplete runtime state.
 * @param {HTMLElement}       searchInput Search input element.
 * @param {Object[]}          predictions Place predictions from Google Places.
 */
const renderSuggestions = ( state, searchInput, predictions ) => {
	if ( ! state.list ) {
		return;
	}

	const listId = state.list.id || 'location-map-autocomplete';

	state.list.innerHTML = autocompleteSuggestions(
		predictions.map( ( prediction, index ) => ( {
			optionId: `${ listId }-option-${ index }`,
			placeId: prediction.place_id,
			mainText:
				prediction.structured_formatting?.main_text ||
				prediction.description ||
				'',
			secondaryText:
				prediction.structured_formatting?.secondary_text || '',
		} ) )
	);

	const hasSuggestions = predictions.length > 0;
	state.list.hidden = ! hasSuggestions;
	state.activeIndex = -1;

	if ( searchInput ) {
		searchInput.setAttribute(
			'aria-expanded',
			hasSuggestions ? 'true' : 'false'
		);
		searchInput.removeAttribute( 'aria-activedescendant' );
	}
};

/**
 * Fetches and renders debounced place predictions for the current query.
 *
 * @param {AutocompleteState} state       Autocomplete runtime state.
 * @param {Object}            map         Google Map instance.
 * @param {HTMLElement}       searchInput Search input element.
 * @param {string}            query       Current search query.
 * @param {string}            country     ISO country code restriction.
 */
const updateSuggestions = async ( state, map, searchInput, query, country ) => {
	const services = await getServices( state, map );

	if ( ! services ) {
		return;
	}

	services.service.getPlacePredictions(
		{
			input: query,
			componentRestrictions: { country: [ country ] },
		},
		( predictions, status ) => {
			if ( status !== 'OK' || ! predictions?.length ) {
				hideSuggestions( state, searchInput );
				return;
			}

			renderSuggestions( state, searchInput, predictions );
		}
	);
};

/**
 * Handles selection of an autocomplete suggestion.
 *
 * @param {HTMLElement}       searchInput Search input element.
 * @param {AutocompleteState} state       Autocomplete runtime state.
 * @param {Object}            map         Google Map instance.
 * @param {HTMLElement}       button      Selected suggestion button.
 */
const handleSuggestionSelect = async ( searchInput, state, map, button ) => {
	const services = await getServices( state, map );

	if ( ! services ) {
		return;
	}

	const placeId = button.getAttribute( 'data-place-id' ) || '';
	const fallbackName = button.getAttribute( 'data-label' ) || '';
	const place = await resolvePlaceById(
		services.placesService,
		placeId,
		fallbackName
	);

	if ( ! place ) {
		return;
	}

	searchInput.value = place.name;
	hideSuggestions( state, searchInput );
	await state.onPlaceSelected( place );
};

/**
 * Binds autocomplete interaction events for a block instance.
 *
 * @param {HTMLElement}                                                     block       Block wrapper element.
 * @param {HTMLElement}                                                     searchInput Search input element.
 * @param {AutocompleteState}                                               state       Autocomplete runtime state.
 * @param {Object}                                                          map         Google Map instance.
 * @param {{ minChars: number, debounceMs: number, searchCountry: string }} config      Normalized autocomplete settings.
 */
const bindAutocompleteEvents = ( block, searchInput, state, map, config ) => {
	const wrapper = searchInput.closest( '.b-location-map__search-form' );

	if ( ! wrapper || ! state.list || state.listenersBound ) {
		return;
	}

	state.listenersBound = true;

	searchInput.addEventListener( 'input', () => {
		if ( state.debounceTimer ) {
			window.clearTimeout( state.debounceTimer );
		}

		state.debounceTimer = window.setTimeout( () => {
			state.debounceTimer = null;

			const query = searchInput.value.trim();

			if ( query.length < config.minChars ) {
				hideSuggestions( state, searchInput );
				return;
			}

			updateSuggestions(
				state,
				map,
				searchInput,
				query,
				config.searchCountry
			);
		}, config.debounceMs );
	} );

	searchInput.addEventListener( 'keydown', ( event ) => {
		const options = getOptionButtons( state );

		switch ( event.key ) {
			case 'Escape':
				if ( isListOpen( state ) ) {
					event.preventDefault();
					hideSuggestions( state, searchInput );
				}
				break;

			case 'ArrowDown':
				event.preventDefault();

				if ( isListOpen( state ) && options.length ) {
					setActiveOption(
						state,
						searchInput,
						state.activeIndex < options.length - 1
							? state.activeIndex + 1
							: 0
					);
					break;
				}

				if ( searchInput.value.trim().length >= config.minChars ) {
					updateSuggestions(
						state,
						map,
						searchInput,
						searchInput.value.trim(),
						config.searchCountry
					).then( () => {
						if ( isListOpen( state ) ) {
							setActiveOption( state, searchInput, 0 );
						}
					} );
				}
				break;

			case 'ArrowUp':
				event.preventDefault();

				if ( isListOpen( state ) && options.length ) {
					setActiveOption(
						state,
						searchInput,
						state.activeIndex > 0
							? state.activeIndex - 1
							: options.length - 1
					);
				}
				break;

			case 'Enter':
				if (
					isListOpen( state ) &&
					state.activeIndex >= 0 &&
					options[ state.activeIndex ]
				) {
					event.preventDefault();
					handleSuggestionSelect(
						searchInput,
						state,
						map,
						options[ state.activeIndex ]
					);
				}
				break;

			default:
				break;
		}
	} );

	searchInput.addEventListener( 'focusout', ( event ) => {
		const nextTarget = event.relatedTarget;

		if ( nextTarget && state.list?.contains( nextTarget ) ) {
			return;
		}

		hideSuggestions( state, searchInput );
	} );

	state.list.addEventListener( 'keydown', ( event ) => {
		if ( event.key === 'Escape' ) {
			event.preventDefault();
			hideSuggestions( state, searchInput );
			searchInput.focus();
		}
	} );

	state.list.addEventListener( 'focusout', ( event ) => {
		const nextTarget = event.relatedTarget;

		if ( nextTarget === searchInput || state.list.contains( nextTarget ) ) {
			return;
		}

		hideSuggestions( state, searchInput );
	} );

	state.list.addEventListener( 'click', ( event ) => {
		const button = event.target.closest( selectors.item );

		if ( ! button || ! state.list.contains( button ) ) {
			return;
		}

		handleSuggestionSelect( searchInput, state, map, button );
	} );

	block.addEventListener( 'click', ( event ) => {
		if ( wrapper.contains( event.target ) ) {
			return;
		}

		hideSuggestions( state, searchInput );
	} );
};

/**
 * Initializes debounced Google Places autocomplete on the search input.
 *
 * @param {HTMLElement}                                   block           Block wrapper element.
 * @param {HTMLElement}                                   searchInput     Search input element.
 * @param {Object|null}                                   map             Google Map instance for PlacesService.
 * @param {SearchAutocompleteSettings}                    settings        Block settings from SSR.
 * @param {(place: PlaceSelection) => void|Promise<void>} onPlaceSelected Place selection callback.
 */
export const initSearchAutocomplete = (
	block,
	searchInput,
	map,
	settings,
	onPlaceSelected
) => {
	const list = block.querySelector( selectors.list );

	if ( ! list || ! map ) {
		return;
	}

	const state = getAutocompleteState( block );
	state.list = list;
	state.onPlaceSelected = onPlaceSelected;

	bindAutocompleteEvents(
		block,
		searchInput,
		state,
		map,
		getAutocompleteSettings( settings )
	);
};
