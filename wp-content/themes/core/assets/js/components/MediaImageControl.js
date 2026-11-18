import { __ } from '@wordpress/i18n';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import {
	Button,
	Fieldset,
	Spinner,
	Stack,
	VisuallyHidden,
} from '@wordpress/ui';
import { useBlockMedia } from 'hooks/useBlockMedia';

const previewTriggerStyle = {
	display: 'flex',
	alignItems: 'center',
	justifyContent: 'center',
	width: '100%',
	minHeight: '120px',
	boxSizing: 'border-box',
};

const missingMessageStyle = {
	textAlign: 'center',
	maxWidth: '100%',
	lineHeight: 1.4,
	padding: '0.5rem 0.25rem',
};

/**
 * Inspector control for choosing, previewing, replacing, and removing an image
 * from the media library (featured-image-style UI). Loads attachment data via
 * `useBlockMedia`; pass only `mediaId` from block attributes.
 *
 * @param {Object}                  props
 * @param {number|string}           props.mediaId        Attachment ID, or 0 when empty (coerced like `useBlockMedia`).
 * @param {(media: Object) => void} props.onSelect       Called with the selected media object from MediaUpload.
 * @param {() => void}              props.onRemove       Called when the user removes the image.
 * @param {string}                  [props.label]        Visual label for the control.
 * @param {string[]}                [props.allowedTypes] Passed to MediaUpload.
 * @param {string}                  [props.chooseLabel]  Empty-state button text.
 * @param {string}                  [props.replaceLabel] Replace flow label and button text.
 * @param {string}                  [props.removeLabel]  Remove button text.
 * @param {string}                  [props.missingLabel] Shown when the ID is set but the attachment cannot be loaded.
 */
export default function MediaImageControl( {
	mediaId,
	onSelect,
	onRemove,
	label = __( 'Image', 'tribe' ),
	allowedTypes = [ 'image' ],
	chooseLabel = __( 'Choose an image', 'tribe' ),
	replaceLabel = __( 'Replace image', 'tribe' ),
	removeLabel = __( 'Remove image', 'tribe' ),
	missingLabel = __(
		'Could not load image data. Try replacing the image.',
		'tribe'
	),
} ) {
	const { media, isLoading } = useBlockMedia( mediaId );
	const hasMedia = Number( mediaId ) > 0;
	const src = media?.source_url;
	const previewAlt =
		( media?.alt_text ?? media?.media_details?.alt ) ||
		__( 'Selected image', 'tribe' );

	return (
		<Fieldset.Root>
			<Fieldset.Legend>{ label }</Fieldset.Legend>
			<MediaUploadCheck>
				<MediaUpload
					title={ hasMedia ? replaceLabel : chooseLabel }
					allowedTypes={ allowedTypes }
					onSelect={ onSelect }
					value={ mediaId }
					render={ ( { open } ) => (
						<>
							<Button
								variant={ hasMedia ? 'unstyled' : 'outline' }
								tone="neutral"
								style={ previewTriggerStyle }
								onClick={ open }
								aria-busy={ isLoading }
							>
								{ ! hasMedia && chooseLabel }
								{ isLoading && (
									<>
										<VisuallyHidden render={ <span /> }>
											{ __( 'Loading image', 'tribe' ) }
										</VisuallyHidden>
										<Spinner />
									</>
								) }
								{ hasMedia &&
									! isLoading &&
									( src ? (
										<img
											src={ src }
											alt={ previewAlt }
											width={ media.media_details?.width }
											height={
												media.media_details?.height
											}
											style={ {
												maxWidth: '100%',
												height: 'auto',
											} }
										/>
									) : (
										<span style={ missingMessageStyle }>
											{ missingLabel }
										</span>
									) ) }
							</Button>
							{ hasMedia && (
								<Stack direction="row" gap="sm" wrap="wrap">
									<Button
										variant="outline"
										tone="neutral"
										onClick={ open }
									>
										{ replaceLabel }
									</Button>
									<Button
										variant="minimal"
										tone="neutral"
										onClick={ onRemove }
									>
										{ removeLabel }
									</Button>
								</Stack>
							) }
						</>
					) }
				/>
			</MediaUploadCheck>
		</Fieldset.Root>
	);
}
