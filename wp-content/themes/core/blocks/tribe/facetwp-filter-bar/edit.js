import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	BaseControl,
	Button,
	Flex,
	FlexItem,
	TextControl,
	Modal,
} from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { useState, useCallback } from '@wordpress/element';
import { Icon, close, dragHandle, pencil } from '@wordpress/icons';
import { ServerSideRender } from '@wordpress/server-side-render';
import {
	closestCenter,
	DndContext,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import { arrayMove, SortableContext, useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import metadata from './block.json';

import './editor.pcss';

const SortableFacet = ( { facet, onRemove, onDisplayLabelChange } ) => {
	const { attributes, listeners, setNodeRef, transform, transition } =
		useSortable( { id: facet.slug } );

	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ draftLabel, setDraftLabel ] = useState( '' );

	const displayLabel = facet.displayLabel ?? facet.label;

	const openModal = useCallback( () => {
		setDraftLabel( displayLabel );
		setIsModalOpen( true );
	}, [ displayLabel ] );

	const closeModal = useCallback( () => setIsModalOpen( false ), [] );

	const applyLabel = useCallback( () => {
		onDisplayLabelChange(
			facet.slug,
			draftLabel.trim() !== '' ? draftLabel.trim() : undefined
		);
		closeModal();
	}, [ facet.slug, draftLabel, onDisplayLabelChange, closeModal ] );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		cursor: 'grab',
	};

	return (
		<div ref={ setNodeRef } style={ style }>
			<Flex
				as="div"
				align="center"
				gap={ 2 }
				style={ { marginBottom: '8px' } }
			>
				<FlexItem
					{ ...attributes }
					{ ...listeners }
					aria-label={ __( 'Drag to reorder', 'tribe' ) }
					style={ { flex: 1, minWidth: 0 } }
				>
					<Flex align="center">
						<FlexItem>
							<Icon icon={ dragHandle } size={ 32 } />
						</FlexItem>
						<FlexItem style={ { flex: 1 } }>
							{ facet.label }
							{ displayLabel !== facet.label && (
								<span
									style={ {
										display: 'block',
										fontSize: '12px',
										color: 'var(--wp-admin-theme-color)',
									} }
								>
									→ { displayLabel }
								</span>
							) }
						</FlexItem>
					</Flex>
				</FlexItem>
				<FlexItem>
					<Button
						variant="text"
						onClick={ openModal }
						label={ __( 'Edit display label', 'tribe' ) }
						size="compact"
						style={ { padding: '0' } }
					>
						<Icon icon={ pencil } />
					</Button>
				</FlexItem>
				<FlexItem>
					<Button
						variant="text"
						onClick={ () => onRemove( facet.slug ) }
						label={ __( 'Remove Facet', 'tribe' ) }
						size="compact"
						isDestructive
						style={ { padding: '0' } }
					>
						<Icon icon={ close } />
					</Button>
				</FlexItem>
			</Flex>
			{ isModalOpen && (
				<Modal
					title={ __( 'Edit display label', 'tribe' ) }
					onRequestClose={ closeModal }
					size="small"
				>
					<TextControl
						__nextHasNoMarginBottom
						label={ __( 'Display label', 'tribe' ) }
						help={ __(
							'Label shown on the site. Leave blank to use the FacetWP label.',
							'tribe'
						) }
						value={ draftLabel }
						onChange={ setDraftLabel }
						placeholder={ facet.label }
					/>
					<Flex
						justify="flex-end"
						gap={ 2 }
						style={ { marginTop: '16px' } }
					>
						<Button variant="tertiary" onClick={ closeModal }>
							{ __( 'Cancel', 'tribe' ) }
						</Button>
						<Button variant="primary" onClick={ applyLabel }>
							{ __( 'Apply', 'tribe' ) }
						</Button>
					</Flex>
				</Modal>
			) }
		</div>
	);
};

function Edit( {
	attributes,
	setAttributes,
	isSelected,
	context,
	facetwpFacets,
} ) {
	const blockProps = useBlockProps();
	const [ selectedFacet, setSelectedFacet ] = useState(
		facetwpFacets[ 0 ]?.name ?? ''
	);

	const { facets } = attributes;

	const filterBarPosition =
		context[ 'tribe/facetwp-archive/filterBarPosition' ];

	const handleAddFacet = () => {
		const mappedFacet = facetwpFacets.find(
			( facet ) => facet.name === selectedFacet
		);

		if (
			! mappedFacet ||
			facets.some( ( f ) => f.slug === mappedFacet.name )
		) {
			return;
		}

		setAttributes( {
			facets: [
				...facets,
				{
					slug: mappedFacet.name,
					label: mappedFacet.label,
					type: mappedFacet.type ?? '',
					displayLabel: mappedFacet.label,
				},
			],
		} );
	};

	const handleRemoveFacet = ( slug ) => {
		setAttributes( {
			facets: facets.filter( ( facet ) => facet.slug !== slug ),
		} );
	};

	const handleDragEnd = useCallback(
		( event ) => {
			const { active, over } = event;
			if ( over && active.id !== over.id ) {
				const oldIndex = facets.findIndex(
					( f ) => f.slug === active.id
				);
				const newIndex = facets.findIndex(
					( f ) => f.slug === over.id
				);
				if ( oldIndex !== -1 && newIndex !== -1 ) {
					setAttributes( {
						facets: arrayMove( facets, oldIndex, newIndex ),
					} );
				}
			}
		},
		[ facets, setAttributes ]
	);

	const handleDisplayLabelChange = useCallback(
		( slug, value ) => {
			setAttributes( {
				facets: facets.map( ( f ) =>
					f.slug === slug
						? {
								...f,
								displayLabel:
									value && value.trim() !== ''
										? value.trim()
										: undefined,
						  }
						: f
				),
			} );
		},
		[ facets, setAttributes ]
	);

	const sensors = useSensors( useSensor( PointerSensor ) );

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block={ metadata.name }
				attributes={ attributes }
			/>
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<div
							style={ {
								marginBottom: '16px',
								paddingLeft: '16px',
								borderLeft:
									'4px solid var(--wp-admin-theme-color)',
							} }
						>
							{ filterBarPosition === 'top' && (
								<p>
									{ __(
										'The filter bar will be displayed at the top of the page. Typically facets of type "dropdown" or "fselect" are best suited for this position.',
										'tribe'
									) }
								</p>
							) }
							{ filterBarPosition === 'sidebar' && (
								<p>
									{ __(
										'The filter bar will be displayed in the sidebar. Typically facets of type "checkbox" or "radio" are best suited for this position.',
										'tribe'
									) }
								</p>
							) }
							<p style={ { marginBottom: '0' } }>
								{ __(
									'To change the position of the filter bar, use the "Filter Bar Position" setting in the parent FacetWP Archive block.',
									'tribe'
								) }
							</p>
						</div>
						<BaseControl
							__nextHasNoMarginBottom
							id="active-facets"
							label={ __( 'Active Facets', 'tribe' ) }
						>
							<DndContext
								sensors={ sensors }
								collisionDetection={ closestCenter }
								onDragEnd={ handleDragEnd }
							>
								<SortableContext
									items={ facets.map( ( f ) => f.slug ) }
								>
									{ facets.map( ( facet ) => (
										<SortableFacet
											key={ facet.slug }
											facet={ facet }
											onRemove={ handleRemoveFacet }
											onDisplayLabelChange={
												handleDisplayLabelChange
											}
										/>
									) ) }
								</SortableContext>
							</DndContext>
						</BaseControl>
						<BaseControl
							__nextHasNoMarginBottom
							id="select-facet"
							label={ __( 'Select Facet', 'tribe' ) }
						>
							<SelectControl
								__nextHasNoMarginBottom
								__next40pxDefaultSize
								value={ selectedFacet }
								options={ facetwpFacets.map( ( facet ) => ( {
									label: `${ facet.label } (${ facet.type })`,
									value: facet.name,
									disabled: facets.some(
										( f ) => f.slug === facet.name
									),
								} ) ) }
								onChange={ ( value ) =>
									setSelectedFacet( value )
								}
							/>
							<Button
								__next40pxDefaultSize
								variant="primary"
								onClick={ handleAddFacet }
							>
								{ __( 'Add Facet', 'tribe' ) }
							</Button>
						</BaseControl>
					</PanelBody>
				</InspectorControls>
			) }
		</div>
	);
}

export default withSelect( ( select, ownProps ) => {
	return {
		facetwpFacets:
			select( 'core/editor' ).getEditorSettings()?.facetwpFacets ?? [],
		...ownProps,
	};
} )( Edit );
