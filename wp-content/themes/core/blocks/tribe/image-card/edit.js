import { __ } from '@wordpress/i18n';
import {
	BlockControls,
	InspectorControls,
	LinkControl,
	MediaUpload,
	MediaUploadCheck,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	BaseControl,
	Button,
	Flex,
	FlexItem,
	PanelBody,
	Popover,
	ResponsiveWrapper,
	TextareaControl,
	TextControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import { withSelect } from '@wordpress/data';
import { useMemo, useState } from '@wordpress/element';
import blockSettings from './block.json';

import './editor.pcss';

const Edit = ( { attributes, setAttributes, isSelected, media } ) => {
	const blockProps = useBlockProps();

	const {
		mediaId,
		title,
		description,
		linkUrl,
		linkOpensInNewTab,
		linkText,
		linkA11yLabel,
	} = attributes;

	/**
	 * Use internal state instead of a ref to make sure that the component
	 * re-renders when the popover's anchor updates.
	 */
	const [ isEditingUrl, setIsEditingUrl ] = useState( false );

	/**
	 * When using the LinkControl component, it is best practice to use memoization
	 * for handling edits to the value prop (url, target, etc.)
	 *
	 * @see https://github.com/WordPress/gutenberg/tree/trunk/packages/block-editor/src/components/link-control#value
	 */
	const memoizedLinkValue = useMemo(
		() => ( {
			url: linkUrl,
			opensInNewTab: linkOpensInNewTab,
			title: linkText,
		} ),
		[ linkUrl, linkOpensInNewTab, linkText ]
	);

	/**
	 * @function startEditingUrl
	 *
	 * @description Toggles the state of the popover for editing the link URL.
	 */
	const startEditingUrl = () => {
		setIsEditingUrl( ( state ) => ! state );
	};

	/**
	 * @function unlinkUrl
	 *
	 * @description Unlinks the URL by setting attributes to default values and closes the popover.
	 */
	const unlinkUrl = () => {
		setAttributes( {
			linkUrl: '',
			linkOpensInNewTab: false,
		} );

		setIsEditingUrl( false );
	};

	/**
	 * @function onSelectMedia
	 *
	 * @description Handles the selection of media from the media library.
	 *
	 * @param {Object} selectedMedia
	 */
	const onSelectMedia = ( selectedMedia ) => {
		setAttributes( {
			mediaId: selectedMedia.id,
			mediaUrl: selectedMedia.url,
		} );
	};

	/**
	 * @function removeMedia
	 *
	 * @description Removes the selected media by setting the media values to defaults.
	 */
	const removeMedia = () => {
		setAttributes( {
			mediaId: 0,
			mediaUrl: blockSettings?.attributes?.mediaUrl?.default || '',
		} );
	};

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block="tribe/image-card"
				attributes={ attributes }
			/>
			<BlockControls>
				<ToolbarGroup title={ __( 'Link', 'tribe' ) }>
					<ToolbarButton
						icon={ 'admin-links' }
						label={ __( 'Link', 'tribe' ) }
						onClick={ startEditingUrl }
						isActive={ !! linkUrl }
					/>
				</ToolbarGroup>
			</BlockControls>
			{ isEditingUrl && (
				<Popover
					onClose={ () => {
						setIsEditingUrl( false );
					} }
				>
					<LinkControl
						value={ memoizedLinkValue }
						onChange={ ( value ) =>
							setAttributes( {
								linkUrl: value.url,
								linkOpensInNewTab: value.opensInNewTab,
							} )
						}
						onRemove={ () => {
							unlinkUrl();
						} }
					/>
				</Popover>
			) }
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<BaseControl __nextHasNoMarginBottom>
							<BaseControl.VisualLabel>
								{ __( 'Image', 'tribe' ) }
							</BaseControl.VisualLabel>
							<MediaUploadCheck>
								<MediaUpload
									allowedTypes={ [ 'image' ] }
									onSelect={ onSelectMedia }
									value={ mediaId }
									render={ ( { open } ) => (
										<Button
											className={
												mediaId === 0
													? 'editor-post-featured-image__toggle'
													: 'editor-post-featured-image__preview'
											}
											onClick={ open }
										>
											{ mediaId === 0 &&
												__(
													'Choose an image',
													'tribe'
												) }
											{ media !== undefined && (
												<ResponsiveWrapper
													naturalWidth={
														media.media_details
															.width
													}
													naturalHeight={
														media.media_details
															.height
													}
												>
													<img
														src={ media.source_url }
														alt={
															media.media_details
																.alt
														}
													/>
												</ResponsiveWrapper>
											) }
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
												title={ __(
													'Replace image',
													'tribe'
												) }
												value={ mediaId }
												onSelect={ onSelectMedia }
												allowedTypes={ [ 'image' ] }
												render={ ( { open } ) => (
													<Button
														onClick={ open }
														variant="secondary"
													>
														{ __(
															'Replace image',
															'tribe'
														) }
													</Button>
												) }
											/>
										</MediaUploadCheck>
									</FlexItem>
									<FlexItem>
										<MediaUploadCheck>
											<Button
												onClick={ removeMedia }
												isLink
												isDestructive
											>
												{ __(
													'Remove image',
													'tribe'
												) }
											</Button>
										</MediaUploadCheck>
									</FlexItem>
								</Flex>
							) }
						</BaseControl>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Title', 'tribe' ) }
							value={ title }
							help={ __(
								'The title of the card. It should be descriptive, but relatively short.',
								'tribe'
							) }
							placeholder={ __(
								'Card title that is a little longer and fits on a few lines',
								'tribe'
							) }
							onChange={ ( value ) =>
								setAttributes( { title: value } )
							}
						/>
						<TextareaControl
							__nextHasNoMarginBottom
							label={ __( 'Description', 'tribe' ) }
							value={ description }
							help={ __(
								'The description of the card. It should give the viewer an overview of the content the card is linking to.',
								'tribe'
							) }
							placeholder={ __(
								'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
								'tribe'
							) }
							onChange={ ( value ) =>
								setAttributes( { description: value } )
							}
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Link Text', 'tribe' ) }
							value={ linkText }
							help={ __(
								'The visual text of the button component in the card.',
								'tribe'
							) }
							placeholder={ __( 'Link Label', 'tribe' ) }
							onChange={ ( value ) =>
								setAttributes( { linkText: value } )
							}
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Link A11y Text', 'tribe' ) }
							value={ linkA11yLabel }
							help={ __(
								'The hidden description of the card link. This is used for screen readers and should be descriptive of the link target.',
								'tribe'
							) }
							placeholder={ __( 'Link to Card Title', 'tribe' ) }
							onChange={ ( value ) =>
								setAttributes( { linkA11yLabel: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>
			) }
		</div>
	);
};

/**
 * Pass the media object to the Edit function as a prop.
 */
export default withSelect( ( select, props ) => {
	return {
		media: props.attributes.mediaId
			? select( 'core' ).getMedia( props.attributes.mediaId )
			: undefined,
	};
} )( Edit );
