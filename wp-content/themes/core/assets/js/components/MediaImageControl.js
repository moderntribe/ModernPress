import { __ } from '@wordpress/i18n';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import {
	BaseControl,
	Button,
	Flex,
	FlexItem,
	ResponsiveWrapper,
	Spinner,
	VisuallyHidden,
} from '@wordpress/components';
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
 * @param {Object}        props
 * @param {number|string} props.mediaId        Attachment ID, or 0 when empty (coerced like `useBlockMedia`).
 * @param {Function}      props.onSelect       Called with the selected media object from MediaUpload.
 * @param {Function}      props.onRemove       Called when the user removes the image.
 * @param {string}        [props.label]        Visual label for the control.
 * @param {string[]}      [props.allowedTypes] Passed to MediaUpload.
 * @param {string}        [props.chooseLabel]  Empty-state button text.
 * @param {string}        [props.replaceLabel] Replace flow label and button text.
 * @param {string}        [props.removeLabel]  Remove button text.
 * @param {string}        [props.missingLabel] Shown when the ID is set but the attachment cannot be loaded.
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
	const { media, isLoading, isMissing } = useBlockMedia( mediaId );

	const width = media?.media_details?.width;
	const height = media?.media_details?.height;
	const src = media?.source_url;
	const altFromMedia = media?.alt_text ?? media?.media_details?.alt ?? '';
	const previewAlt = altFromMedia || __( 'Selected image', 'tribe' );

	const previewImage =
		src &&
		( width && height ? (
			<ResponsiveWrapper naturalWidth={ width } naturalHeight={ height }>
				<img src={ src } alt={ previewAlt } />
			</ResponsiveWrapper>
		) : (
			<img src={ src } alt={ previewAlt } />
		) );

	return (
		<BaseControl __nextHasNoMarginBottom>
			<BaseControl.VisualLabel>{ label }</BaseControl.VisualLabel>
			<MediaUploadCheck>
				<MediaUpload
					allowedTypes={ allowedTypes }
					onSelect={ onSelect }
					value={ mediaId }
					render={ ( { open } ) => (
						<Button
							className={
								mediaId === 0
									? 'editor-post-featured-image__toggle'
									: 'editor-post-featured-image__preview'
							}
							style={
								mediaId !== 0 ? previewTriggerStyle : undefined
							}
							onClick={ open }
							aria-busy={ mediaId !== 0 && isLoading }
						>
							{ mediaId === 0 && chooseLabel }
							{ mediaId !== 0 && isLoading && (
								<>
									<VisuallyHidden as="span">
										{ __( 'Loading image', 'tribe' ) }
									</VisuallyHidden>
									<Spinner />
								</>
							) }
							{ mediaId !== 0 && ! isLoading && isMissing && (
								<span style={ missingMessageStyle }>
									{ missingLabel }
								</span>
							) }
							{ mediaId !== 0 &&
								! isLoading &&
								! isMissing &&
								previewImage }
						</Button>
					) }
				/>
			</MediaUploadCheck>
			{ mediaId !== 0 && (
				<Flex
					style={ {
						marginTop: '1rem',
					} }
				>
					<FlexItem>
						<MediaUploadCheck>
							<MediaUpload
								title={ replaceLabel }
								value={ mediaId }
								onSelect={ onSelect }
								allowedTypes={ allowedTypes }
								render={ ( { open } ) => (
									<Button
										onClick={ open }
										variant="secondary"
									>
										{ replaceLabel }
									</Button>
								) }
							/>
						</MediaUploadCheck>
					</FlexItem>
					<FlexItem>
						<MediaUploadCheck>
							<Button
								variant="link"
								isDestructive
								onClick={ onRemove }
							>
								{ removeLabel }
							</Button>
						</MediaUploadCheck>
					</FlexItem>
				</Flex>
			) }
		</BaseControl>
	);
}
