/**
 * @module location-map/edit
 *
 * @description Block editor controls for the location map block.
 */

import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { ServerSideRender } from '@wordpress/server-side-render';
import PostSearchField from 'components/PostSearchField';

import './editor.pcss';

const LOCATION_POST_TYPE = 'location';

/** @type {import('@wordpress/components').SelectControlOption[]} */
const MAP_HEIGHT_MODE_OPTIONS = [
	{
		label: __( 'Fixed (px)', 'tribe' ),
		value: 'fixed',
	},
	{
		label: __( 'Viewport', 'tribe' ),
		value: 'viewport',
	},
];

/** @type {import('@wordpress/components').SelectControlOption[]} */
const LOCATION_SOURCE_OPTIONS = [
	{
		label: __( 'Manual selection', 'tribe' ),
		value: 'manual',
	},
	{
		label: __( 'All locations', 'tribe' ),
		value: 'all',
	},
	{
		label: __( 'Custom endpoint', 'tribe' ),
		value: 'endpoint',
	},
];

/**
 * Location Map block editor UI.
 *
 * @param {Object}   props               Component props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {boolean}  props.isSelected    Whether the block is selected.
 * @param {Function} props.setAttributes Updates block attributes.
 * @return {import('react').JSX.Element} Block editor markup.
 */
export default function Edit( { attributes, isSelected, setAttributes } ) {
	const blockProps = useBlockProps();
	const {
		locationSource,
		chosenLocations,
		endpointUrl,
		showSidebar,
		showSearch,
		showLocationList,
		searchRadius,
		defaultLat,
		defaultLng,
		defaultZoom,
		fitBounds,
		clusterMarkers,
		mapHeightMode,
		mapHeight,
	} = attributes;

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block="tribe/location-map"
				attributes={ attributes }
			/>
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Location Source', 'tribe' ) }>
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Source', 'tribe' ) }
							value={ locationSource }
							options={ LOCATION_SOURCE_OPTIONS }
							onChange={ ( value ) =>
								setAttributes( { locationSource: value } )
							}
						/>
						{ locationSource === 'manual' && (
							<PostSearchField
								label={ __( 'Locations', 'tribe' ) }
								placeholder={ __(
									'Search for locations…',
									'tribe'
								) }
								value={ chosenLocations }
								onChange={ ( locations ) =>
									setAttributes( {
										chosenLocations: locations,
									} )
								}
								includedTypes={ [ LOCATION_POST_TYPE ] }
							/>
						) }
						{ locationSource === 'endpoint' && (
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __(
									'Locations endpoint URL',
									'tribe'
								) }
								help={ __(
									'Must return JSON with a locations array.',
									'tribe'
								) }
								value={ endpointUrl }
								onChange={ ( value ) =>
									setAttributes( { endpointUrl: value } )
								}
							/>
						) }
					</PanelBody>
					<PanelBody
						title={ __( 'Layout', 'tribe' ) }
						initialOpen={ false }
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Show sidebar', 'tribe' ) }
							checked={ showSidebar }
							onChange={ ( value ) => {
								const next = { showSidebar: value };

								if ( ! value ) {
									next.showSearch = false;
									next.showLocationList = false;
								}

								setAttributes( next );
							} }
						/>
						{ showSidebar && (
							<>
								<ToggleControl
									__nextHasNoMarginBottom
									label={ __( 'Show search', 'tribe' ) }
									checked={ showSearch }
									onChange={ ( value ) => {
										const next = { showSearch: value };

										if ( value && ! showLocationList ) {
											next.showLocationList = true;
										}

										setAttributes( next );
									} }
								/>
								<ToggleControl
									__nextHasNoMarginBottom
									label={ __(
										'Show location list',
										'tribe'
									) }
									checked={ showLocationList }
									disabled={ showSearch }
									help={
										showSearch
											? __(
													'The location list is required when search is enabled.',
													'tribe'
											  )
											: undefined
									}
									onChange={ ( value ) =>
										setAttributes( {
											showLocationList: value,
										} )
									}
								/>
							</>
						) }
						<SelectControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Map height', 'tribe' ) }
							value={ mapHeightMode ?? 'fixed' }
							options={ MAP_HEIGHT_MODE_OPTIONS }
							onChange={ ( value ) =>
								setAttributes( { mapHeightMode: value } )
							}
						/>
						{ ( mapHeightMode ?? 'fixed' ) === 'fixed' && (
							<RangeControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Map height (px)', 'tribe' ) }
								min={ 300 }
								max={ 900 }
								value={ mapHeight }
								onChange={ ( value ) =>
									setAttributes( { mapHeight: value } )
								}
							/>
						) }
					</PanelBody>
					<PanelBody
						title={ __( 'Map Settings', 'tribe' ) }
						initialOpen={ false }
					>
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Search radius (miles)', 'tribe' ) }
							min={ 5 }
							max={ 100 }
							value={ searchRadius }
							onChange={ ( value ) =>
								setAttributes( { searchRadius: value } )
							}
						/>
						<RangeControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Default zoom', 'tribe' ) }
							min={ 3 }
							max={ 18 }
							value={ defaultZoom }
							onChange={ ( value ) =>
								setAttributes( { defaultZoom: value } )
							}
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Default latitude', 'tribe' ) }
							type="number"
							value={ String( defaultLat ) }
							onChange={ ( value ) => {
								const parsed = parseFloat( value );

								if ( ! Number.isNaN( parsed ) ) {
									setAttributes( { defaultLat: parsed } );
								}
							} }
						/>
						<TextControl
							__next40pxDefaultSize
							__nextHasNoMarginBottom
							label={ __( 'Default longitude', 'tribe' ) }
							type="number"
							value={ String( defaultLng ) }
							onChange={ ( value ) => {
								const parsed = parseFloat( value );

								if ( ! Number.isNaN( parsed ) ) {
									setAttributes( { defaultLng: parsed } );
								}
							} }
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Fit bounds to markers', 'tribe' ) }
							checked={ fitBounds }
							onChange={ ( value ) =>
								setAttributes( { fitBounds: value } )
							}
						/>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Cluster markers', 'tribe' ) }
							checked={ clusterMarkers }
							onChange={ ( value ) =>
								setAttributes( { clusterMarkers: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>
			) }
		</div>
	);
}
