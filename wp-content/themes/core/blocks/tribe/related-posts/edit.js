import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { ServerSideRender } from '@wordpress/server-side-render';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import PostSearchField from '../../../assets/js/components/PostSearchField';

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

export default function Edit( { attributes, isSelected, setAttributes } ) {
	const blockProps = useBlockProps();
	const {
		hasAutomaticSelection,
		chosenPosts,
		postsToShow,
		taxonomySlug,
		layout,
	} = attributes;

	const { taxonomies, currentPostId } = useSelect( ( select ) => {
		const { getCurrentPostId, getCurrentPostType } =
			select( 'core/editor' );
		const currentType = getCurrentPostType();

		let taxList = [];
		if ( currentType ) {
			const allTaxonomies =
				select( coreDataStore ).getTaxonomies( {
					type: currentType,
					per_page: -1,
				} ) ?? [];
			taxList = allTaxonomies.filter(
				( taxonomy ) => taxonomy.visibility?.show_in_rest !== false
			);
		}

		return {
			taxonomies: taxList,
			currentPostId: getCurrentPostId(),
		};
	}, [] );

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

	const excludeIds = useMemo(
		() => ( currentPostId ? [ currentPostId ] : [] ),
		[ currentPostId ]
	);

	const setChosenPosts = ( selectedPosts ) => {
		setAttributes( { chosenPosts: selectedPosts } );
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
						{ ! hasAutomaticSelection && (
							<PostSearchField
								label={ __( 'Manual Post Selection', 'tribe' ) }
								placeholder={ __(
									'Type to search for posts…',
									'tribe'
								) }
								value={ chosenPosts }
								onChange={ setChosenPosts }
								excludeIds={ excludeIds }
							/>
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
