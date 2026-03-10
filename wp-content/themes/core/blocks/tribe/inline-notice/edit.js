import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	BaseControl,
	Button,
	Modal,
	PanelBody,
	SelectControl,
} from '@wordpress/components';
import { useState } from '@wordpress/element';
import IconPicker from 'components/IconPicker';
import DynamicColorPicker from 'components/DynamicColorPicker';
import { formatIconName } from 'blocks/tribe/icon-picker/utils';
import { ICONS_LIST } from 'blocks/tribe/icon-picker/icons/icons-list';

import './editor.pcss';

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const blockProps = useBlockProps();

	const {
		selectedIcon,
		isRounded,
		iconPadding,
		iconLabel,
		iconSize,
		searchQuery,
		selectedIconColor,
		selectedBgColor,
		heading,
		headerTextColorTheme,
		themeColor,
	} = attributes;

	const [ isModalOpen, setIsModalOpen ] = useState( false );

	/**
	 * Selected icon component based on the selectedIcon attribute
	 */
	const SelectedIconComponent =
		ICONS_LIST.find( ( icon ) => icon.key === selectedIcon )?.component ||
		null;

	/**
	 * Ensure selectedIcon is valid
	 */
	const validIcon =
		ICONS_LIST.find( ( { key } ) => key === selectedIcon ) || null;

	const classes = [
		'b-inline-notice',
		`b-inline-notice--theme-${ headerTextColorTheme }`,
	]
		.filter( Boolean )
		.join( ' ' );

	const innerBlocksProps = useInnerBlocksProps(
		{
			className: 'b-inline-notice__content',
		},
		{
			template: [ 'core/paragraph', {} ],
		}
	);

	return (
		<div { ...blockProps }>
			{ isModalOpen && (
				<Modal
					title={ __( 'Select Icon', 'tribe' ) }
					onRequestClose={ () => setIsModalOpen( false ) }
					size="medium"
					className="controls-tribe-icon-picker"
				>
					<IconPicker
						selectedIcon={ selectedIcon }
						isRounded={ isRounded }
						iconPadding={ iconPadding }
						iconLabel={ iconLabel }
						iconSize={ iconSize }
						searchQuery={ searchQuery }
						selectedIconColor={ selectedIconColor }
						selectedBgColor={ selectedBgColor }
						onChange={ ( changed ) => setAttributes( changed ) }
					/>
					<div
						style={ {
							display: 'flex',
							marginTop: '16px',
						} }
					>
						<Button
							isPrimary
							onClick={ () => setIsModalOpen( false ) }
						>
							{ __( 'Save & Close', 'tribe' ) }
						</Button>
					</div>
				</Modal>
			) }
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<BaseControl
							__nextHasNoMarginBottom
							id="icon-component"
							className="controls-tribe-icon-picker icon-preview icon-card"
						>
							{ validIcon ? (
								<>
									<div
										className="icon-image"
										style={ {
											backgroundColor:
												selectedBgColor ||
												'transparent',
											color: selectedIconColor || 'white',
											borderRadius: isRounded
												? '50%'
												: '0',
										} }
									>
										<SelectedIconComponent
											id="icon-component"
											style={ {
												color: selectedIconColor,
											} }
										/>
									</div>
									<p className="icon-name">
										{ formatIconName( validIcon.name ) }
									</p>
								</>
							) : (
								__( 'No Icon Selected', 'tribe' )
							) }
							<Button
								isPrimary
								onClick={ () => setIsModalOpen( true ) }
							>
								{ __( 'Open Icon Picker', 'tribe' ) }
							</Button>
						</BaseControl>
						<DynamicColorPicker
							colorAttribute="themeColor"
							colorValue={ themeColor }
							controlLabel={ __( 'Select Theme Color', 'tribe' ) }
							showTransparentOption={ false }
							onChange={ ( changed ) =>
								setAttributes( { ...changed } )
							}
						/>
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Header Text Color Theme', 'tribe' ) }
							help={ __(
								'The color theme for the header text.',
								'tribe'
							) }
							value={ headerTextColorTheme }
							options={ [
								{
									label: __( 'Light', 'tribe' ),
									value: 'light',
								},
								{
									label: __( 'Dark', 'tribe' ),
									value: 'dark',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { headerTextColorTheme: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>
			) }
			<aside
				className={ classes }
				style={ {
					'--theme-color': themeColor,
				} }
			>
				<div className="b-inline-notice__header">
					{ validIcon ? (
						<>
							<div
								className="b-inline-notice__icon-wrapper"
								style={ {
									backgroundColor:
										selectedBgColor || 'transparent',
									color: selectedIconColor || 'white',
									borderRadius: isRounded ? '50%' : '0',
									width: iconSize + 'px',
									height: iconSize + 'px',
									padding: iconPadding + 'px',
								} }
							>
								<SelectedIconComponent
									id="icon-component"
									style={ {
										color: selectedIconColor,
									} }
								/>
							</div>
						</>
					) : (
						__( 'No Icon Selected', 'tribe' )
					) }
					<RichText
						tagName="h2"
						className="b-inline-notice__heading t-body s-remove-margin--top"
						value={ heading }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
						placeholder={ __(
							'Enter Inline Notice Heading',
							'tribe'
						) }
						allowedFormats={ [
							'core/bold',
							'core/italic',
							'core/link',
						] }
					/>
				</div>
				<div { ...innerBlocksProps } />
			</aside>
		</div>
	);
}
