import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

import { isLastInSection, isNestedFeature } from '../js/utils';
import RowItemContent from './RowItemContent';

/**
 * Sortable wrapper that applies section styling and selection state to a row.
 *
 * @param {Object}   root0
 * @param {Object}   root0.row                   Row data including clientId.
 * @param {Array}    root0.rows                  Full row list.
 * @param {number}   root0.rowIndex              Position of this row in the list.
 * @param {Array}    root0.columns               Table column definitions.
 * @param {boolean}  root0.isSelected            Whether this row is the active block.
 * @param {boolean}  root0.isHiddenWhileDragging Hides the source item during drag.
 * @param {Function} root0.onSelect              Selects the row block in the editor.
 * @param {Function} root0.onDeleteRow           Removes the row by client id.
 */
export default function SortableRowItem( {
	row,
	rows,
	rowIndex,
	columns,
	isSelected,
	isHiddenWhileDragging,
	onSelect,
	onDeleteRow,
} ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( { id: row.clientId } );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isHiddenWhileDragging ? 0 : 1,
	};

	const isCategory = row.rowType === 'category';
	const isNested = isNestedFeature( rows, rowIndex );
	const isSectionTail = isLastInSection( rows, rowIndex );
	const isOrphanFeature = row.rowType === 'feature' && ! isNested;

	return (
		<div
			ref={ setNodeRef }
			style={ style }
			role="button"
			tabIndex={ 0 }
			className={
				'b-comparison-table-editor__row-wrapper' +
				( isCategory ? ' is-section-head' : '' ) +
				( isNested ? ' is-section-child' : '' ) +
				( isOrphanFeature ? ' is-orphan-feature' : '' ) +
				( isSectionTail ? ' is-section-tail' : '' ) +
				( isSelected ? ' is-selected' : '' ) +
				( isDragging ? ' is-dragging' : '' )
			}
			onClick={ () => onSelect( row.clientId ) }
			onKeyDown={ ( event ) => {
				if ( event.key === 'Enter' || event.key === ' ' ) {
					event.preventDefault();
					onSelect( row.clientId );
				}
			} }
		>
			<RowItemContent
				row={ row }
				rows={ rows }
				rowIndex={ rowIndex }
				columns={ columns }
				onDeleteRow={ onDeleteRow }
				dragHandleProps={ { attributes, listeners } }
			/>
		</div>
	);
}
