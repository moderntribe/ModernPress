import {
	BlockControls,
	InspectorControls,
	MediaPlaceholder,
	MediaReplaceFlow,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	RangeControl,
	ToolbarGroup,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { trash } from '@wordpress/icons';
import './editor.pcss';

import {
	closestCenter,
	DndContext,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import { arrayMove, SortableContext, useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { useCallback } from '@wordpress/element';

// Sortable item component
const SortableImage = ( {
	id,
	img,
	index,
	isSelected,
	removeImage,
	imageStyle,
} ) => {
	const { attributes, listeners, setNodeRef, transform, transition } =
		useSortable( { id } );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		cursor: 'move',
		position: 'relative',
	};

	return (
		<div
			ref={ setNodeRef }
			{ ...attributes }
			style={ style }
			className="gallery-item"
		>
			<div
				{ ...attributes }
				{ ...listeners }
				style={ {
					cursor: 'grab',
				} }
				aria-label="Drag to reorder"
			>
				<img
					src={ img.url }
					alt={ img.alt }
					data-id={ img.id }
					style={ imageStyle }
				/>
			</div>
			{ isSelected && (
				<Button
					className="remove-image-button"
					icon={ trash }
					aria-label={ __( 'Remove image' ) }
					onClick={ () => removeImage( index ) }
					style={ {
						position: 'absolute',
						top: '-15px',
						right: '-30px',
						backgroundColor: 'rgba(0,0,0,0.7)',
						color: 'white',
						padding: '0px',
						minWidth: '0',
						height: 'auto',
					} }
				/>
			) }
		</div>
	);
};

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const { marqueeSpeed, images } = attributes;
	const blockProps = useBlockProps();

	// Handle selection of new media items
	const onSelectImages = ( newImages ) => {
		setAttributes( {
			images: newImages.map( ( image ) => ( {
				id: image.id,
				url: image.url || image.source_url,
				alt: image.alt || image.alt_text || '',
			} ) ),
		} );
	};

	// Handle removing an image
	const removeImage = ( index ) => {
		const newImages = [ ...images ];
		newImages.splice( index, 1 );
		setAttributes( { images: newImages } );
	};

	// Handle drag-and-drop sort event
	const handleDragEnd = useCallback(
		( event ) => {
			const { active, over } = event;
			if ( active.id !== over?.id ) {
				const oldIndex = images.findIndex(
					( img ) => img.id === active.id
				);
				const newIndex = images.findIndex(
					( img ) => img.id === over.id
				);
				setAttributes( {
					images: arrayMove( images, oldIndex, newIndex ),
				} );
			}
		},
		[ images, setAttributes ]
	);

	// Basic image styles
	const imageStyle = {
		maxWidth: '200px',
		height: 'auto',
	};

	const sensors = useSensors( useSensor( PointerSensor ) );

	return (
		<>
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<RangeControl
							__next40pxDefaultSize
							label={ __( 'Marquee Speed', 'tribe' ) }
							help={ __(
								'Adjust the speed of the logo marquee animation.',
								'tribe'
							) }
							min={ 50 }
							max={ 1000 }
							step={ 50 }
							value={ marqueeSpeed }
							onChange={ ( value ) =>
								setAttributes( { marqueeSpeed: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>
			) }
			<BlockControls>
				{ images.length > 0 && (
					<ToolbarGroup>
						<MediaReplaceFlow
							mediaIds={ images.map( ( img ) => img.id ) }
							allowedTypes={ [ 'image' ] }
							accept="image/*"
							multiple
							onSelect={ onSelectImages }
						/>
					</ToolbarGroup>
				) }
			</BlockControls>
			<div { ...blockProps }>
				{ images.length === 0 ? (
					<MediaPlaceholder
						icon="format-gallery"
						labels={ {
							title: __( 'Logo Marquee' ),
							instructions: __(
								'Drag images, upload new ones or select files from your library.'
							),
						} }
						onSelect={ onSelectImages }
						accept="image/*"
						allowedTypes={ [ 'image' ] }
						multiple
					/>
				) : (
					<DndContext
						sensors={ sensors }
						collisionDetection={ closestCenter }
						onDragEnd={ handleDragEnd }
					>
						<SortableContext
							items={ images.map( ( img ) => img.id ) }
						>
							<div className="logo-list">
								{ images.map( ( img, index ) => (
									<SortableImage
										key={ img.id }
										id={ img.id }
										img={ img }
										index={ index }
										isSelected={ isSelected }
										removeImage={ removeImage }
										imageStyle={ imageStyle }
									/>
								) ) }
								{ isSelected && (
									<div
										className="logo-list-add-item"
										style={ {
											display: 'flex',
											justifyContent: 'center',
											alignItems: 'center',
											width: '200px',
											height: '160px',
											padding: '0 25px',
											overflow: 'hidden',
										} }
									>
										<MediaPlaceholder
											icon="plus"
											labels={ {
												title: __( 'Add to gallery' ),
												instructions: '',
											} }
											onSelect={ ( newImages ) => {
												setAttributes( {
													images: [
														...images,
														...newImages.map(
															( image ) => ( {
																id: image.id,
																url:
																	image.url ||
																	image.source_url,
																alt:
																	image.alt ||
																	image.alt_text ||
																	'',
															} )
														),
													],
												} );
											} }
											accept="image/*"
											allowedTypes={ [ 'image' ] }
											multiple
											value={ {} }
										/>
									</div>
								) }
							</div>
						</SortableContext>
					</DndContext>
				) }
			</div>
		</>
	);
}
