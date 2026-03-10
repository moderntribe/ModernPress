import { useSettings } from '@wordpress/block-editor';
import { BaseControl, ColorPalette } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function DynamicColorPicker( {
	colorAttribute,
	colorValue,
	onChange,
	controlLabel = __( 'Select Color', 'tribe' ),
	useOpacity = false,
	showTransparentOption = true,
	colorsToUse = [],
} ) {
	/**
	 * useSettings returns an array where each item is the return value of the
	 * setting requested. In this case, we are requesting only one setting,
	 * 'color.palette', so accessing the first item of the array gives us our
	 * colors defined in theme.json
	 */
	const themeColors = useSettings( 'color.palette' );

	/**
	 * @function getThemeColors
	 *
	 * @description Retrieves the theme colors and formats them for use in the ColorPalette component.
	 */
	const getThemeColors = () => {
		if ( ! themeColors || ! Array.isArray( themeColors[ 0 ] ) ) {
			return [];
		}

		const colors = themeColors[ 0 ].map( ( { name, color } ) => ( {
			name,
			color,
		} ) );

		// If the showTransparentOption flag is true, add a 'Transparent' option
		if ( showTransparentOption ) {
			colors.push( {
				name: __( 'Transparent', 'tribe' ),
				color: 'transparent',
			} );
		}

		return colors;
	};

	return (
		<BaseControl __nextHasNoMarginBottom>
			<BaseControl.VisualLabel>{ controlLabel }</BaseControl.VisualLabel>
			<ColorPalette
				__experimentalIsRenderedInSidebar={ true }
				colors={
					colorsToUse.length > 0 ? colorsToUse : getThemeColors()
				}
				disableCustomColors={ false }
				enableAlpha={ useOpacity }
				value={ colorValue }
				clearable={ false }
				onChange={ ( value ) =>
					onChange( {
						[ colorAttribute ]: value,
					} )
				}
			/>
		</BaseControl>
	);
}
