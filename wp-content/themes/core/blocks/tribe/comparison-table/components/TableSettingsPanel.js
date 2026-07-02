import { __ } from '@wordpress/i18n';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';

/**
 * Sidebar panel for table-wide settings.
 *
 * @param {Object}   root0
 * @param {boolean}  root0.showFooterCtas             Whether CTA rows are rendered.
 * @param {Function} root0.onChangeShowFooterCtas     Updates the CTA visibility flag.
 * @param {string}   root0.ctaPlacement               Where column CTAs are rendered.
 * @param {Function} root0.onChangeCtaPlacement       Updates the CTA placement.
 * @param {boolean}  root0.mobileCardView             Whether mobile card layout is enabled.
 * @param {Function} root0.onChangeMobileCardView     Updates the mobile card layout flag.
 * @param {boolean}  root0.mobileCardCarousel         Whether mobile cards render as a carousel.
 * @param {Function} root0.onChangeMobileCardCarousel Updates the mobile carousel flag.
 */
export default function TableSettingsPanel( {
	showFooterCtas,
	onChangeShowFooterCtas,
	ctaPlacement,
	onChangeCtaPlacement,
	mobileCardView,
	onChangeMobileCardView,
	mobileCardCarousel,
	onChangeMobileCardCarousel,
} ) {
	return (
		<PanelBody title={ __( 'Table Settings', 'tribe' ) }>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Show CTAs', 'tribe' ) }
				checked={ showFooterCtas }
				onChange={ onChangeShowFooterCtas }
			/>
			{ showFooterCtas && (
				<SelectControl
					__next40pxDefaultSize
					__nextHasNoMarginBottom
					label={ __( 'CTA placement', 'tribe' ) }
					value={ ctaPlacement }
					options={ [
						{
							label: __( 'Table footer', 'tribe' ),
							value: 'footer',
						},
						{
							label: __( 'Table header', 'tribe' ),
							value: 'header',
						},
					] }
					onChange={ onChangeCtaPlacement }
				/>
			) }
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __( 'Mobile card layout', 'tribe' ) }
				help={ __(
					'When off, the scrollable table is shown on all screen sizes.',
					'tribe'
				) }
				checked={ mobileCardView }
				onChange={ onChangeMobileCardView }
			/>
			{ mobileCardView && (
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Mobile card carousel', 'tribe' ) }
					help={ __(
						'Display one plan card at a time with pagination on mobile.',
						'tribe'
					) }
					checked={ mobileCardCarousel }
					onChange={ onChangeMobileCardCarousel }
				/>
			) }
		</PanelBody>
	);
}
