import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, RadioControl, FormTokenField } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const { filterBarPosition, postTypes } = attributes;

	const availablePostTypes = useSelect( ( select ) => {
		const types = select( coreDataStore ).getPostTypes( {
			per_page: -1,
		} );
		return ( types ?? [] ).filter(
			( type ) =>
				type.slug !== 'attachment' &&
				type.visibility?.show_in_nav_menus === true
		);
	}, [] );

	const postTypeSuggestions = availablePostTypes.map(
		( type ) => type.labels?.singular_name || type.name
	);

	const postTypeLabelToSlug = availablePostTypes.reduce( ( map, type ) => {
		map[ type.labels?.singular_name || type.name ] = type.slug;
		return map;
	}, {} );

	const postTypeSlugToLabel = availablePostTypes.reduce( ( map, type ) => {
		map[ type.slug ] = type.labels?.singular_name || type.name;
		return map;
	}, {} );

	const blockProps = useBlockProps( {
		className: `b-faceted-directory b-faceted-directory--filter-bar-${ filterBarPosition }`,
	} );
	const innerBlockProps = useInnerBlocksProps(
		{ className: 'b-faceted-directory__inner' },
		{
			allowedBlocks: [ 'tribe/filter-bar', 'tribe/directory-grid' ],
			template: [
				[ 'tribe/filter-bar', { lock: { move: true, remove: true } } ],
				[
					'tribe/directory-grid',
					{ lock: { move: true, remove: true } },
				],
			],
			renderAppender: false,
		}
	);

	return (
		<div { ...blockProps }>
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<FormTokenField
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Post Types', 'tribe' ) }
							value={ ( postTypes ?? [] ).map(
								( slug ) => postTypeSlugToLabel[ slug ] || slug
							) }
							suggestions={ postTypeSuggestions }
							onChange={ ( tokens ) => {
								const slugs = tokens
									.map(
										( token ) =>
											postTypeLabelToSlug[ token ] ||
											token
									)
									.filter( Boolean );
								setAttributes( {
									postTypes:
										slugs.length > 0 ? slugs : [ 'post' ],
								} );
							} }
							help={ __(
								'Facets in the filter bar are limited to those configured for these post types.',
								'tribe'
							) }
						/>
						<div style={ { marginTop: '16px' } }>
							<RadioControl
								label={ __( 'Filter Bar Position', 'tribe' ) }
								options={ [
									{
										label: __( 'Top', 'tribe' ),
										value: 'top',
									},
									{
										label: __( 'Sidebar', 'tribe' ),
										value: 'sidebar',
									},
								] }
								selected={ filterBarPosition }
								help={ __(
									'The position of the filter bar relative to the grid.',
									'tribe'
								) }
								onChange={ ( value ) =>
									setAttributes( {
										filterBarPosition: value,
									} )
								}
							/>
						</div>
					</PanelBody>
				</InspectorControls>
			) }
			<div { ...innerBlockProps } />
		</div>
	);
}
