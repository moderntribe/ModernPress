import { __ } from '@wordpress/i18n';
import { PanelBody } from '@wordpress/components';

import InspectorCell from './InspectorCell';

/**
 * Sidebar panel listing per-column cell controls for a feature row.
 *
 * @param {Object}   root0
 * @param {Array}    root0.columns       Parent table column definitions.
 * @param {Array}    root0.syncedCells   Cells normalized to the column count.
 * @param {Function} root0.onSetCellType Updates only a cell's type.
 * @param {Function} root0.onUpdateCell  Merges partial cell attribute changes.
 */
export default function ColumnValuesPanel( {
	columns,
	syncedCells,
	onSetCellType,
	onUpdateCell,
} ) {
	if ( columns.length === 0 ) {
		return null;
	}

	return (
		<PanelBody
			title={ __( 'Column values', 'tribe' ) }
			initialOpen={ true }
		>
			{ columns.map( ( column, index ) => (
				<InspectorCell
					key={ column.id || index }
					column={ column }
					index={ index }
					cell={ syncedCells[ index ] }
					onSetCellType={ onSetCellType }
					onUpdateCell={ onUpdateCell }
				/>
			) ) }
		</PanelBody>
	);
}
