import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import {
	closestCenter,
	DndContext,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	SortableContext,
	horizontalListSortingStrategy,
} from '@dnd-kit/sortable';

import SortableColumnChip from './SortableColumnChip';

/**
 * Horizontal bar of sortable column chips with an add-column action.
 *
 * @param {Object}   props
 * @param {Array}    props.columns          Table column definitions.
 * @param {string}   props.selectedColumnId Id of the column open in the sidebar.
 * @param {Function} props.onSelectColumn   Selects a column for editing.
 * @param {Function} props.onRemoveColumn   Removes a column by index.
 * @param {Function} props.onAddColumn      Appends a new column.
 * @param {Function} props.onColumnDragEnd  Handles dnd-kit column reorder events.
 */
export default function ColumnBar( {
	columns,
	selectedColumnId,
	onSelectColumn,
	onRemoveColumn,
	onAddColumn,
	onColumnDragEnd,
} ) {
	const sensors = useSensors( useSensor( PointerSensor ) );

	return (
		<div className="b-comparison-table-editor__columns-bar">
			<DndContext
				sensors={ sensors }
				collisionDetection={ closestCenter }
				onDragEnd={ onColumnDragEnd }
			>
				<SortableContext
					items={ columns.map( ( column ) => column.id ) }
					strategy={ horizontalListSortingStrategy }
				>
					<div className="b-comparison-table-editor__column-list">
						{ columns.map( ( column, index ) => (
							<SortableColumnChip
								key={ column.id }
								column={ column }
								index={ index }
								isSelected={ selectedColumnId === column.id }
								onSelect={ onSelectColumn }
								onRemoveColumn={ onRemoveColumn }
							/>
						) ) }
						<div className="b-comparison-table-editor__add-column">
							<Button variant="secondary" onClick={ onAddColumn }>
								{ __( 'Add column', 'tribe' ) }
							</Button>
						</div>
					</div>
				</SortableContext>
			</DndContext>
		</div>
	);
}
