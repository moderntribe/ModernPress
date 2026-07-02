import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { dragHandle, tableColumnDelete } from '@wordpress/icons';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

/**
 * Sortable column header chip with drag, select, and delete controls.
 *
 * @param {Object}   root0
 * @param {Object}   root0.column         Column definition object.
 * @param {number}   root0.index          Column position in the table.
 * @param {boolean}  root0.isSelected     Whether this column is active in the sidebar.
 * @param {Function} root0.onSelect       Selects the column for editing.
 * @param {Function} root0.onRemoveColumn Removes the column by index.
 */
export default function SortableColumnChip( {
	column,
	index,
	isSelected,
	onSelect,
	onRemoveColumn,
} ) {
	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
		isDragging,
	} = useSortable( { id: column.id } );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.5 : 1,
	};

	return (
		<div
			ref={ setNodeRef }
			style={ style }
			role="button"
			tabIndex={ 0 }
			className={
				'b-comparison-table-editor__column-chip' +
				( isSelected ? ' is-active' : '' )
			}
			onClick={ () => onSelect( column.id ) }
			onKeyDown={ ( event ) => {
				if ( event.key === 'Enter' || event.key === ' ' ) {
					event.preventDefault();
					onSelect( column.id );
				}
			} }
		>
			<Button
				className="b-comparison-table-editor__column-drag"
				variant="link"
				icon={ dragHandle }
				aria-label={ __( 'Drag to reorder column', 'tribe' ) }
				onClick={ ( event ) => event.stopPropagation() }
				{ ...attributes }
				{ ...listeners }
			/>
			<span className="b-comparison-table-editor__column-chip-label">
				{ column.label || __( 'Untitled plan', 'tribe' ) }
			</span>
			{ column.badge && (
				<span className="b-comparison-table-editor__column-chip-badge t-tag">
					{ column.badge }
				</span>
			) }
			<Button
				className="b-comparison-table-editor__column-delete"
				variant="link"
				icon={ tableColumnDelete }
				isDestructive
				aria-label={ __( 'Remove column', 'tribe' ) }
				onClick={ ( event ) => {
					event.stopPropagation();
					onRemoveColumn( index );
				} }
			/>
		</div>
	);
}
