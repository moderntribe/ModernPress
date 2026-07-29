import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';

/**
 * Sidebar panel for a row's type and label.
 *
 * @param {Object}   root0
 * @param {string}   root0.rowType         Current row type (`category` or `feature`).
 * @param {string}   root0.label           Row label shown in the table.
 * @param {Function} root0.onChangeRowType Updates the row type.
 * @param {Function} root0.onChangeLabel   Updates the row label.
 */
export default function RowSettingsPanel( {
	rowType,
	label,
	onChangeRowType,
	onChangeLabel,
} ) {
	const isCategory = rowType === 'category';

	return (
		<PanelBody title={ __( 'Row Settings', 'tribe' ) }>
			<SelectControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Row type', 'tribe' ) }
				value={ rowType }
				options={ [
					{
						label: __( 'Feature', 'tribe' ),
						value: 'feature',
					},
					{
						label: __( 'Category', 'tribe' ),
						value: 'category',
					},
				] }
				onChange={ onChangeRowType }
			/>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={
					isCategory
						? __( 'Category label', 'tribe' )
						: __( 'Feature label', 'tribe' )
				}
				value={ label }
				onChange={ onChangeLabel }
			/>
		</PanelBody>
	);
}
