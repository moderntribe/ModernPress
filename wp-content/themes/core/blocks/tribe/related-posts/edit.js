import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	FormTokenField,
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { ServerSideRender } from '@wordpress/server-side-render';
import { withSelect } from '@wordpress/data';

function Edit( { props, postList } ) {
	const blockProps = useBlockProps();
	const { attributes, isSelected, setAttributes } = props;
	const { hasAutomaticSelection, chosenPosts, postsToShow, layout } =
		attributes;

	const setChosenPosts = ( selectedPosts ) => {
		const newChosenPosts = selectedPosts.map( ( selectedPost ) => {
			/**
			 * if we've already added a value, it will appear as an object
			 * in this case, we can just return the existing object
			 */
			if ( typeof selectedPost !== 'string' ) {
				return selectedPost;
			}

			/**
			 * if this is a new value, it will appear as a string so we'll need to grab
			 * the post object via the post title. The name is provided via the Suggestions
			 * array in the FormTokenField component.
			 */
			const foundPost = postList.find(
				( post ) => post.title.rendered === selectedPost
			);

			if ( ! foundPost ) {
				return false;
			}

			return {
				value: foundPost.title.rendered,
				id: foundPost.id,
			};
		} );

		setAttributes( {
			chosenPosts: newChosenPosts,
		} );
	};

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block="tribe/related-posts"
				attributes={ attributes }
			/>
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Has Automatic Selection?', 'tribe' ) }
							help={ __(
								'If checked, this setting allows the block to control which posts show. Currently this is done by adding posts that have any categories in common with the current post.',
								'tribe'
							) }
							onChange={ ( value ) => {
								setAttributes( {
									hasAutomaticSelection: value,
								} );
							} }
							checked={ hasAutomaticSelection }
						/>
						{ ! hasAutomaticSelection && postList && (
							<div style={ { marginBottom: '16px' } }>
								<FormTokenField
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									__experimentalShowHowTo={ false }
									label={ __(
										'Manual Post Selection',
										'tribe'
									) }
									suggestions={ postList.map(
										( post ) => post.title.rendered
									) }
									value={ chosenPosts }
									onChange={ ( tokens ) => {
										setChosenPosts( tokens );
									} }
									placeholder={ __(
										'Start typing to search for posts',
										'tribe'
									) }
								/>
							</div>
						) }
						{ hasAutomaticSelection && (
							<RangeControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __(
									'Number of Posts to Display',
									'tribe'
								) }
								min={ 1 }
								max={ 9 }
								marks={ true }
								value={ postsToShow }
								onChange={ ( value ) => {
									setAttributes( {
										postsToShow: value,
									} );
								} }
							/>
						) }
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Block Layout', 'tribe' ) }
							value={ layout }
							options={ [
								{
									label: __( 'Grid', 'tribe' ),
									value: 'grid',
								},
								{
									label: __( 'List', 'tribe' ),
									value: 'list',
								},
							] }
							onChange={ ( value ) => {
								setAttributes( { layout: value } );
							} }
						/>
					</PanelBody>
				</InspectorControls>
			) }
		</div>
	);
}

export default withSelect( ( select, ownProps ) => {
	const { getEntityRecords } = select( 'core' );
	const { getCurrentPostId } = select( 'core/editor' );
	const currentPostId = getCurrentPostId();

	const postList = getEntityRecords( 'postType', 'post', {
		per_page: 100,
		exclude: [ currentPostId ],
	} );

	return {
		props: ownProps,
		postList,
	};
} )( Edit );
