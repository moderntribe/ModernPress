import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody } from '@wordpress/components';
import { ServerSideRender } from '@wordpress/server-side-render';
import ImageAspectRatioControl from 'components/ImageAspectRatioControl';
import metadata from './block.json';

export default function Edit( {
	attributes,
	context,
	setAttributes,
	isSelected,
} ) {
	const blockProps = useBlockProps();

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
						<ImageAspectRatioControl
							value={ attributes.imageAspectRatio }
							onChange={ ( value ) =>
								setAttributes( { imageAspectRatio: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>
			) }
		</div>
	);
}
