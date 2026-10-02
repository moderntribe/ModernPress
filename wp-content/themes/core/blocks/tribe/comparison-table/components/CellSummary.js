import { __ } from '@wordpress/i18n';

import { syncCellsToColumnCount } from '../js/utils';

/**
 * Read-only grid of cell values shown beneath a feature row in the editor.
 *
 * @param {Object} root0
 * @param {Array}  root0.cells   Row cell values keyed by column index.
 * @param {Array}  root0.columns Table column definitions.
 */
export default function CellSummary( { cells, columns } ) {
	const syncedCells = syncCellsToColumnCount( cells, columns.length );

	return (
		<div
			className="b-comparison-table-editor__cell-summary"
			aria-label={ __( 'Column values', 'tribe' ) }
		>
			{ columns.map( ( column, index ) => {
				const cell = syncedCells[ index ];
				const type = cell?.type || 'dash';

				let display = '—';

				if ( type === 'check' ) {
					display = '✓';
				} else if ( type === 'text' ) {
					display = cell?.value || '…';
				}

				return (
					<span
						key={ column.id || index }
						className={
							'b-comparison-table-editor__cell-summary-item' +
							` b-comparison-table-editor__cell-summary-item--${ type }`
						}
						title={
							( column.label || __( 'Column', 'tribe' ) ) +
							`: ${ display }`
						}
					>
						<span className="b-comparison-table-editor__cell-summary-plan">
							{ column.label || index + 1 }
						</span>
						<span className="b-comparison-table-editor__cell-summary-value">
							{ display }
						</span>
					</span>
				);
			} ) }
		</div>
	);
}
