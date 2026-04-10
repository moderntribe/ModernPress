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

const getPickerLabel = ( post ) => {
	if ( post.pickerLabel ) {
		return post.pickerLabel;
	}

	return post.value;
};

const LATEST_ITEMS_VALUE = '__latest__';

const getEffectiveTaxonomySlug = ( taxonomies = [], taxonomySlug = '' ) => {
	if ( taxonomySlug === LATEST_ITEMS_VALUE ) {
		return taxonomySlug;
	}

	if ( taxonomies.some( ( taxonomy ) => taxonomy.slug === taxonomySlug ) ) {
		return taxonomySlug;
	}

	if ( taxonomies.some( ( taxonomy ) => taxonomy.slug === 'category' ) ) {
		return 'category';
	}

	return taxonomies[ 0 ]?.slug ?? '';
};

function Edit( { props, postList, taxonomies } ) {
	const blockProps = useBlockProps();
	const { attributes, isSelected, setAttributes } = props;
	const {
		hasAutomaticSelection,
		chosenPosts,
		postsToShow,
		taxonomySlug,
		layout,
	} = attributes;
	const effectiveTaxonomySlug = getEffectiveTaxonomySlug(
		taxonomies,
		taxonomySlug
	);
	const taxonomyOptions = [
		{
			label: __( 'Latest Items', 'tribe' ),
			value: LATEST_ITEMS_VALUE,
		},
		...( taxonomies ?? [] ).map( ( taxonomy ) => ( {
			label: taxonomy.name,
			value: taxonomy.slug,
		} ) ),
	];
	const tokenValues = ( chosenPosts ?? [] ).map( ( chosenPost ) => {
		if ( typeof chosenPost === 'string' ) {
			return chosenPost;
		}

		const matchedPost = ( postList ?? [] ).find(
			( post ) => post.id === chosenPost.id
		);
		if ( matchedPost ) {
			return {
				...chosenPost,
				value: matchedPost.pickerLabel,
			};
		}

		return chosenPost;
	} );

	const setChosenPosts = ( selectedPosts ) => {
		const newChosenPosts = selectedPosts
			.map( ( selectedPost ) => {
				/**
				 * if we've already added a value, it will appear as an object
				 * in this case, we can just return the existing object
				 */
				if ( typeof selectedPost !== 'string' ) {
					return selectedPost;
				}

				/**
				 * if this is a new value, it will appear as a string so we'll need to grab
				 * the post object via the picker label shown in the suggestions list.
				 */
				const foundPost = ( postList ?? [] ).find(
					( post ) => post.pickerLabel === selectedPost
				);

				if ( ! foundPost ) {
					return false;
				}

				return {
					value: foundPost.pickerLabel,
					id: foundPost.id,
				};
			} )
			.filter( Boolean );

		setAttributes( {
			chosenPosts: newChosenPosts,
		} );
	};

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block="tribe/related-posts"
				attributes={ {
					...attributes,
					taxonomySlug: effectiveTaxonomySlug,
				} }
			/>
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Has Automatic Selection?', 'tribe' ) }
							help={ __(
								'When enabled, the block shows posts related by the selected option. Taxonomy matches fall back to latest items if no matches are found.',
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
									suggestions={ ( postList ?? [] ).map(
										getPickerLabel
									) }
									value={ tokenValues }
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
							<>
								<SelectControl
									__next40pxDefaultSize
									__nextHasNoMarginBottom
									label={ __( 'Related By', 'tribe' ) }
									help={ __(
										'Choose whether automatic mode uses the latest items or matches by a taxonomy.',
										'tribe'
									) }
									value={ effectiveTaxonomySlug }
									options={ taxonomyOptions }
									onChange={ ( value ) => {
										setAttributes( {
											taxonomySlug: value,
										} );
									} }
								/>
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
							</>
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
	const { getEntityRecords, getPostTypes, getTaxonomies } = select( 'core' );
	const { getCurrentPostId, getCurrentPostType } = select( 'core/editor' );
	const currentPostId = getCurrentPostId();
	const currentPostType = getCurrentPostType();

	const EXCLUDED_TYPES = new Set( [
		'attachment',
		'page',
		'wp_block',
		'wp_template',
		'wp_template_part',
		'wp_navigation',
	] );

	const allPostTypes = getPostTypes( { per_page: -1 } ) ?? [];
	const selectableTypes = allPostTypes.filter(
		( type ) => type.viewable === true && ! EXCLUDED_TYPES.has( type.slug )
	);
	const taxonomies = currentPostType
		? (
				getTaxonomies( {
					type: currentPostType,
					per_page: -1,
				} ) ?? []
		  ).filter(
				( taxonomy ) => taxonomy.visibility?.show_in_rest !== false
		  )
		: [];

	const postList = selectableTypes.flatMap( ( type ) => {
		const records = getEntityRecords( 'postType', type.slug, {
			per_page: 100,
			exclude: [ currentPostId ],
		} );
		return ( records ?? [] ).map( ( post ) => ( {
			...post,
			pickerLabel: `${ post.title.rendered } (${ type.labels.singular_name })`,
		} ) );
	} );

	return {
		props: ownProps,
		postList,
		taxonomies,
	};
} )( Edit );
