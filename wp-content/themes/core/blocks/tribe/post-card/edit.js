import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { ServerSideRender } from '@wordpress/server-side-render';
import { SelectControl, Stack } from '@wordpress/ui';
import ImageAspectRatioControl from 'components/ImageAspectRatioControl';
import metadata from './block.json';

export default function Edit( {
	attributes,
	context,
	setAttributes,
	isSelected,
} ) {
	const blockProps = useBlockProps();

	const { headingLevel, layout } = attributes;
	const headingItems = [
		{ label: __( 'H2', 'tribe' ), value: 'h2' },
		{ label: __( 'H3', 'tribe' ), value: 'h3' },
	];
	const layoutItems = [
		{ label: __( 'Vertical', 'tribe' ), value: 'vertical' },
		{ label: __( 'Horizontal', 'tribe' ), value: 'horizontal' },
	];

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
						<Stack direction="column" gap="md">
							<SelectControl
								label={ __( 'Heading Level', 'tribe' ) }
								items={ headingItems }
								value={
									headingItems.find(
										( item ) => item.value === headingLevel
									) ?? null
								}
								description={ __(
									'The heading level for the post title.',
									'tribe'
								) }
								onValueChange={ ( item ) => {
									if ( item ) {
										setAttributes( {
											headingLevel: item.value,
										} );
									}
								} }
							/>
							<SelectControl
								label={ __( 'Card Layout', 'tribe' ) }
								items={ layoutItems }
								value={
									layoutItems.find(
										( item ) => item.value === layout
									) ?? null
								}
								description={ __(
									'The layout for the post card.',
									'tribe'
								) }
								onValueChange={ ( item ) => {
									if ( item ) {
										setAttributes( { layout: item.value } );
									}
								} }
							/>
							<ImageAspectRatioControl
								value={ attributes.imageAspectRatio }
								onChange={ ( value ) =>
									setAttributes( { imageAspectRatio: value } )
								}
							/>
						</Stack>
					</PanelBody>
				</InspectorControls>
			) }
		</div>
	);
}
