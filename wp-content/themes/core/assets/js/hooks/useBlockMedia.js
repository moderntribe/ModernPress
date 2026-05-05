import { useSelect } from '@wordpress/data';

/**
 * REST query passed to `getEntityRecord` for attachment posts. Kept as a stable
 * reference so `hasFinishedResolution` matches the resolver cache key (same
 * pattern as core’s post featured image control).
 */
const ATTACHMENT_RECORD_QUERY = { context: 'view' };

/**
 * Subscribe to a media attachment from the `core` data store via
 * `getEntityRecord( 'postType', 'attachment', id, query )`.
 *
 * Uses the attachment post type instead of the legacy `getMedia` shortcut so
 * this stays aligned with current Gutenberg data APIs.
 *
 * @param {number|string} mediaId Attachment ID from block attributes, or 0 / empty when none. Numeric strings are coerced.
 * @return {{ media: Object|undefined|null, hasResolved: boolean, isLoading: boolean, isMissing: boolean }} `media` is `undefined` while loading; falsy after resolution if the attachment is missing or was deleted.
 */
export function useBlockMedia( mediaId ) {
	let id = 0;
	if ( typeof mediaId === 'number' && mediaId > 0 ) {
		id = mediaId;
	} else {
		const n = Number( mediaId );
		if ( n > 0 ) {
			id = n;
		}
	}

	return useSelect(
		( select ) => {
			if ( ! id ) {
				return {
					media: undefined,
					hasResolved: true,
					isLoading: false,
					isMissing: false,
				};
			}

			const { getEntityRecord, hasFinishedResolution } = select( 'core' );
			const media = getEntityRecord(
				'postType',
				'attachment',
				id,
				ATTACHMENT_RECORD_QUERY
			);
			const hasResolved = hasFinishedResolution( 'getEntityRecord', [
				'postType',
				'attachment',
				id,
				ATTACHMENT_RECORD_QUERY,
			] );

			return {
				media,
				hasResolved,
				isLoading: ! hasResolved,
				isMissing: hasResolved && ! media,
			};
		},
		[ id ]
	);
}
