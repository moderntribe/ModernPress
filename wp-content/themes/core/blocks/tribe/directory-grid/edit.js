import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import { ServerSideRender } from '@wordpress/server-side-render';
import metadata from './block.json';

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const blockProps = useBlockProps();
	const { postsPerPage, showPagination } = attributes;

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block={ metadata.name }
				attributes={ attributes }
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
