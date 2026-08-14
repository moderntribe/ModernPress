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
import { useSelect } from '@wordpress/data';
import { useState, useCallback, useMemo, useEffect } from '@wordpress/element';
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

const SortableFacet = ( { facet, onRemove, onDisplayLabelChange } ) => {
	const { attributes, listeners, setNodeRef, transform, transition } =
		useSortable( { id: facet.slug } );

	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ draftLabel, setDraftLabel ] = useState( '' );

	const label = facet.label ?? facet.slug;
	const displayLabel = facet.displayLabel;
	const hasOverride =
		typeof displayLabel === 'string' &&
		displayLabel.trim() !== '' &&
		displayLabel !== label;

	const openModal = useCallback( () => {
		setDraftLabel( hasOverride ? displayLabel : '' );
		setIsModalOpen( true );
	}, [ displayLabel, hasOverride ] );

	const closeModal = useCallback( () => setIsModalOpen( false ), [] );

	const applyLabel = useCallback( () => {
		const next = draftLabel.trim();
		onDisplayLabelChange(
			facet.slug,
			next !== '' && next !== label ? next : undefined
		);
		closeModal();
	}, [ facet.slug, draftLabel, label, onDisplayLabelChange, closeModal ] );

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
							{ label }
							{ hasOverride && (
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
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'Display label', 'tribe' ) }
						help={ __(
							'Label shown on the site. Leave blank to use the facet label from settings.',
							'tribe'
						) }
						value={ draftLabel }
						onChange={ setDraftLabel }
						placeholder={ label }
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

export default function Edit( {
	attributes,
	setAttributes,
	isSelected,
	context,
} ) {
	const blockProps = useBlockProps();
	const { facets } = attributes;

	const filterBarPosition =
		context[ 'tribe/faceted-directory/filterBarPosition' ];
	const contextPostTypes = context[ 'tribe/faceted-directory/postTypes' ];

	const tribeFacets = useSelect(
		( select ) =>
			select( 'core/editor' ).getEditorSettings()?.tribeFacets ?? [],
		[]
	);

	const availableFacets = useMemo( () => {
		const postTypes = contextPostTypes ?? [ 'post' ];

		return tribeFacets.filter( ( facet ) => {
			if ( facet.source === 'system' ) {
				return true;
			}
			if ( ! Array.isArray( facet.post_types ) ) {
				return false;
			}
			return facet.post_types.some( ( type ) =>
				postTypes.includes( type )
			);
		} );
	}, [ tribeFacets, contextPostTypes ] );

	// Block context never reaches a ServerSideRender preview, so send it along.
	const previewContext = useMemo(
		() => ( {
			tribePreviewContext: {
				filterBarPosition: filterBarPosition ?? 'top',
				postTypes: contextPostTypes ?? [ 'post' ],
			},
		} ),
		[ filterBarPosition, contextPostTypes ]
	);

	const catalogBySlug = useMemo( () => {
		const map = new Map();
		tribeFacets.forEach( ( facet ) => {
			map.set( facet.slug, facet );
		} );
		return map;
	}, [ tribeFacets ] );

	// Merge saved order/overrides with live settings so label/type never go stale.
	const activeFacets = useMemo(
		() =>
			facets.map( ( facet ) => {
				const live = catalogBySlug.get( facet.slug );
				const label = live?.label ?? facet.label ?? facet.slug;
				const displayLabel =
					typeof facet.displayLabel === 'string' &&
					facet.displayLabel.trim() !== '' &&
					facet.displayLabel !== label
						? facet.displayLabel
						: undefined;

				return {
					slug: facet.slug,
					label,
					displayLabel,
				};
			} ),
		[ facets, catalogBySlug ]
	);

	const [ selectedFacet, setSelectedFacet ] = useState(
		availableFacets[ 0 ]?.slug ?? ''
	);

	useEffect( () => {
		if (
			! availableFacets.some( ( facet ) => facet.slug === selectedFacet )
		) {
			setSelectedFacet( availableFacets[ 0 ]?.slug ?? '' );
		}
	}, [ availableFacets, selectedFacet ] );

	const handleAddFacet = () => {
		const mappedFacet = availableFacets.find(
			( facet ) => facet.slug === selectedFacet
		);

		if (
			! mappedFacet ||
			facets.some( ( f ) => f.slug === mappedFacet.slug )
		) {
			return;
		}

		setAttributes( {
			facets: [
				...facets,
				{
					slug: mappedFacet.slug,
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
				facets: facets.map( ( f ) => {
					if ( f.slug !== slug ) {
						return f;
					}

					const next = { slug: f.slug };
					if ( value && value.trim() !== '' ) {
						next.displayLabel = value.trim();
					}

					return next;
				} ),
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
				urlQueryArgs={ previewContext }
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
							<p>
								{ filterBarPosition === 'sidebar'
									? __(
											'This filter bar uses the sidebar layout. On smaller screens it opens as a flyout.',
											'tribe'
									  )
									: __(
											'This filter bar appears above the directory results.',
											'tribe'
									  ) }
							</p>
							<p style={ { marginBottom: '0' } }>
								{ __(
									'Change position and post types on the parent Faceted Directory block. Control types are set under Tribe → Facets.',
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
									items={ activeFacets.map(
										( f ) => f.slug
									) }
								>
									{ activeFacets.map( ( facet ) => (
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
								options={ availableFacets.map( ( facet ) => ( {
									label: facet.label,
									value: facet.slug,
									disabled: facets.some(
										( f ) => f.slug === facet.slug
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
								disabled={ ! selectedFacet }
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
