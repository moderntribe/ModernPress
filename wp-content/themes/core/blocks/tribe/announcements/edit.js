import {
	InspectorControls,
	RichText,
	useBlockProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import './editor.pcss';

export default function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();

	const {
		heading,
		body,
		ctaLabel,
		ctaLink,
		ctaStyle,
		theme,
		textAlignment,
		orientation,
		dismissible,
	} = attributes;

	const darkThemes = [ 'brand', 'black', 'error' ];
	const isDark = darkThemes.includes( theme );

	const classes = [
		'b-announcement',
		`b-announcement--theme-${ theme }`,
		`b-announcement--align-${ textAlignment }`,
		orientation === 'vertical' ? 'b-announcement--orientation-vertical' : '',
		isDark ? 'is-style-dark' : '',
	]
		.filter( Boolean )
		.join( ' ' );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody
					title={ __( 'Call to Action', 'tribe' ) }
					initialOpen={ true }
				>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'CTA Label', 'tribe' ) }
						help={ __(
							'The text that will be displayed on the call to action button.',
							'tribe'
						) }
						value={ ctaLabel }
						onChange={ ( value ) =>
							setAttributes( { ctaLabel: value } )
						}
					/>
					<TextControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'CTA URL', 'tribe' ) }
						help={ __(
							'The URL that the call to action button will link to.',
							'tribe'
						) }
						value={ ctaLink }
						onChange={ ( value ) =>
							setAttributes( { ctaLink: value } )
						}
						type="url"
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'CTA Style', 'tribe' ) }
						help={ __(
							'Choose between a primary button or a ghost button style for the call to action.',
							'tribe'
						) }
						value={ ctaStyle }
						options={ [
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
							setAttributes( { ctaStyle: value } )
						}
					/>
				</PanelBody>

				<PanelBody
					title={ __( 'Display Settings', 'tribe' ) }
					initialOpen={ false }
				>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Theme', 'tribe' ) }
						help={ __(
							'The color theme of the announcement block.',
							'tribe'
						) }
						value={ theme }
						options={ [
							{ label: __( 'Brand', 'tribe' ), value: 'brand' },
							{ label: __( 'Black', 'tribe' ), value: 'black' },
							{
								label: __( 'Warning', 'tribe' ),
								value: 'warning',
							},
							{ label: __( 'Error', 'tribe' ), value: 'error' },
						] }
						onChange={ ( value ) =>
							setAttributes( { theme: value } )
						}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Text Alignment', 'tribe' ) }
						help={ __(
							'The alignnent of the text within the announcement block.',
							'tribe'
						) }
						value={ textAlignment }
						options={ [
							{ label: __( 'Left', 'tribe' ), value: 'left' },
							{ label: __( 'Center', 'tribe' ), value: 'center' },
						] }
						onChange={ ( value ) =>
							setAttributes( { textAlignment: value } )
						}
					/>
					<SelectControl
						__nextHasNoMarginBottom
						__next40pxDefaultSize
						label={ __( 'Orientation', 'tribe' ) }
						help={ __(
							'Desktop layout: horizontal places heading, body, and CTA in one row. Vertical stacks them. On mobile, content is always stacked.',
							'tribe'
						) }
						value={ orientation }
						options={ [
							{
								label: __( 'Horizontal', 'tribe' ),
								value: 'horizontal',
							},
							{
								label: __( 'Vertical', 'tribe' ),
								value: 'vertical',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { orientation: value } )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Dismissible', 'tribe' ) }
						help={ __(
							'If enabled, allow users to dismiss the announcement.',
							'tribe'
						) }
						checked={ dismissible }
						onChange={ ( value ) =>
							setAttributes( { dismissible: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<aside className={ classes }>
				<div className="b-announcement__inner">
					<RichText
						tagName="h2"
						className="b-announcement__heading t-body"
						value={ heading }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
						placeholder={ __(
							'Enter announcement heading…',
							'tribe'
						) }
					/>

					<RichText
						tagName="p"
						className="b-announcement__body t-body"
						value={ body }
						onChange={ ( value ) =>
							setAttributes( { body: value } )
						}
						placeholder={ __(
							'Enter announcement body text…',
							'tribe'
						) }
					/>

					{ ctaLabel && ctaLink && (
						<div className="b-announcement__cta-wrapper l-flex">
							<span className="b-announcement__cta">
								<a
									href={ ctaLink }
									className={ `a-btn-${ ctaStyle }` }
								>
									{ ctaLabel }
								</a>
							</span>
						</div>
					) }
				</div>

				{ dismissible && (
					<div className="b-announcement__dismiss-wrapper">
						<button
							type="button"
							className="b-announcement__dismiss"
							aria-label="Dismiss announcement"
						>
							<span className="b-announcement__dismiss-text">
								{ __( 'Dismiss', 'tribe' ) }
							</span>
						</button>
					</div>
				) }
			</aside>
		</div>
	);
}
