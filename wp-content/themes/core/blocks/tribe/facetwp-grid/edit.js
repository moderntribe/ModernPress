import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	RangeControl,
	ToggleControl,
} from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { ServerSideRender } from '@wordpress/server-side-render';
import metadata from './block.json';

function Edit( { attributes, setAttributes, isSelected, postTypes } ) {
	const blockProps = useBlockProps();

	const { postType, postsPerPage, showAllPosts, showPagination } = attributes;

	const filteredPostTypes = postTypes?.filter(
		( type ) => type.visibility.show_in_nav_menus === true
	);

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block={ metadata.name }
				attributes={ attributes }
			/>
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Post Type', 'tribe' ) }
							value={ postType }
							options={ filteredPostTypes?.map( ( type ) => ( {
								label: type.labels.singular_name,
								value: type.slug,
							} ) ) }
							help={ __(
								'Choose a post type to display in the grid.',
								'tribe'
							) }
							onChange={ ( value ) =>
								setAttributes( { postType: value } )
							}
						/>
						<ToggleControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Show All Posts', 'tribe' ) }
							help={ __(
								'If checked, all posts will be displayed in the grid. If unchecked, the number of posts displayed will be limited to the number set in the "Posts Per Page" setting.',
								'tribe'
							) }
							checked={ showAllPosts }
							onChange={ ( value ) =>
								setAttributes( { showAllPosts: value } )
							}
						/>
						{ ! showAllPosts && (
							<RangeControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Posts Per Page', 'tribe' ) }
								min={ 1 }
								max={ 24 }
								step={ 1 }
								value={ postsPerPage }
								onChange={ ( value ) =>
									setAttributes( { postsPerPage: value } )
								}
							/>
						) }
						<ToggleControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Show Pagination', 'tribe' ) }
							help={ __(
								'If checked, pagination will be displayed for the grid. Note: The pager facet must first be created in the FacetWP settings and the pager type must be set to "numbers".',
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

export default withSelect( ( select, ownProps ) => {
	const postTypes = select( 'core' ).getPostTypes();

	return {
		postTypes,
		...ownProps,
	};
} )( Edit );
