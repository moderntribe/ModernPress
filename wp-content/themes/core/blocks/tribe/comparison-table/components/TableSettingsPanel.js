import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl } from '@wordpress/components';

/**
 * Sidebar panel for table-wide settings.
 *
 * @param {Object}   root0
 * @param {boolean}  root0.showFooterCtas         Whether footer CTA rows are rendered.
 * @param {Function} root0.onChangeShowFooterCtas Updates the footer CTA visibility flag.
 */
export default function TableSettingsPanel( {
	showFooterCtas,
	onChangeShowFooterCtas,
} ) {
	return (
		<PanelBody title={ __( 'Table Settings', 'tribe' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show footer CTAs', 'tribe' ) }
				checked={ showFooterCtas }
				onChange={ onChangeShowFooterCtas }
			/>
		</PanelBody>
	);
}
