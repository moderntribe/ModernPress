import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

/**
 * Sidebar controls for editing the currently selected plan column.
 *
 * @param {Object}   root0
 * @param {Object}   root0.selectedColumn      Column being edited, if any.
 * @param {number}   root0.selectedColumnIndex Index of the selected column.
 * @param {boolean}  root0.showFooterCtas      Whether CTA fields should be shown.
 * @param {Function} root0.onUpdateColumn      Merges partial changes into a column.
 */
export default function ColumnInspectorPanel( {
	selectedColumn,
	selectedColumnIndex,
	showFooterCtas,
	onUpdateColumn,
} ) {
	if ( ! selectedColumn ) {
		return null;
	}

	return (
		<PanelBody title={ __( 'Columns', 'tribe' ) } initialOpen={ true }>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Plan label', 'tribe' ) }
				value={ selectedColumn.label }
				onChange={ ( value ) =>
					onUpdateColumn( selectedColumnIndex, { label: value } )
				}
			/>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Subtitle', 'tribe' ) }
				value={ selectedColumn.subtitle }
				onChange={ ( value ) =>
					onUpdateColumn( selectedColumnIndex, { subtitle: value } )
				}
			/>
			<TextControl
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				label={ __( 'Badge', 'tribe' ) }
				value={ selectedColumn.badge }
				onChange={ ( value ) =>
					onUpdateColumn( selectedColumnIndex, { badge: value } )
				}
			/>
			{ showFooterCtas && (
				<>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'CTA label', 'tribe' ) }
						value={ selectedColumn.ctaLabel }
						onChange={ ( value ) =>
							onUpdateColumn( selectedColumnIndex, {
								ctaLabel: value,
							} )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'CTA URL', 'tribe' ) }
						value={ selectedColumn.ctaUrl }
						onChange={ ( value ) =>
							onUpdateColumn( selectedColumnIndex, {
								ctaUrl: value,
							} )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Open CTA in new tab', 'tribe' ) }
						checked={ !! selectedColumn.ctaOpensInNewTab }
						onChange={ ( value ) =>
							onUpdateColumn( selectedColumnIndex, {
								ctaOpensInNewTab: value,
							} )
						}
					/>
					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={ __( 'CTA style', 'tribe' ) }
						value={ selectedColumn.ctaStyle || 'outlined' }
						options={ [
							{
								label: __( 'Default', 'tribe' ),
								value: 'default',
							},
							{
								label: __( 'Outlined', 'tribe' ),
								value: 'outlined',
							},
							{
								label: __( 'Ghost', 'tribe' ),
								value: 'ghost',
							},
						] }
						onChange={ ( value ) =>
							onUpdateColumn( selectedColumnIndex, {
								ctaStyle: value,
							} )
						}
					/>
				</>
			) }
		</PanelBody>
	);
}
