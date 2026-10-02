import RowItemContent from './RowItemContent';

/**
 * Drag overlay shown when reordering a category and its nested feature rows.
 *
 * @param {Object} root0
 * @param {Array}  root0.rows          Full row list.
 * @param {Object} root0.sectionBounds Inclusive start/end indices for the dragged section.
 * @param {Array}  root0.columns       Table column definitions.
 */
export default function SectionDragPreview( { rows, sectionBounds, columns } ) {
	const sectionRows = rows.slice(
		sectionBounds.start,
		sectionBounds.end + 1
	);

	return (
		<div className="b-comparison-table-editor__drag-overlay">
			{ sectionRows.map( ( row, index ) => (
				<RowItemContent
					key={ row.clientId }
					row={ row }
					rows={ rows }
					rowIndex={ sectionBounds.start + index }
					columns={ columns }
					isPreview
				/>
			) ) }
		</div>
	);
}
