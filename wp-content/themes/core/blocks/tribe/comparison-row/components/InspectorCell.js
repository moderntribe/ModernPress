import { __ } from '@wordpress/i18n';
import { RadioControl, TextControl } from '@wordpress/components';

/** Radio options for the per-column cell type control. */
const getCellTypeOptions = () => [
	{ label: __( 'Check', 'tribe' ), value: 'check' },
	{ label: __( 'Dash', 'tribe' ), value: 'dash' },
	{ label: __( 'Text', 'tribe' ), value: 'text' },
];

/**
 * Sidebar controls for one column's cell type and optional text value.
 *
 * @param {Object}   root0
 * @param {Object}   root0.column        Column definition for the label.
 * @param {number}   root0.index         Column index within the row.
 * @param {Object}   root0.cell          Current cell state for this column.
 * @param {Function} root0.onSetCellType Updates only the cell type.
 * @param {Function} root0.onUpdateCell  Merges partial cell attribute changes.
 */
export default function InspectorCell( {
	column,
	index,
	cell,
	onSetCellType,
	onUpdateCell,
} ) {
	const cellType = cell?.type || 'dash';

	return (
		<div className="wp-block-tribe-comparison-row-editor__inspector-cell">
			<p className="wp-block-tribe-comparison-row-editor__inspector-cell-label">
				{ column.label || __( 'Column', 'tribe' ) + ` ${ index + 1 }` }
			</p>
			<RadioControl
				className="wp-block-tribe-comparison-row-editor__cell-type"
				label={ __( 'Cell type', 'tribe' ) }
				hideLabelFromVision
				selected={ cellType }
				options={ getCellTypeOptions() }
				onChange={ ( value ) => onSetCellType( index, value ) }
			/>
			{ cellType === 'text' && (
				<TextControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'Value', 'tribe' ) }
					value={ cell?.value || '' }
					onChange={ ( value ) =>
						onUpdateCell( index, { type: 'text', value } )
					}
				/>
			) }
		</div>
	);
}
