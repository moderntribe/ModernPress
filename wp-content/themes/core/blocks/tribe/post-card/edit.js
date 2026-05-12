import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from '@wordpress/components';
import { ServerSideRender } from '@wordpress/server-side-render';
import metadata from './block.json';

export default function Edit( {
	attributes,
	context,
	setAttributes,
	isSelected,
} ) {
	const blockProps = useBlockProps();

	const { headingLevel, layout } = attributes;

	/**
	 * Set up a query string to pass the post id to the server side render.
	 * As far as I can tell, this is the only way to pass the post id from context
	 * to the server side render function for displaying in the editor.
	 */
	const urlQueryArgs = { editorPostId: context.postId };

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block={ metadata.name }
				attributes={ attributes }
				urlQueryArgs={ urlQueryArgs }
			/>
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Heading Level', 'tribe' ) }
							value={ headingLevel }
							help={ __(
								'The heading level for the post title.',
								'tribe'
							) }
							options={ [
								{ label: __( 'H2', 'tribe' ), value: 'h2' },
								{ label: __( 'H3', 'tribe' ), value: 'h3' },
							] }
							onChange={ ( value ) =>
								setAttributes( { headingLevel: value } )
							}
						/>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Card Layout', 'tribe' ) }
							value={ layout }
							help={ __(
								'The layout for the post card.',
								'tribe'
							) }
							options={ [
								{
									label: __( 'Vertical', 'tribe' ),
									value: 'vertical',
								},
								{
									label: __( 'Horizontal', 'tribe' ),
									value: 'horizontal',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { layout: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>
			) }
		</div>
	);
}
