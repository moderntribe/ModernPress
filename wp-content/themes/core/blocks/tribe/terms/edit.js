import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { withSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

function Edit( { props, taxonomies } ) {
	const blockProps = useBlockProps();
	const { attributes, isSelected, setAttributes } = props;
	const { taxonomyToUse, onlyPrimaryTerm, hasLinks } = attributes;

	/**
	 * Prepare taxonomies provided by the `withSelect` function for
	 * taxonomyToUse SelectControl options.
	 *
	 * We also add a default empty option to prompt the user to
	 * select a taxonomy.
	 *
	 * Because we are using an onChange handler in the SelectControl below,
	 * the user must make a selection for the attribute to be set. If there was
	 * only one option, it would be impossible to set the attribute as
	 * no change would occur.
	 */
	const listTaxonomies = taxonomies
		? taxonomies.map( ( taxonomy ) => {
				return {
					label: taxonomy.name,
					value: taxonomy.slug,
				};
		  } )
		: [];

	return (
		<div { ...blockProps }>
			<ServerSideRender block="tribe/terms" attributes={ attributes } />
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<SelectControl
							__nextHasNoMarginBottom
							__next40pxDefaultSize
							label={ __( 'Taxonomy', 'tribe' ) }
							value={ taxonomyToUse ?? '' }
							help={ __(
								'The taxonomy to pull terms from.',
								'tribe'
							) }
							onChange={ ( newValue ) => {
								setAttributes( {
									taxonomyToUse: newValue,
								} );
							} }
							options={ [
								{
									disabled: true,
									label: 'Select a Taxonomy',
									value: '',
								},
								...listTaxonomies,
							] }
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Should only grab the primary term',
								'tribe'
							) }
							help={ __(
								'Default functionality is to grab all terms in the taxonomy.',
								'tribe'
							) }
							checked={ !! onlyPrimaryTerm }
							onChange={ ( newValue ) =>
								setAttributes( {
									onlyPrimaryTerm: newValue,
								} )
							}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __(
								'Terms should display as links',
								'tribe'
							) }
							help={ __(
								'Default functionality is that terms do not display as links.',
								'tribe'
							) }
							checked={ !! hasLinks }
							onChange={ ( newValue ) =>
								setAttributes( {
									hasLinks: newValue,
								} )
							}
						/>
					</PanelBody>
				</InspectorControls>
			) }
		</div>
	);
}

export default withSelect( ( select, ownProps ) => {
	const { postType } = ownProps.context;

	const taxonomies = select( 'core' ).getTaxonomies( {
		type: postType,
		per_page: -1,
	} );

	return {
		props: ownProps,
		taxonomies,
	};
} )( Edit );
