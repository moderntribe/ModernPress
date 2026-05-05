import { createBlock } from '@wordpress/blocks';
import {
	RichText,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { Button, Flex, FlexItem, Tooltip } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useCallback, useEffect, useMemo, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { dragHandle, trash } from '@wordpress/icons';

import {
	closestCenter,
	DndContext,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	SortableContext,
	useSortable,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import './editor.pcss';

/**
 * Sortable tab row for drag-and-drop reordering (vertical tab list).
 *
 * @param {Object}   root0
 * @param {Object}   root0.tab
 * @param {number}   root0.index
 * @param {string}   root0.currentActiveTabInstanceId
 * @param {Function} root0.onSelectTab
 * @param {Function} root0.onUpdateTitle
 * @param {Function} root0.onUpdateContent
 * @param {Function} root0.onDeleteTab
 */
function SortableVerticalTab( {
	tab,
	index,
	currentActiveTabInstanceId,
	onSelectTab,
	onUpdateTitle,
	onUpdateContent,
	onDeleteTab,
} ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( { id: tab.clientId } );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.5 : 1,
	};

	const isSelected = currentActiveTabInstanceId === tab.id;

	const handleTabKeyDown = ( e ) => {
		if ( e.target.closest( '[contenteditable="true"]' ) ) {
			return;
		}

		if ( e.key === 'Enter' || e.key === ' ' ) {
			e.preventDefault();
			onSelectTab( tab.id );
		}
	};

	return (
		<div
			ref={ setNodeRef }
			role="button"
			tabIndex={ 0 }
			className={
				'b-vertical-tabs__tab' +
				( isSelected ? ' editor-is-selected' : '' )
			}
			style={ style }
			onClick={ () => onSelectTab( tab.id ) }
			onKeyDown={ handleTabKeyDown }
			aria-pressed={ isSelected }
		>
			<Flex
				className="b-vertical-tabs__tab-toolbar"
				align="center"
				justify="flex-start"
			>
				<Button
					className="b-vertical-tabs__tab-drag-handle"
					variant="link"
					icon={ dragHandle }
					aria-label={ __( 'Drag to reorder tab', 'tribe' ) }
					onClick={ ( e ) => e.stopPropagation() }
					{ ...attributes }
					{ ...listeners }
				/>
				<FlexItem className="b-vertical-tabs__tab-toolbar-title">
					<RichText
						tagName="span"
						className="b-vertical-tabs__tab-title t-display-xx-small s-remove-margin--top"
						value={ tab.title }
						onChange={ ( value ) =>
							onUpdateTitle( value, tab.clientId )
						}
						onClick={ ( e ) => e.stopPropagation() }
						onFocus={ () => onSelectTab( tab.id ) }
						onKeyDown={ ( e ) => e.stopPropagation() }
						allowedFormats={ [] }
						placeholder={ __( 'Tab Heading', 'tribe' ) }
					/>
				</FlexItem>
				<Tooltip text={ __( 'Remove tab', 'tribe' ) } delay={ 300 }>
					<Button
						className="b-vertical-tabs__tab-delete"
						variant="link"
						icon={ trash }
						isDestructive
						aria-label={ __( 'Remove tab', 'tribe' ) }
						onClick={ ( e ) => {
							e.stopPropagation();
							onDeleteTab( index, tab.id, tab.clientId );
						} }
					/>
				</Tooltip>
			</Flex>
			{ isSelected ? (
				<div className="b-vertical-tabs__tab-hidden">
					<RichText
						tagName="span"
						className="b-vertical-tabs__tab-description t-body"
						value={ tab.content }
						onChange={ ( value ) =>
							onUpdateContent( value, tab.clientId )
						}
						onClick={ ( e ) => e.stopPropagation() }
						onKeyDown={ ( e ) => e.stopPropagation() }
						allowedFormats={ [ 'core/bold', 'core/italic' ] }
						placeholder={ __( 'Tab Description', 'tribe' ) }
					/>
				</div>
			) : null }
		</div>
	);
}

export default function Edit( { attributes, clientId, setAttributes } ) {
	const blockProps = useBlockProps( { className: 'b-vertical-tabs' } );
	const dispatch = useDispatch( 'core/block-editor' );
	const { removeBlocks, moveBlockToPosition } =
		useDispatch( 'core/block-editor' );

	const innerBlocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks( clientId ),
		[ clientId ]
	);
	const blockEditorSelect = useSelect(
		( select ) => select( 'core/block-editor' ),
		[]
	);

	const { currentActiveTabInstanceId } = attributes;

	const tabs = useMemo(
		() =>
			innerBlocks.map( ( block ) => ( {
				clientId: block.clientId,
				id: block.attributes.blockId,
				title: block.attributes.title,
				content: block.attributes.content,
			} ) ),
		[ innerBlocks ]
	);

	const sensors = useSensors( useSensor( PointerSensor ) );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'b-vertical-tabs__tab-content',
		},
		{
			allowedBlocks: [ 'tribe/vertical-tab' ],
			template: [ [ 'tribe/vertical-tab' ] ],
			renderAppender: false,
		}
	);

	/**
	 * First tab is active when the block loads in the editor (once per mount).
	 */
	const hasSetInitialTab = useRef( false );
	useEffect( () => {
		if ( innerBlocks.length > 0 && ! hasSetInitialTab.current ) {
			setAttributes( {
				currentActiveTabInstanceId: innerBlocks[ 0 ].attributes.blockId,
			} );
			hasSetInitialTab.current = true;
		}
	}, [ innerBlocks, setAttributes ] );

	const updateTabTitle = ( title, tabClientId ) => {
		dispatch.updateBlockAttributes( tabClientId, { title } );
	};

	const updateTabContent = ( content, tabClientId ) => {
		dispatch.updateBlockAttributes( tabClientId, { content } );
	};

	const addNewTab = ( positionToAdd = innerBlocks.length ) => {
		const newTab = createBlock( 'tribe/vertical-tab' );

		dispatch
			.insertBlock( newTab, positionToAdd, clientId, true )
			.then( () => {
				const newInstanceId =
					blockEditorSelect.getBlocks( clientId )[ positionToAdd ]
						.attributes.blockId;

				setAttributes( {
					currentActiveTabInstanceId: newInstanceId,
				} );
			} );
	};

	const deleteTab = ( index, tabInstanceId, tabClientId ) => {
		removeBlocks( tabClientId );

		const newInnerBlocks = blockEditorSelect.getBlocks( clientId );

		if ( newInnerBlocks.length === 0 ) {
			addNewTab( newInnerBlocks.length );
			return;
		}

		let newActiveTabInstanceId = currentActiveTabInstanceId;

		if ( newActiveTabInstanceId === tabInstanceId ) {
			newActiveTabInstanceId =
				index === 0
					? newInnerBlocks[ index ].attributes.blockId
					: newInnerBlocks[ index - 1 ].attributes.blockId;
		}

		setAttributes( {
			currentActiveTabInstanceId: newActiveTabInstanceId,
		} );
	};

	const moveTab = useCallback(
		( fromIndex, toIndex ) => {
			if (
				fromIndex === toIndex ||
				toIndex < 0 ||
				toIndex >= innerBlocks.length
			) {
				return;
			}

			const blockClientId = innerBlocks[ fromIndex ].clientId;
			moveBlockToPosition( blockClientId, clientId, clientId, toIndex );
		},
		[ innerBlocks, clientId, moveBlockToPosition ]
	);

	const handleDragEnd = useCallback(
		( event ) => {
			const { active, over } = event;

			if ( ! over || active.id === over.id ) {
				return;
			}

			const oldIndex = tabs.findIndex(
				( t ) => t.clientId === active.id
			);
			const newIndex = tabs.findIndex( ( t ) => t.clientId === over.id );

			if ( oldIndex === -1 || newIndex === -1 ) {
				return;
			}

			moveTab( oldIndex, newIndex );
		},
		[ tabs, moveTab ]
	);

	const tabList = (
		<div className="b-vertical-tabs__tab-container">
			{ tabs.map( ( tab, index ) => (
				<SortableVerticalTab
					key={ tab.clientId }
					tab={ tab }
					index={ index }
					currentActiveTabInstanceId={ currentActiveTabInstanceId }
					onSelectTab={ ( id ) =>
						setAttributes( { currentActiveTabInstanceId: id } )
					}
					onUpdateTitle={ updateTabTitle }
					onUpdateContent={ updateTabContent }
					onDeleteTab={ deleteTab }
				/>
			) ) }
			<Button
				__next40pxDefaultSize
				variant="primary"
				onClick={ () => addNewTab() }
				className="b-vertical-tabs__editor-add-tab"
			>
				{ __( 'Add New Tab', 'tribe' ) }
			</Button>
		</div>
	);

	return (
		<div { ...blockProps }>
			{ tabs.length > 0 ? (
				<DndContext
					sensors={ sensors }
					collisionDetection={ closestCenter }
					onDragEnd={ handleDragEnd }
				>
					<SortableContext
						items={ tabs.map( ( t ) => t.clientId ) }
						strategy={ verticalListSortingStrategy }
					>
						{ tabList }
					</SortableContext>
				</DndContext>
			) : (
				tabList
			) }
			<div { ...innerBlocksProps } />
		</div>
	);
}
