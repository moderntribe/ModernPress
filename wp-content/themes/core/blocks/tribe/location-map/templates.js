import { __ } from '@wordpress/i18n';
import { escapeAttribute, escapeHTML } from '@wordpress/escape-html';

/**
 * @param {Object} data Normalized location data.
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
