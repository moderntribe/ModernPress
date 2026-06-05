import { useState, useRef } from '@wordpress/element';
import {
	closestCenter,
	DndContext,
	DragOverlay,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	SortableContext,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';

import { getSectionBounds } from '../js/utils';
import RowActions from './RowActions';
import RowItemContent from './RowItemContent';
import SectionDragPreview from './SectionDragPreview';
import SortableRowItem from './SortableRowItem';

/**
 * Sortable list of comparison rows with section drag support and add-row actions.
 *
 * @param {Object}      props
 * @param {Array}       props.rows                  Editor row objects derived from inner blocks.
 * @param {Array}       props.columns               Table column definitions.
 * @param {string}      props.selectedRowClientId   Client id of the selected row block.
 * @param {Function}    props.onSelectRow           Selects a row block in the editor.
 * @param {Function}    props.onDeleteRow           Removes a row by client id.
 * @param {Function}    props.onAddRow              Inserts a new feature row.
 * @param {Function}    props.onAddCategory         Inserts a new category row.
 * @param {Object|null} props.draggingSectionBounds Active category section bounds while dragging.
 * @param {Function}    props.onRowDragStart        Notifies parent when row drag begins.
 * @param {Function}    props.onRowDragEnd          Notifies parent when row drag ends.
 * @param {Function}    props.onRowDragCancel       Notifies parent when row drag is cancelled.
 */
export default function RowListEditor( {
	rows,
	columns,
	selectedRowClientId,
	draggingSectionBounds,
	onSelectRow,
	onDeleteRow,
	onAddRow,
	onAddCategory,
	onRowDragStart,
	onRowDragEnd,
	onRowDragCancel,
} ) {
	const sensors = useSensors( useSensor( PointerSensor ) );
	const rowsRef = useRef( null );
	const [ activeDragId, setActiveDragId ] = useState( null );
	const [ overlayWidth, setOverlayWidth ] = useState( null );

	const activeIndex = rows.findIndex(
		( row ) => row.clientId === activeDragId
	);
	const activeRow = activeIndex >= 0 ? rows[ activeIndex ] : null;
	const overlaySectionBounds =
		activeRow?.rowType === 'category'
			? getSectionBounds( rows, activeIndex )
			: null;
	const isSectionDrag = !! overlaySectionBounds;

	const handleDragStart = ( event ) => {
		if ( rowsRef.current ) {
			setOverlayWidth( rowsRef.current.offsetWidth );
		}

		setActiveDragId( event.active.id );
		onRowDragStart( event );
	};

	const handleDragEnd = ( event ) => {
		setActiveDragId( null );
		setOverlayWidth( null );
		onRowDragEnd( event );
	};

	const handleDragCancel = () => {
		setActiveDragId( null );
		setOverlayWidth( null );
		onRowDragCancel();
	};

	return (
		<div className="b-comparison-table-editor__rows" ref={ rowsRef }>
			<DndContext
				sensors={ sensors }
				collisionDetection={ closestCenter }
				onDragStart={ handleDragStart }
				onDragEnd={ handleDragEnd }
				onDragCancel={ handleDragCancel }
			>
				<SortableContext
					items={ rows.map( ( row ) => row.clientId ) }
					strategy={ verticalListSortingStrategy }
				>
					{ rows.map( ( row, rowIndex ) => {
						const isInDraggingSection =
							isSectionDrag &&
							draggingSectionBounds &&
							rowIndex >= draggingSectionBounds.start &&
							rowIndex <= draggingSectionBounds.end;
						const isHiddenWhileDragging = isSectionDrag
							? isInDraggingSection
							: row.clientId === activeDragId;

						return (
							<SortableRowItem
								key={ row.clientId }
								row={ row }
								rows={ rows }
								rowIndex={ rowIndex }
								columns={ columns }
								isSelected={
									selectedRowClientId === row.clientId
								}
								isHiddenWhileDragging={ isHiddenWhileDragging }
								onSelect={ onSelectRow }
								onDeleteRow={ onDeleteRow }
							/>
						);
					} ) }
				</SortableContext>

				<DragOverlay dropAnimation={ null }>
					{ activeRow && overlayWidth ? (
						<div style={ { width: overlayWidth } }>
							{ isSectionDrag && overlaySectionBounds ? (
								<SectionDragPreview
									rows={ rows }
									sectionBounds={ overlaySectionBounds }
									columns={ columns }
								/>
							) : (
								<div className="b-comparison-table-editor__drag-overlay">
									<RowItemContent
										row={ activeRow }
										rows={ rows }
										rowIndex={ activeIndex }
										columns={ columns }
										isPreview
									/>
								</div>
							) }
						</div>
					) : null }
				</DragOverlay>
			</DndContext>

			<RowActions onAddCategory={ onAddCategory } onAddRow={ onAddRow } />
		</div>
	);
}
