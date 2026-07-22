/* global MutationObserver */

/**
 * @module location-map/editor-preview
 *
 * @description Hydrates the server-rendered location map preview in the editor.
 */

import { useEffect, useRef } from '@wordpress/element';
import { ServerSideRender } from '@wordpress/server-side-render';

import { initLocationMapBlock } from './view';
import { waitForGoogleMaps } from './js/wait-for-google-maps';

const BLOCK_SELECTOR = '.b-location-map';
const INITIALIZED_ATTRIBUTE = 'data-location-map-initialized';

/**
 * Initializes an unhydrated location map block inside a container.
 *
 * @param {HTMLElement|null} container Preview wrapper element.
 */
const hydratePreview = async ( container ) => {
	if ( ! container ) {
		return;
	}

	await waitForGoogleMaps();

	const block = container.querySelector(
		`${ BLOCK_SELECTOR }:not([${ INITIALIZED_ATTRIBUTE }="true"])`
	);

	if ( ! block ) {
		return;
	}

	block.setAttribute( INITIALIZED_ATTRIBUTE, 'true' );
	await initLocationMapBlock( block );
};

/**
 * Server-rendered block preview that initializes the interactive map in-editor.
 *
 * @param {Object} props            Component props.
 * @param {Object} props.attributes Block attributes passed to SSR.
 * @return {import('react').JSX.Element} Editor preview markup.
 */
export default function EditorPreview( { attributes } ) {
	const previewRef = useRef( null );

	useEffect( () => {
		let cancelled = false;
		const container = previewRef.current;

		if ( ! container ) {
			return undefined;
		}

		const tryHydrate = () => {
			if ( cancelled ) {
				return;
			}

			hydratePreview( container ).catch( () => {} );
		};

		const observer = new MutationObserver( tryHydrate );

		observer.observe( container, {
			childList: true,
			subtree: true,
		} );

		tryHydrate();

		return () => {
			cancelled = true;
			observer.disconnect();
		};
	}, [ attributes ] );

	return (
		<div ref={ previewRef } className="b-location-map__editor-preview">
			<ServerSideRender
				block="tribe/location-map"
				attributes={ attributes }
			/>
		</div>
	);
}
