import { createBlock } from '@wordpress/blocks';
import {
	useBlockProps,
	RichText,
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
	horizontalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import './editor.pcss';

/**
 * Sortable tab wrapper for drag-and-drop reordering.
 * @param {Object}   root0
 * @param {Object}   root0.tab
 * @param {number}   root0.index
 * @param {string}   root0.currentActiveTabInstanceId
 * @param {Function} root0.onSelectTab
 * @param {Function} root0.onUpdateLabel
 * @param {Function} root0.onDeleteTab
 */
function SortableTab( {
	tab,
	index,
	currentActiveTabInstanceId,
	onSelectTab,
	onUpdateLabel,
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
		display: 'flex',
		alignItems: 'center',
		justifyContent: 'flex-start',
		gap: 'var(--spacer-10)',
	};

	const handleTabKeyDown = ( e ) => {
		// Don't capture keys when user is editing the tab label (so Space/Enter can be typed).
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
				'wp-block-tribe-horizontal-tabs__tab' +
				( currentActiveTabInstanceId === tab.id ? ' active-tab' : '' )
			}
			style={ style }
			onClick={ () => onSelectTab( tab.id ) }
			onKeyDown={ handleTabKeyDown }
			aria-pressed={ currentActiveTabInstanceId === tab.id }
		>
			<Button
				className="wp-block-tribe-horizontal-tabs__tab-drag-handle"
				variant="link"
				icon={ dragHandle }
				aria-label={ __( 'Drag to reorder tab', 'tribe' ) }
				onClick={ ( e ) => e.stopPropagation() }
				{ ...attributes }
				{ ...listeners }
			/>
			<RichText
				tagName="span"
				className="wp-block-tribe-horizontal-tabs__tab-label"
				value={ tab.label }
				onChange={ ( value ) => onUpdateLabel( value, tab.clientId ) }
				onClick={ ( e ) => e.stopPropagation() }
				onFocus={ () => onSelectTab( tab.id ) }
				onKeyDown={ ( e ) => e.stopPropagation() }
				allowedFormats={ [] }
				placeholder={ __( 'Tab Label', 'tribe' ) }
			/>
			<Tooltip text={ __( 'Remove tab', 'tribe' ) } delay={ 300 }>
				<Button
					className="wp-block-tribe-horizontal-tabs__tab-delete"
					variant="link"
					icon={ trash }
					aria-label={ __( 'Remove tab', 'tribe' ) }
					onClick={ ( e ) => {
						e.stopPropagation();
						onDeleteTab( index, tab.id, tab.clientId );
					} }
				/>
			</Tooltip>
		</div>
	);
}

export default function Edit( { clientId, attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const dispatch = useDispatch( 'core/block-editor' );
	const { removeBlocks, moveBlockToPosition } =
		useDispatch( 'core/block-editor' );
	// Subscribe to inner blocks so the component re-renders when order changes (e.g. after moveBlockToPosition).
	const innerBlocks = useSelect(
		( select ) => select( 'core/block-editor' ).getBlocks( clientId ),
		[ clientId ]
	);
	const blockEditorSelect = useSelect(
		( select ) => select( 'core/block-editor' ),
		[]
	);
	const { currentActiveTabInstanceId } = attributes;

	// Derive tab list directly from innerBlocks so the tab bar reflects order immediately (no useEffect delay).
	const tabs = useMemo(
		() =>
			innerBlocks.map( ( block ) => ( {
				clientId: block.clientId,
				id: block.attributes.blockId,
				buttonId: 'button-' + block.attributes.blockId,
				label: block.attributes.tabLabel,
				isActive:
					currentActiveTabInstanceId === block.attributes.blockId,
			} ) ),
		[ innerBlocks, currentActiveTabInstanceId ]
	);

	const sensors = useSensors( useSensor( PointerSensor ) );

	/**
	 * setup inner block props and add classname to wrapper
	 */
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'wp-block-tribe-horizontal-tabs__tab-content',
		},
		{
			allowedBlocks: [ 'tribe/horizontal-tab' ],
			template: [ [ 'tribe/horizontal-tab' ] ],
			renderAppender: false,
		}
	);

	/**
	 * Default to the first tab when the block loads in the editor (once per mount).
	 * Does not persist which tab was last active; always open with first tab.
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

	/**
	 * @function updateTabLabel
	 *
	 * @description Dispatch action to matching tab to update its label
	 *
	 * @param {string} tabLabel
	 * @param {string} tabClientId
	 */
	const updateTabLabel = ( tabLabel, tabClientId ) => {
		dispatch.updateBlockAttributes( tabClientId, { tabLabel } );
	};

	/**
	 * @function addNewTab
	 *
	 * @description adds a new tab and an InnerBlock to match
	 *
	 * @param {number} positionToAdd
	 */
	const addNewTab = ( positionToAdd = innerBlocks.length ) => {
		// create block
		const newTab = createBlock( 'tribe/horizontal-tab' );

		// add new tab
		dispatch
			.insertBlock( newTab, positionToAdd, clientId, true )
			.then( () => {
				// dispatch will return us a promise which we can use to set our new active tab instanceId
				const newInstanceId =
					blockEditorSelect.getBlocks( clientId )[ positionToAdd ]
						.attributes.blockId;

				// set new tab as active
				setAttributes( {
					currentActiveTabInstanceId: newInstanceId,
				} );
			} );
	};

	/**
	 * @function deleteTab
	 *
	 * @description handles removing tab & InnerBlock based on index
	 *
	 * @param {number} index
	 * @param {string} tabInstanceId
	 * @param {string} tabClientId
	 */
	const deleteTab = ( index, tabInstanceId, tabClientId ) => {
		// remove block from InnerBlocks
		removeBlocks( tabClientId );

		// Fetch new inner blocks
		const newInnerBlocks = blockEditorSelect.getBlocks( clientId );

		// Add a new tab if we've deleted the last one
		if ( newInnerBlocks.length === 0 ) {
			addNewTab( newInnerBlocks.length );

			return;
		}

		// decide which block should be the new selected block
		let newActiveTabInstanceId = currentActiveTabInstanceId;

		if ( newActiveTabInstanceId === tabInstanceId ) {
			// if we want the first block, show new "first block", any other tab, use the "next" tab
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

			// Use moveBlockToPosition so the block tree order is updated for save.
			// Signature: (clientId, fromRootClientId, toRootClientId, index).
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
		<Flex
			className="wp-block-tribe-horizontal-tabs__tabs"
			align="center"
			justify="flex-start"
		>
			{ tabs.map( ( tab, index ) => (
				<SortableTab
					key={ tab.clientId }
					tab={ tab }
					index={ index }
					currentActiveTabInstanceId={ currentActiveTabInstanceId }
					onSelectTab={ ( id ) =>
						setAttributes( { currentActiveTabInstanceId: id } )
					}
					onUpdateLabel={ updateTabLabel }
					onDeleteTab={ deleteTab }
				/>
			) ) }
			<FlexItem
				className="wp-block-tribe-horizontal-tabs__add"
				justify="flex-start"
			>
				<Button variant="primary" onClick={ () => addNewTab() }>
					{ __( 'Add New Tab', 'tribe' ) }
				</Button>
			</FlexItem>
		</Flex>
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
						strategy={ horizontalListSortingStrategy }
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
