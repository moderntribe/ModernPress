import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { dragHandle, tableRowDelete } from '@wordpress/icons';

import { isNestedFeature } from '../js/utils';
import CellSummary from './CellSummary';

/**
 * Renders a single row's editor chrome: drag handle, label, delete control,
 * and optional cell summary for feature rows.
 *
 * @param {Object}      root0
 * @param {Object}      root0.row               Row data including type, label, and cells.
 * @param {Array}       root0.rows              Full row list for nesting context.
 * @param {number}      root0.rowIndex          Position of this row in the list.
 * @param {Array}       root0.columns           Table column definitions.
 * @param {boolean}     [root0.isPreview]       When true, renders a non-interactive drag preview.
 * @param {Function}    root0.onDeleteRow       Removes the row by client id.
 * @param {Object|null} [root0.dragHandleProps] dnd-kit attributes and listeners for the drag handle.
 */
export default function RowItemContent( {
	row,
	rows,
	rowIndex,
	columns,
	isPreview = false,
	onDeleteRow,
	dragHandleProps = null,
} ) {
	const isCategory = row.rowType === 'category';
	const isNested = isNestedFeature( rows, rowIndex );

	return (
		<div
			className={
				'b-comparison-table-editor__row' +
				( isCategory
					? ' b-comparison-table-editor__row--category'
					: ' b-comparison-table-editor__row--feature' ) +
				( isNested ? ' b-comparison-table-editor__row--nested' : '' ) +
				( isPreview ? ' b-comparison-table-editor__row--preview' : '' )
			}
		>
			<div
				className={
					'b-comparison-table-editor__row-toolbar' +
					( isCategory
						? ' b-comparison-table-editor__row-toolbar--category'
						: ' b-comparison-table-editor__row-toolbar--feature' )
				}
			>
				{ dragHandleProps ? (
					<Button
						className="b-comparison-table-editor__row-drag"
						variant="link"
						icon={ dragHandle }
						aria-label={ __( 'Drag to reorder row', 'tribe' ) }
						onClick={ ( event ) => event.stopPropagation() }
						{ ...dragHandleProps.attributes }
						{ ...dragHandleProps.listeners }
					/>
				) : (
					<span
						className="b-comparison-table-editor__row-drag b-comparison-table-editor__row-drag--preview"
						aria-hidden="true"
					>
						<Button
							variant="link"
							icon={ dragHandle }
							tabIndex={ -1 }
							aria-hidden="true"
						/>
					</span>
				) }

				<span
					className={
						'b-comparison-table-editor__row-label-text' +
						( isCategory
							? ' b-comparison-table-editor__row-label-text--category'
							: '' )
					}
				>
					{ isCategory
						? row.label || __( 'Category name', 'tribe' )
						: row.label || __( 'Feature name', 'tribe' ) }
				</span>

				{ ! isPreview && (
					<Button
						className="b-comparison-table-editor__row-delete"
						variant="link"
						icon={ tableRowDelete }
						isDestructive
						aria-label={ __( 'Remove row', 'tribe' ) }
						onClick={ ( event ) => {
							event.stopPropagation();
							onDeleteRow( row.clientId );
						} }
					/>
				) }
			</div>

			{ ! isCategory && columns.length > 0 && (
				<div className="b-comparison-table-editor__row-cells">
					<CellSummary cells={ row.cells } columns={ columns } />
				</div>
			) }
		</div>
	);
}
