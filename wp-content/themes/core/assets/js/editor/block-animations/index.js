/**
 * @module block-animation
 *
 * @description handles setting up animation settings for blocks
 */

import { InspectorControls } from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	SelectControl,
	ToggleControl,
} from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import animationSettings from './settings';

/**
 * @function applyAnimationProps
 *
 * @description updates props on the block with new animation settings
 *
 * @param {Object} props
 * @param {Object} block
 * @param {Object} attributes
 *
 * @return {Object} updated props object
 */
const applyAnimationProps = ( props, block, attributes ) => {
	// return default props if block isn't in the includes array
	if (
		animationSettings.includes.length > 0 &&
		! animationSettings.includes.includes( block.name )
	) {
		return props;
	}

	// return default props if block is in the excludes array
	if (
		animationSettings.excludes.length > 0 &&
		animationSettings.excludes.includes( block.name )
	) {
		return props;
	}

	const {
		animationType,
		animationDirection,
		animationDuration,
		animationDelay,
		animationMobileDisableDelay,
		animationEasing,
		animationTrigger,
		animationPosition,
	} = attributes;

	if ( animationType === undefined || animationType === 'none' ) {
		return props;
	}

	if ( props.className === undefined ) {
		props.className = '';
	}

	props.className = `${
		props.className !== '' ? props.className + ' ' : ''
	} is-animated-on-scroll-${ animationPosition } tribe-animation-type-${ animationType } tribe-animation-direction-${ animationDirection }`;

	if ( animationDuration !== undefined && animationDuration ) {
		props.style = {
			...props.style,
			'--tribe-animation-speed': animationDuration,
			'--tribe-animation-offset':
				animationSettings.offset[ animationDuration ],
		};
	}

	if ( animationDelay !== undefined && animationDelay ) {
		props.style = {
			...props.style,
			'--tribe-animation-delay': animationDelay,
		};
	}

	if (
		animationMobileDisableDelay !== undefined &&
		animationMobileDisableDelay
	) {
		props.className = `${ props.className } tribe-animation-mobile-disable-delay`;
	}

	if ( animationEasing !== undefined && animationEasing ) {
		props.style = {
			...props.style,
			'--tribe-animation-easing': animationEasing,
		};
	}

	if ( animationTrigger !== undefined && animationTrigger ) {
		props.className = `${ props.className } tribe-animate-multiple`;
	}

	return props;
};

/**
 * @function animationControls
 *
 * @description creates component that overrides the edit functionality of the block with new animation controls
 */
const animationControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { attributes, setAttributes, isSelected, name } = props;

		// return default Edit function if block isn't in the includes array
		if (
			animationSettings.includes.length > 0 &&
			! animationSettings.includes.includes( name )
		) {
			return <BlockEdit { ...props } />;
		}

		// return default Edit function if block is in the excludes array
		if (
			animationSettings.excludes.length > 0 &&
			animationSettings.excludes.includes( name )
		) {
			return <BlockEdit { ...props } />;
		}

		const {
			animationType,
			animationDirection,
			showAdvancedControls,
			animationDuration,
			animationDelay,
			animationMobileDisableDelay,
			animationEasing,
			animationTrigger,
			animationPosition,
		} = attributes;

		const blockStyles = { ...props.style };

		if ( animationType !== undefined && animationType !== 'none' ) {
			// set block styles for animation duration
			if ( animationDuration !== undefined && animationDuration ) {
				blockStyles[ '--tribe-animation-speed' ] = animationDuration;

				blockStyles[ '--tribe-animation-offset' ] =
					animationSettings.offset[ animationDuration ];
			}

			// set block styles for animation delay
			if ( animationDelay !== undefined && animationDelay ) {
				blockStyles[ '--tribe-animation-delay' ] = animationDelay;
			}

			// set block styles for animation easing
			if ( animationEasing !== undefined && animationEasing ) {
				blockStyles[ '--tribe-animation-easing' ] = animationEasing;
			}
		}

		const blockProps = {
			...props,
			style: blockStyles,
		};

		return (
			<Fragment>
				<BlockEdit { ...blockProps } />
				{ isSelected && (
					<InspectorControls>
						<PanelBody
							title={ __( 'Block Animations', 'tribe' ) }
							initialOpen={ false }
						>
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Animation Type', 'tribe' ) }
								value={ animationType }
								help={ __(
									'Animation Type is the type of animation that should run.',
									'tribe'
								) }
								onChange={ ( newValue ) => {
									setAttributes( {
										animationType: newValue,
									} );
								} }
								options={ animationSettings.type }
							/>
							{ animationType === undefined ||
								( animationType !== 'none' && (
									<>
										<SelectControl
											__next40pxDefaultSize
											__nextHasNoMarginBottom
											label={ __(
												'Animation Direction',
												'tribe'
											) }
											value={ animationDirection }
											help={ __(
												'Animation direction is the direction you want the animation to run in.',
												'tribe'
											) }
											onChange={ ( newValue ) => {
												setAttributes( {
													animationDirection:
														newValue,
												} );
											} }
											options={
												animationSettings.direction[
													animationType
												]
											}
										/>
										<Button
											__next40pxDefaultSize
											text={
												showAdvancedControls
													? __(
															'Hide Advanced Controls',
															'tribe'
													  )
													: __(
															'Show Advanced Controls',
															'tribe'
													  )
											}
											variant="secondary"
											icon={
												showAdvancedControls
													? 'arrow-up-alt2'
													: 'arrow-down-alt2'
											}
											onClick={ () => {
												setAttributes( {
													showAdvancedControls:
														! showAdvancedControls,
												} );
											} }
											style={ { width: '100%' } }
										/>
										{ showAdvancedControls && (
											<div
												style={ { paddingTop: '16px' } }
											>
												<SelectControl
													__next40pxDefaultSize
													__nextHasNoMarginBottom
													label={ __(
														'Animation Duration',
														'tribe'
													) }
													value={ animationDuration }
													help={ __(
														'Animation duration is the speed at which the animation should run.'
													) }
													onChange={ ( newValue ) =>
														setAttributes( {
															animationDuration:
																newValue,
														} )
													}
													options={
														animationSettings.duration
													}
												/>
												<SelectControl
													__next40pxDefaultSize
													__nextHasNoMarginBottom
													label={ __(
														'Animation Delay',
														'tribe'
													) }
													value={ animationDelay }
													help={ __(
														'Animation delay adds extra time before the animation starts.',
														'tribe'
													) }
													onChange={ ( newValue ) =>
														setAttributes( {
															animationDelay:
																newValue,
														} )
													}
													options={
														animationSettings.delay
													}
												/>
												<ToggleControl
													__nextHasNoMarginBottom
													label={ __(
														'Animation delays should be disabled on mobile.',
														'tribe'
													) }
													help={ __(
														"Default functionality will not disable animation delays on mobile. This feature is useful for animations that are delayed on desktop, but shouldn't be on mobile.",
														'tribe'
													) }
													checked={
														!! animationMobileDisableDelay
													}
													onChange={ ( newValue ) =>
														setAttributes( {
															animationMobileDisableDelay:
																newValue,
														} )
													}
												/>
												<SelectControl
													__next40pxDefaultSize
													__nextHasNoMarginBottom
													label={ __(
														'Animation Easing',
														'tribe'
													) }
													value={ animationEasing }
													help={ __(
														'Animation easing determines what easing function the animation should use.',
														'tribe'
													) }
													onChange={ ( newValue ) =>
														setAttributes( {
															animationEasing:
																newValue,
														} )
													}
													options={
														animationSettings.easing
													}
												/>
												<ToggleControl
													__nextHasNoMarginBottom
													label={ __(
														'Animation should trigger every time the element is in the viewport',
														'tribe'
													) }
													help={ __(
														'Default functionality is to trigger the animation once.',
														'tribe'
													) }
													checked={
														!! animationTrigger
													}
													onChange={ ( newValue ) =>
														setAttributes( {
															animationTrigger:
																newValue,
														} )
													}
												/>
												<SelectControl
													__next40pxDefaultSize
													__nextHasNoMarginBottom
													label={ __(
														'Animation Trigger Position',
														'tribe'
													) }
													value={ animationPosition }
													help={ __(
														'Animation trigger position determines how much of the element should be in the viewport before the animation triggers.',
														'tribe'
													) }
													onChange={ ( newValue ) =>
														setAttributes( {
															animationPosition:
																newValue,
														} )
													}
													options={
														animationSettings.position
													}
												/>
											</div>
										) }
									</>
								) ) }
						</PanelBody>
					</InspectorControls>
				) }
			</Fragment>
		);
	};
}, 'animationControls' );

/**
 * @function addAnimationAttributes
 *
 * @description add new attributes to blocks for animation settings
 *
 * @param {Object} settings
 * @param {string} name
 *
 * @return {Object} returns updates settings object
 */
const addAnimationAttributes = ( settings, name ) => {
	// return default settings if block isn't in the includes array
	if (
		animationSettings.includes.length > 0 &&
		! animationSettings.includes.includes( name )
	) {
		return settings;
	}

	// return default settings if block is in the excludes array
	if (
		animationSettings.excludes.length > 0 &&
		animationSettings.excludes.includes( name )
	) {
		return settings;
	}

	if ( settings?.attributes !== undefined ) {
		settings.attributes = {
			...settings.attributes,
			...animationSettings.attributes,
		};
	}

	return settings;
};

/**
 * @function init
 *
 * @description initializes this modules functions
 */
const init = () => {
	addFilter(
		'blocks.registerBlockType',
		'tribe/add-animation-options',
		addAnimationAttributes
	);

	addFilter(
		'editor.BlockEdit',
		'tribe/animation-advanced-control',
		animationControls
	);

	addFilter(
		'blocks.getSaveContent.extraProps',
		'tribe/apply-animation-classes',
		applyAnimationProps
	);
};

export default init;
