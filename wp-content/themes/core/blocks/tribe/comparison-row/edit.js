import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

import {
	syncCellsToColumnCount,
	isCategoryRow,
} from '../comparison-table/js/utils';
import ColumnValuesPanel from './components/ColumnValuesPanel';
import RowSettingsPanel from './components/RowSettingsPanel';

import './editor.pcss';

/**
 * Sidebar-only editor for a comparison row. Canvas output is hidden; row content
 * is managed through inspector panels and the parent table's row list.
 *
 * @param {Object}   root0
 * @param {Object}   root0.attributes    Row block attributes.
 * @param {Function} root0.setAttributes Updates row block attributes.
 * @param {Object}   root0.context       Parent-provided column context.
 */
export default function Edit( { attributes, setAttributes, context } ) {
	const { rowType, label, cells } = attributes;
	const columns = context[ 'tribe/comparison-table/columns' ] || [];
	const columnCount = columns.length;
	const syncedCells = syncCellsToColumnCount( cells, columnCount );
	const isCategory = rowType === 'category';

	const blockProps = useBlockProps( {
		className: 'wp-block-tribe-comparison-row-editor is-sidebar-only',
	} );

	/**
	 * Merges partial changes into one cell while preserving the others.
	 *
	 * @param {number} cellIndex Target cell index.
	 * @param {Object} nextCell  Partial cell attribute changes.
	 */
	const updateCell = ( cellIndex, nextCell ) => {
		const nextCells = syncedCells.map( ( cell, index ) =>
			index === cellIndex ? { ...cell, ...nextCell } : cell
		);

		setAttributes( { cells: nextCells } );
	};

	/**
	 * Changes a cell's display type without clearing its stored text value.
	 *
	 * @param {number} cellIndex Target cell index.
	 * @param {string} nextType  One of `check`, `dash`, or `text`.
	 */
	const setCellType = ( cellIndex, nextType ) => {
		updateCell( cellIndex, { type: nextType } );
	};

	return (
		<>
			<div { ...blockProps } aria-hidden="true" />

			<InspectorControls>
				<RowSettingsPanel
					rowType={ rowType }
					label={ label }
					onChangeRowType={ ( value ) => {
						if ( isCategoryRow( value ) ) {
							setAttributes( { rowType: value, cells: [] } );
							return;
						}

						setAttributes( {
							rowType: value,
							cells: syncCellsToColumnCount( cells, columnCount ),
						} );
					} }
					onChangeLabel={ ( value ) =>
						setAttributes( { label: value } )
					}
				/>

				{ ! isCategory && (
					<ColumnValuesPanel
						columns={ columns }
						syncedCells={ syncedCells }
						onSetCellType={ setCellType }
						onUpdateCell={ updateCell }
					/>
				) }
			</InspectorControls>
		</>
	);
}
