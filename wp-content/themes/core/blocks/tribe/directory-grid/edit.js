import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { useMemo } from '@wordpress/element';
import { ServerSideRender } from '@wordpress/server-side-render';
import metadata from './block.json';

export default function Edit( {
	attributes,
	setAttributes,
	isSelected,
	context,
} ) {
	const blockProps = useBlockProps();
	const { postsPerPage, showPagination } = attributes;
	const contextPostTypes = context[ 'tribe/faceted-directory/postTypes' ];

	// Block context never reaches a ServerSideRender preview, so send it along.
	const previewContext = useMemo(
		() => ( {
			tribePreviewContext: {
				postTypes: contextPostTypes ?? [ 'post' ],
			},
		} ),
		[ contextPostTypes ]
	);

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block={ metadata.name }
				attributes={ attributes }
				urlQueryArgs={ previewContext }
			/>
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Posts Per Page', 'tribe' ) }
							min={ 1 }
							max={ 99 }
							step={ 1 }
							value={ postsPerPage }
							onChange={ ( value ) =>
								setAttributes( { postsPerPage: value } )
							}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Show Pagination', 'tribe' ) }
							help={ __(
								'Display numeric pagination below the grid when there is more than one page of results.',
								'tribe'
							) }
							checked={ showPagination }
							onChange={ ( value ) =>
								setAttributes( { showPagination: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>
			) }
		</div>
	);
}
