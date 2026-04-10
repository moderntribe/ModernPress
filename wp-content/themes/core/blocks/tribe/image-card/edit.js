import { __ } from '@wordpress/i18n';
import {
	BlockControls,
	InspectorControls,
	LinkControl,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	Popover,
	TextareaControl,
	TextControl,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import MediaImageControl from 'components/MediaImageControl';
import { ServerSideRender } from '@wordpress/server-side-render';
import { useMemo, useState } from '@wordpress/element';
import blockSettings from './block.json';

import './editor.pcss';

export default function Edit( { attributes, setAttributes, isSelected } ) {
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
						<MediaImageControl
							mediaId={ mediaId }
							onSelect={ onSelectMedia }
							onRemove={ removeMedia }
						/>
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
}
