import { __ } from '@wordpress/i18n';
import { escapeAttribute, escapeHTML } from '@wordpress/escape-html';

/**
 * @typedef {Object} LocationCardData
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
 * Builds the markup for a single location card in the sidebar list.
 *
 * @param {LocationCardData} data Normalized location data.
 * @return {string} Location card HTML markup.
 */
export const locationCard = ( data ) => `
<li class="b-location-map__list-item">
	<article class="b-location-map__location" data-location-id="${ escapeAttribute(
		String( data.id )
	) }">
		<h3 class="b-location-map__location-title t-display-xx-small">
			${
				data.url
					? `<a href="${ escapeAttribute( data.url ) }">${ escapeHTML(
							data.title
					  ) }</a>`
					: escapeHTML( data.title )
			}
		</h3>
		${
			data.hours
				? `<p class="b-location-map__location-hours t-body">${ escapeHTML(
						data.hours
				  ) }</p>`
				: ''
		}
		${
			data.phone
				? `<p class="b-location-map__location-phone t-body"><a href="tel:${ escapeAttribute(
						data.phone
				  ) }">${ escapeHTML( data.phone ) }</a></p>`
				: ''
		}
		${
			data.email
				? `<p class="b-location-map__location-email t-body"><a href="mailto:${ escapeAttribute(
						data.email
				  ) }">${ escapeHTML( data.email ) }</a></p>`
				: ''
		}
		${
			data.address
				? `<p class="b-location-map__location-address t-body">${ escapeHTML(
						data.address
				  ) }</p>`
				: ''
		}
		<div class="b-location-map__location-actions">
			<button
				type="button"
				class="b-location-map__location-action"
				data-js="location-map-show-on-map"
				data-lat="${ escapeAttribute( String( data.lat ) ) }"
				data-lng="${ escapeAttribute( String( data.lng ) ) }"
			>
				${ __( 'Show on map', 'tribe' ) }
			</button>
			${
				data.directionsUrl
					? `<a class="b-location-map__location-action is-directions" href="${ escapeAttribute(
							data.directionsUrl
					  ) }" target="_blank" rel="noopener noreferrer">${ __(
							'Get directions',
							'tribe'
					  ) }</a>`
					: ''
			}
		</div>
	</article>
</li>
`;

/**
 * @typedef {Object} AutocompleteItemData
 * @property {string} optionId        Unique DOM id for aria-activedescendant.
 * @property {string} placeId         Google Places place ID.
 * @property {string} mainText        Primary suggestion label.
 * @property {string} [secondaryText] Secondary suggestion label.
 */

/**
 * Builds the markup for a single autocomplete suggestion.
 *
 * @param {AutocompleteItemData} data Place prediction display data.
 * @return {string} Autocomplete item HTML markup.
 */
export const autocompleteItem = ( data ) => `
<li class="b-location-map__autocomplete-item" role="presentation">
	<button
		type="button"
		id="${ escapeAttribute( data.optionId ) }"
		class="b-location-map__autocomplete-button"
		data-js="location-map-autocomplete-item"
		data-place-id="${ escapeAttribute( data.placeId ) }"
		data-label="${ escapeAttribute( data.mainText ) }"
		role="option"
		aria-selected="false"
	>
		<span class="b-location-map__autocomplete-main t-body">${ escapeHTML(
			data.mainText
		) }</span>
		${
			data.secondaryText
				? `<span class="b-location-map__autocomplete-secondary t-body">${ escapeHTML(
						data.secondaryText
				  ) }</span>`
				: ''
		}
	</button>
</li>
`;

/**
 * Builds markup for the full autocomplete suggestion list.
 *
 * @param {AutocompleteItemData[]} items Place prediction display data.
 * @return {string} Autocomplete list items HTML markup.
 */
export const autocompleteSuggestions = ( items ) =>
	items.map( ( item ) => autocompleteItem( item ) ).join( '' );
