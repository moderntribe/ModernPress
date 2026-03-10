import { __ } from '@wordpress/i18n';
import { PanelRow } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { formatIconName } from './utils';
import IconPicker from 'components/IconPicker';
import { ICONS_LIST } from './icons/icons-list';

import './editor.pcss';

export default function Edit( { attributes, setAttributes } ) {
	const {
		selectedIcon,
		isRounded,
		iconPadding,
		iconLabel,
		iconSize,
		searchQuery,
		selectedIconColor,
		selectedBgColor,
	} = attributes;

	const SelectedIconComponent =
		ICONS_LIST.find( ( icon ) => icon.key === selectedIcon )?.component ||
		null;

	// Ensure selectedIcon is valid
	const validIcon =
		ICONS_LIST.find( ( { key } ) => key === selectedIcon ) || null;

	return (
		<div { ...useBlockProps() }>
			{ validIcon ? (
				<div
					className="icon-wrapper"
					style={ {
						'--icon-picker--background-color':
							selectedBgColor || 'transparent',
						'--icon-picker--icon-color':
							selectedIconColor || 'white',
						'--icon-picker--border-radius': isRounded ? '50%' : '0',
						'--icon-picker--icon-size': `${ iconSize }px` || '100%',
						'--icon-picker--icon-padding': `${ iconPadding }px`,
					} }
					{ ...( selectedBgColor === 'transparent'
						? { 'data-transparent': true }
						: {} ) }
				>
					<SelectedIconComponent
						style={ { color: selectedIconColor } }
						aria-label={
							iconLabel || formatIconName( validIcon.name )
						}
					/>
				</div>
			) : (
				__( 'No Icon Selected', 'tribe' )
			) }

			<InspectorControls>
				<PanelRow className="controls-tribe-icon-picker icon-preview">
					{ validIcon ? (
						<>
							<div
								className="icon-image"
								style={ {
									backgroundColor:
										selectedBgColor || 'transparent',
									color: selectedIconColor || 'white',
									borderRadius: isRounded ? '50%' : '0',
								} }
							>
								<SelectedIconComponent
									style={ { color: selectedIconColor } }
								/>
							</div>
							<p className="icon-name">
								{ formatIconName( validIcon.name ) }
							</p>
						</>
					) : (
						__( 'No Icon Selected', 'tribe' )
					) }
				</PanelRow>

				<div className="controls-tribe-icon-picker">
					<IconPicker
						selectedIcon={ selectedIcon }
						isRounded={ isRounded }
						iconPadding={ iconPadding }
						iconLabel={ iconLabel }
						iconSize={ iconSize }
						searchQuery={ searchQuery }
						selectedIconColor={ selectedIconColor }
						selectedBgColor={ selectedBgColor }
						onChange={ ( changed ) =>
							setAttributes( { ...changed } )
						}
					/>
				</div>
			</InspectorControls>
		</div>
	);
}
