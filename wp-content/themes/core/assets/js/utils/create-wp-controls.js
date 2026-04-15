/**
 * @module create-wp-controls
 *
 * @description handles creating WP controls given an object of settings
 *
 * @see https://github.com/moderntribe/modernpress/tree/main/docs/create-wp-controls-script.md
 */

import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	__experimentalNumberControl as NumberControl, // eslint-disable-line
} from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';

const state = {
	settings: {},
};

/**
 * @function applyBlockProps
 *
 * @description applys additional conditional props to core blocks
 *
 * @param {*} props
 * @param {*} block
 * @param {*} attributes
 *
 * @return {*} return original or updated props
 */
const applyBlockProps = ( props, block, attributes ) => {
	// return default props if block isn't in the includes array
	if ( ! state.settings.blocks.includes( block.name ) ) {
		return props;
	}

	state.settings.controls.forEach( ( control ) => {
		if ( attributes[ control.attribute ] === undefined ) {
			return props;
		}

		// determine if we should add a class name to the block
		if (
			Object.keys( control ).includes( 'applyClass' ) &&
			props.className !== undefined &&
			! props.className.includes( control.applyClass ) &&
			attributes[ control.attribute ] !== undefined &&
			( ( control.type === 'toggle' &&
				attributes[ control.attribute ] ) ||
				control.type !== 'toggle' )
		) {
			props.className = `${ props.className } ${ control.applyClass }`;
		}

		// determine if we should add style properties to the block
		// assumes we only have one style property to add and assigns it to the value of the control created
		if (
			Object.keys( control ).includes( 'applyStyleProperty' ) &&
			attributes[ control.attribute ] !== undefined &&
			( ( control.type === 'toggle' &&
				attributes[ control.attribute ] ) ||
				control.type !== 'toggle' )
		) {
			props.style = {
				...props.style,
				[ control.applyStyleProperty ]: attributes[ control.attribute ],
			};
		}
	} );

	return props;
};

/**
 * @function applyEditorBlockProps
 *
 * @description assigns new props / classes to the block
 */
const applyEditorBlockProps = createHigherOrderComponent(
	( BlockListBlock ) => {
		return ( props ) => {
			const { name, attributes } = props;
			const classes = attributes.classes
				? attributes.classes.split( ' ' )
				: [];
			const styles = { style: {} };

			// return default BlockListBlock if we're dealing with an unsupported block
			if ( ! state.settings.blocks.includes( name ) ) {
				return <BlockListBlock { ...props } />;
			}

			// loop through controls to assign classes & styles if necessary
			state.settings.controls.forEach( ( control ) => {
				if (
					Object.keys( control ).includes( 'applyClass' ) &&
					! classes.includes( control.applyClass ) &&
					attributes[ control.attribute ] !== undefined &&
					( ( control.type === 'toggle' &&
						attributes[ control.attribute ] ) ||
						control.type !== 'toggle' )
				) {
					classes.push( control.applyClass );
				}

				// styles get added to the `wrapperProps` attribute on the BlockListBlock
				if (
					Object.keys( control ).includes( 'applyStyleProperty' ) &&
					attributes[ control.attribute ] !== undefined &&
					( ( control.type === 'toggle' &&
						attributes[ control.attribute ] ) ||
						control.type !== 'toggle' )
				) {
					styles.style = {
						[ control.applyStyleProperty ]:
							attributes[ control.attribute ],
					};
				}
			} );

			return (
				<BlockListBlock
					{ ...props }
					className={ classes }
					wrapperProps={ styles }
				/>
			);
		};
	}
);

/**
 * @function determineControlToRender
 *
 * @description based on provided settings, choose a type of WP control to render; currently supports ToggleControl & NumberControl.
 *
 * @param {*} control
 * @param {*} attributes
 * @param {*} setAttributes
 *
 * @return {*} returns WP React control component
 */
const determineControlToRender = ( control, attributes, setAttributes ) => {
	if ( control.type === 'toggle' ) {
		return (
			<ToggleControl
				__nextHasNoMarginBottom={ true }
				key={ control.attribute }
				label={ control.label }
				checked={ attributes[ control.attribute ] }
				help={ control.helpText }
				onChange={ () => {
					setAttributes( {
						[ control.attribute ]:
							! attributes[ control.attribute ],
					} );
				} }
			/>
		);
	} else if ( control.type === 'number' ) {
		return (
			<NumberControl
				key={ control.attribute }
				label={ control.label }
				value={ attributes[ control.attribute ] }
				help={ control.helpText }
				onChange={ ( value ) => {
					setAttributes( {
						[ control.attribute ]: value,
					} );
				} }
				min={ 0 }
				isShiftStepEnabled={ false }
			/>
		);
	} else if ( control.type === 'select' ) {
		return (
			<SelectControl
				key={ control.attribute }
				label={ control.label }
				value={ attributes[ control.attribute ] }
				help={ control.helpText }
				options={ control.selectOptions }
				onChange={ ( value ) => {
					setAttributes( {
						[ control.attribute ]: value,
					} );
				} }
			/>
		);
	}
};

/**
 * @function addBlockControls
 *
 * @description based on provided settings, adds new controls to existing blocks
 */
const addBlockControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { attributes, setAttributes, name, isSelected } = props;

		// return default Edit function if block doesn't match blocks we want to target
		if ( ! state.settings.blocks.includes( name ) ) {
			return <BlockEdit { ...props } />;
		}

		// set default attributes if not set
		state.settings.controls.forEach( ( control ) => {
			if ( attributes[ control.attribute ] === undefined ) {
				attributes[ control.attribute ] = control.defaultValue;
			}
		} );

		return (
			<Fragment>
				<BlockEdit { ...props } />
				{ isSelected && (
					<InspectorControls>
						<PanelBody
							title={ __( 'Custom Block Settings', 'tribe' ) }
						>
							{ state.settings.controls.map( ( control ) =>
								determineControlToRender(
									control,
									attributes,
									setAttributes
								)
							) }
						</PanelBody>
					</InspectorControls>
				) }
			</Fragment>
		);
	};
}, 'addBlockControls' );

/**
 * @function addBlockAttributes
 *
 * @description adds new attributes to existing blocks
 *
 * @param {*} settings
 * @param {*} name
 *
 * @return {*} existing or updated settings
 */
const addBlockAttributes = ( settings, name ) => {
	if ( ! state.settings.blocks.includes( name ) ) {
		return settings;
	}

	if ( settings?.attributes !== undefined ) {
		settings.attributes = {
			...settings.attributes,
			...state.settings.attributes,
		};
	}

	return settings;
};

/**
 * @function init
 *
 * @description assumes settings object is provided; if not, module does nothing
 *
 * @param {*} settings
 */
const init = ( settings = null ) => {
	if ( ! settings ) {
		return;
	}

	state.settings = settings;

	addFilter(
		'blocks.registerBlockType',
		'tribe/add-block-attributes',
		addBlockAttributes
	);

	addFilter(
		'editor.BlockEdit',
		'tribe/add-block-controls',
		addBlockControls
	);

	addFilter(
		'editor.BlockListBlock',
		'tribe/add-editor-block-props',
		applyEditorBlockProps
	);

	addFilter(
		'blocks.getSaveContent.extraProps',
		'tribe/apply-block-props',
		applyBlockProps
	);
};

export default init;
