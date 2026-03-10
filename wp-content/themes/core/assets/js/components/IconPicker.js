import { __ } from '@wordpress/i18n';
import { useEffect, useMemo, useState } from '@wordpress/element';
import {
	RangeControl,
	TabPanel,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import DynamicColorPicker from 'components/DynamicColorPicker';
import { formatIconName } from 'blocks/tribe/icon-picker/utils';
import { ICONS_LIST } from 'blocks/tribe/icon-picker/icons/icons-list';

export default function IconPicker( {
	selectedIcon,
	isRounded,
	iconPadding,
	iconLabel,
	iconSize,
	searchQuery,
	selectedIconColor,
	selectedBgColor,
	onChange,
} ) {
	const sortedIcons = useMemo(
		() =>
			[ ...ICONS_LIST ].sort( ( a, b ) => a.key.localeCompare( b.key ) ),
		[]
	);
	const [ filteredIcons, setFilteredIcons ] = useState( sortedIcons );

	/**
	 * By default, the Icon Picker will use the color palette defined in
	 * theme.json. By defining a custom array of colors and passing it to the
	 * DynamicColorPicker components, we can give a custom set of
	 * colors to the editor.
	 */
	// const COLORS = [
	// 	{ name: __( 'Blue', 'tribe' ), color: '#0078d4' },
	// 	{ name: __( 'Purple', 'tribe' ), color: '#8661c5' },
	// 	{ name: __( 'Gray', 'tribe' ), color: '#737373' },
	// 	{ name: __( 'Light Gray', 'tribe' ), color: '#d2d2d2' },
	// 	{ name: __( 'Dark Gray', 'tribe' ), color: '#505050' },
	// 	{ name: __( 'Teal', 'tribe' ), color: '#008575' },
	// 	{ name: __( 'White', 'tribe' ), color: '#ffffff' },
	// 	{ name: __( 'Transparent', 'tribe' ), color: 'transparent' },
	// ];

	useEffect( () => {
		setFilteredIcons(
			sortedIcons.filter( ( { key } ) =>
				key
					.toLowerCase()
					.includes( ( searchQuery || '' ).toLowerCase() )
			)
		);
	}, [ searchQuery, sortedIcons ] );

	return (
		<TabPanel
			className="tribe-icon-picker-tab-panel"
			activeClass="active-tab"
			tabs={ [
				{ name: 'icon', title: __( 'Icon', 'tribe' ) },
				{ name: 'colors', title: __( 'Colors', 'tribe' ) },
				{ name: 'dimensions', title: __( 'Dimensions', 'tribe' ) },
			] }
		>
			{ ( tab ) => {
				if ( tab.name === 'icon' ) {
					return (
						<div className="icon-picker">
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Search Icons', 'tribe' ) }
								value={ searchQuery }
								onChange={ ( value ) =>
									onChange( { searchQuery: value } )
								}
							/>
							<div className="icon-grid">
								{ filteredIcons.map(
									( {
										key,
										component: IconComponent,
										name,
									} ) =>
										IconComponent && (
											<div
												key={ key }
												className={ `icon-item ${
													selectedIcon === key
														? 'selected'
														: ''
												}` }
												role="button"
												tabIndex={ 0 }
												onClick={ () =>
													onChange( {
														selectedIcon: key,
													} )
												}
												onKeyDown={ ( e ) => {
													if (
														e.key === 'Enter' ||
														e.key === ' '
													) {
														onChange( {
															selectedIcon: key,
														} );
													}
												} }
											>
												<IconComponent
													title={ formatIconName(
														name
													) }
												/>
											</div>
										)
								) }
							</div>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Custom label', 'tribe' ) }
								value={ iconLabel }
								onChange={ ( value ) =>
									onChange( { iconLabel: value } )
								}
								help={ __(
									'Add a custom label to describe the icon to help screen reader users.',
									'tribe'
								) }
							/>
						</div>
					);
				}
				if ( tab.name === 'colors' ) {
					return (
						<>
							<DynamicColorPicker
								controlLabel={ __( 'Icon Color', 'tribe' ) }
								colorAttribute={ 'selectedIconColor' }
								colorValue={ selectedIconColor }
								showTransparentOption={ false }
								onChange={ ( changed ) =>
									onChange( { ...changed } )
								}
							/>
							<DynamicColorPicker
								controlLabel={ __(
									'Background Color',
									'tribe'
								) }
								colorAttribute={ 'selectedBgColor' }
								colorValue={ selectedBgColor }
								onChange={ ( changed ) =>
									onChange( { ...changed } )
								}
							/>
						</>
					);
				}
				if ( tab.name === 'dimensions' ) {
					return (
						<>
							<RangeControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Container Padding', 'tribe' ) }
								value={ iconPadding }
								onChange={ ( value ) =>
									onChange( { iconPadding: value } )
								}
								min={ 0 }
								max={ 150 }
								afterIcon={ () => <span>px</span> }
							/>
							<RangeControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Container Size', 'tribe' ) }
								value={ iconSize }
								onChange={ ( value ) =>
									onChange( { iconSize: value } )
								}
								min={ 20 }
								max={ 300 }
								step={ 1 }
								afterIcon={ () => <span>px</span> }
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __( 'Rounded Icon', 'tribe' ) }
								checked={ isRounded }
								onChange={ ( value ) =>
									onChange( { isRounded: value } )
								}
							/>
						</>
					);
				}
			} }
		</TabPanel>
	);
}
