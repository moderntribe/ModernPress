/**
 * Create WP Controls — add custom controls to core blocks.
 *
 * Handles toggle, number, and select controls; class application for all types;
 * and conditional visibility (showWhen).
 *
 * @see https://github.com/moderntribe/modernpress/tree/main/docs/create-wp-controls-script.md
 */

import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	SelectControl,
	ToggleControl,
	__experimentalInputControl as InputControl, // eslint-disable-line
} from '@wordpress/components';
import { getBlockType, registerBlockType } from '@wordpress/blocks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { cloneElement, Fragment, useEffect } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { cleanForSlug } from '@wordpress/url';

/** @type {Object[]} Registered settings objects (one per createWPControls call). */
const configs = [];

let filtersRegistered = false;

/**
 * Settings for a block type, if any config targets it.
 *
 * @param {string} blockName Block name (e.g. core/paragraph).
 * @return {Object|undefined} Matching settings object.
 */
const getSettingsForBlock = ( blockName ) =>
	configs.find( ( config ) => config.blocks.includes( blockName ) );

/** Default panel title when control has no panel (used for default and styles groups). */
const DEFAULT_PANEL_TITLE = __( 'Custom Block Settings', 'tribe' );

/** Groups that render controls inside a PanelBody; all others render controls directly. */
const GROUPS_WITH_PANEL = [ 'default', 'styles' ];

/**
 * Whether the control's current value should apply its class/style ("active").
 *
 * @param {Object} control - Control config.
 * @param {*}      value   - Current attribute value.
 * @return {boolean} True if the control value is active.
 */
const isControlValueActive = ( control, value ) => {
	if ( value === undefined ) {
		return false;
	}
	const defaultValue = control.defaultValue;
	switch ( control.type ) {
		case 'toggle':
			return value === true;
		case 'number':
			return value !== defaultValue && value !== '' && value !== null;
		case 'select':
			return (
				value !== '' &&
				value !== null &&
				value !== undefined &&
				value !== defaultValue
			);
		default:
			return value !== defaultValue;
	}
};

/**
 * Class name(s) this control contributes when its value is active.
 *
 * @param {Object} control - Control config.
 * @param {*}      value   - Current attribute value.
 * @return {string[]} Class names to apply.
 */
const getClassesForControl = ( control, value ) => {
	if ( ! control.applyClass || ! isControlValueActive( control, value ) ) {
		return [];
	}
	switch ( control.type ) {
		case 'toggle':
		case 'number':
			return [ control.applyClass ];
		case 'select':
			return [
				`${ control.applyClass }-${ cleanForSlug( String( value ) ) }`,
			];
		default:
			return [ control.applyClass ];
	}
};

/**
 * All managed class names that should be present for the current attributes.
 *
 * @param {Object[]} controls   - Control configs.
 * @param {Object}   attributes - Block attributes.
 * @return {string[]} Class names to apply.
 */
const getManagedClasses = ( controls, attributes ) => {
	return controls.reduce( ( acc, control ) => {
		if ( ! control.applyClass ) {
			return acc;
		}

		const value = attributes[ control.attribute ];

		return acc.concat( getClassesForControl( control, value ) );
	}, [] );
};

/**
 * Whether a class name is managed by any control (for stripping before re-adding).
 *
 * @param {string}   className - Single class name.
 * @param {Object[]} controls  - Control configs.
 * @return {boolean} True if this class is managed by a control.
 */
const isManagedClass = ( className, controls ) => {
	return controls.some( ( control ) => {
		if ( ! control.applyClass ) {
			return false;
		}

		if ( control.type === 'select' ) {
			return className.startsWith( control.applyClass + '-' );
		}

		return className === control.applyClass;
	} );
};

/**
 * Removes managed classes from a class string.
 *
 * @param {string}   className - Full class string.
 * @param {Object[]} controls  - Control configs.
 * @return {string} Class string with managed classes removed.
 */
const stripManagedClasses = ( className, controls ) => {
	if ( ! className || ! className.trim() ) {
		return '';
	}

	const kept = className
		.split( /\s+/ )
		.filter( ( name ) => name && ! isManagedClass( name, controls ) );

	return kept.join( ' ' ).trim();
};

/**
 * Builds final className: base with managed classes stripped, then current managed classes.
 *
 * @param {string}   baseClassName - Existing class string.
 * @param {Object[]} controls      - Control configs.
 * @param {Object}   attributes    - Block attributes.
 * @return {string} Final class string or empty.
 */
const buildClassName = ( baseClassName, controls, attributes ) => {
	const withoutManaged = stripManagedClasses( baseClassName, controls );
	const managed = getManagedClasses( controls, attributes );
	const combined = [ withoutManaged, ...managed ].filter( Boolean );

	return combined.join( ' ' ).trim() || undefined;
};

/**
 * Normalizes a single class source (string or array) into a space-delimited string.
 *
 * @param {string|string[]|undefined} value - Class string or array from block attributes.
 * @return {string} Normalized class string.
 */
const normalizeClassSource = ( value ) => {
	if ( ! value ) {
		return '';
	}

	if ( Array.isArray( value ) ) {
		return value.filter( Boolean ).join( ' ' );
	}

	return String( value ).trim();
};

/**
 * Merges class sources from save props and block attributes into one base string.
 *
 * @param {Object}          sources                      Class sources.
 * @param {string}          [sources.propsClassName]     className on save props / element.
 * @param {string}          [sources.attributeClassName] Block className attribute.
 * @param {string|string[]} [sources.attributeClasses]   Block classes attribute.
 * @return {string} Combined base class string (may be empty).
 */
const getBaseClassName = ( {
	propsClassName,
	attributeClassName,
	attributeClasses,
} = {} ) => {
	// The three sources may overlap (e.g. WP core's customClassName support
	// merges attributeClassName into propsClassName earlier in the
	// pipeline), so dedupe tokens across all three instead of concatenating
	// them.
	const tokens = [
		normalizeClassSource( propsClassName ),
		normalizeClassSource( attributeClassName ),
		normalizeClassSource( attributeClasses ),
	]
		.filter( Boolean )
		.join( ' ' )
		.split( /\s+/ )
		.filter( Boolean );

	return [ ...new Set( tokens ) ].join( ' ' ).trim();
};

/**
 * Applies inline styles from controls during save (via getBlockProps / extraProps).
 *
 * Classes are applied in applySaveElementClasses so apiVersion 3 blocks are covered.
 *
 * @param {Object} props      Block wrapper props.
 * @param {Object} block      Block definition (with name).
 * @param {Object} attributes Block attributes.
 * @return {Object} Original or updated props.
 */
const applyBlockProps = ( props, block, attributes ) => {
	const settings = getSettingsForBlock( block.name );
	if ( ! settings ) {
		return props;
	}

	const controls = settings.controls;

	controls.forEach( ( control ) => {
		if ( ! control.applyStyleProperty ) {
			return;
		}

		const value = attributes[ control.attribute ];

		if ( ! isControlValueActive( control, value ) ) {
			return;
		}

		props.style = {
			...props.style,
			[ control.applyStyleProperty ]: value,
		};
	} );

	return props;
};

/**
 * Applies managed classes to the saved block element (all API versions).
 *
 * core/paragraph is apiVersion 3; WordPress only runs extraProps on the save
 * element itself when apiVersion <= 1. This filter runs for every saved block.
 *
 * @param {Object} element    Saved element from the block's save function.
 * @param {Object} blockType  Block type definition.
 * @param {Object} attributes Block attributes.
 * @return {Object} Original or updated element.
 */
const applySaveElementClasses = ( element, blockType, attributes ) => {
	const settings = getSettingsForBlock( blockType.name );
	if ( ! settings || ! element?.props ) {
		return element;
	}

	const controls = settings.controls;
	const baseClassName = getBaseClassName( {
		propsClassName: element.props.className,
		attributeClassName: attributes.className,
		attributeClasses: attributes.classes,
	} );

	const className = buildClassName(
		baseClassName || undefined,
		controls,
		attributes
	);

	if ( className === element.props.className ) {
		return element;
	}

	return cloneElement( element, {
		...element.props,
		className,
	} );
};

/**
 * Re-registers core block types with custom attributes after block library loads.
 *
 * The registerBlockType filter does not run for blocks that were already
 * registered before this script executes.
 *
 * @param {Object} settings - Config for blocks, controls, and attributes.
 */
const mergeBlockTypeAttributes = ( settings ) => {
	settings.blocks.forEach( ( blockName ) => {
		const blockSettings = getBlockType( blockName );
		if ( ! blockSettings ) {
			return;
		}

		registerBlockType( blockName, {
			...blockSettings,
			attributes: {
				...blockSettings.attributes,
				...settings.attributes,
			},
		} );
	} );
};

/**
 * HOC (Higher Order Component) that assigns props/classes to the block in the editor (BlockListBlock).
 */
const applyEditorBlockProps = createHigherOrderComponent(
	( BlockListBlock ) => {
		return ( props ) => {
			const { name, attributes } = props;
			const settings = getSettingsForBlock( name );
			if ( ! settings ) {
				return <BlockListBlock { ...props } />;
			}

			const controls = settings.controls;
			const baseClassName = getBaseClassName( {
				attributeClassName: attributes.className,
				attributeClasses: attributes.classes,
			} );
			const className = buildClassName(
				baseClassName || undefined,
				controls,
				attributes
			);

			const styles = { style: {} };
			controls.forEach( ( control ) => {
				if ( ! control.applyStyleProperty ) {
					return;
				}

				const value = attributes[ control.attribute ];

				if ( ! isControlValueActive( control, value ) ) {
					return;
				}

				styles.style[ control.applyStyleProperty ] = value;
			} );

			return (
				<BlockListBlock
					{ ...props }
					className={ className ? className.split( ' ' ) : [] }
					wrapperProps={ styles }
				/>
			);
		};
	},
	'applyEditorBlockProps'
);

/**
 * Renders the appropriate WP control (Toggle, Number, Select) from config.
 *
 * @param {Object}   control       - Control config.
 * @param {Object}   attributes    - Block attributes.
 * @param {Function} setAttributes - Block setAttributes.
 * @return {*} Control component or null.
 */
const determineControlToRender = ( control, attributes, setAttributes ) => {
	switch ( control.type ) {
		case 'toggle':
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
		case 'number': {
			const numValue = attributes[ control.attribute ];
			return (
				<InputControl
					__next40pxDefaultSize
					key={ control.attribute }
					label={ control.label }
					help={ control.helpText }
					type="number"
					min={ 0 }
					value={
						numValue === undefined || numValue === null
							? ''
							: String( numValue )
					}
					onChange={ ( nextValue ) => {
						const parsed =
							nextValue === '' || nextValue === undefined
								? control.defaultValue
								: Number( nextValue );
						setAttributes( {
							[ control.attribute ]: parsed,
						} );
					} }
				/>
			);
		}
		case 'select':
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
		default:
			return null;
	}
};

/**
 * Whether the control should be visible (honors showWhen when defined).
 *
 * @param {Object} control    - Control config.
 * @param {Object} attributes - Block attributes.
 * @return {boolean} True if the control should be shown.
 */
const isControlVisible = ( control, attributes ) => {
	if ( typeof control.showWhen !== 'function' ) {
		return true;
	}

	return Boolean( control.showWhen( attributes ) );
};

/**
 * Controls that are visible for the current attributes.
 *
 * @param {Object[]} controls   - Control configs.
 * @param {Object}   attributes - Block attributes.
 * @return {Object[]} Visible controls.
 */
const getVisibleControls = ( controls, attributes ) => {
	return controls.filter( ( control ) =>
		isControlVisible( control, attributes )
	);
};

/**
 * Normalized group for a control ('settings' → 'default', undefined → 'default').
 *
 * @param {Object} control - Control config.
 * @return {string} Group name.
 */
const getControlGroup = ( control ) => {
	const group = control.group;

	return group === undefined || group === 'settings' ? 'default' : group;
};

/**
 * Panel title for a control (only used when group uses PanelBody). Defaults to "Custom Block Settings".
 *
 * @param {Object} control - Control config.
 * @return {string} Panel title.
 */
const getControlPanelTitle = ( control ) => {
	return control.panel !== undefined && control.panel !== ''
		? control.panel
		: DEFAULT_PANEL_TITLE;
};

/**
 * Buckets visible controls by (group, panel) for rendering. Groups in GROUPS_WITH_PANEL
 * are further split by panel title; other groups are one bucket per group with no panel.
 *
 * @param {Object[]} controls   - Control configs.
 * @param {Object}   attributes - Block attributes.
 * @return {Object[]} Array of { group, panelTitle?, controls }.
 */
const bucketControlsByGroupAndPanel = ( controls, attributes ) => {
	const visible = getVisibleControls( controls, attributes );
	if ( visible.length === 0 ) {
		return [];
	}

	const map = new Map();

	visible.forEach( ( control ) => {
		const group = getControlGroup( control );
		const usePanel = GROUPS_WITH_PANEL.includes( group );
		const key = usePanel
			? `${ group }:${ getControlPanelTitle( control ) }`
			: group;

		if ( ! map.has( key ) ) {
			map.set( key, {
				group,
				panelTitle: usePanel ? getControlPanelTitle( control ) : null,
				controls: [],
			} );
		}

		map.get( key ).controls.push( control );
	} );

	return Array.from( map.values() );
};

/**
 * HOC that adds custom controls to blocks. Controls can be placed in any InspectorControls
 * group (default, styles, color, dimensions, etc.). Default/stylesheet groups use a named
 * PanelBody; other groups render controls directly.
 */
const addBlockControls = createHigherOrderComponent( ( BlockEdit ) => {
	return ( props ) => {
		const { attributes, setAttributes, name, isSelected } = props;

		const settings = getSettingsForBlock( name );

		useEffect( () => {
			if ( ! settings ) {
				return;
			}

			const defaults = {};

			settings.controls.forEach( ( control ) => {
				if ( attributes[ control.attribute ] === undefined ) {
					defaults[ control.attribute ] = control.defaultValue;
				}
			} );

			if ( Object.keys( defaults ).length > 0 ) {
				setAttributes( defaults );
			}
		}, [ attributes, setAttributes, settings ] );

		if ( ! settings ) {
			return <BlockEdit { ...props } />;
		}

		const buckets = bucketControlsByGroupAndPanel(
			settings.controls,
			attributes
		);

		if ( buckets.length === 0 || ! isSelected ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<Fragment>
				<BlockEdit { ...props } />
				{ buckets.map( ( bucket, index ) => {
					const groupProp =
						bucket.group === 'default' ? undefined : bucket.group;
					const content = bucket.panelTitle ? (
						<PanelBody
							key={ bucket.panelTitle }
							title={ bucket.panelTitle }
						>
							{ bucket.controls.map( ( control ) =>
								determineControlToRender(
									control,
									attributes,
									setAttributes
								)
							) }
						</PanelBody>
					) : (
						<div
							className="create-wp-controls__group-control"
							key={ `direct-${ index }` }
							style={ { gridColumn: '1 / -1' } }
						>
							{ bucket.controls.map( ( control ) =>
								determineControlToRender(
									control,
									attributes,
									setAttributes
								)
							) }
						</div>
					);

					return (
						<InspectorControls
							key={ `${ bucket.group }-${ index }` }
							group={ groupProp }
						>
							{ content }
						</InspectorControls>
					);
				} ) }
			</Fragment>
		);
	};
}, 'addBlockControls' );

/**
 * Merges declared attributes into existing block settings.
 *
 * @param {Object} settings - Block type settings.
 * @param {string} name     - Block name.
 * @return {Object} Existing or updated settings.
 */
const addBlockAttributes = ( settings, name ) => {
	const config = getSettingsForBlock( name );
	if ( ! config ) {
		return settings;
	}

	if ( settings?.attributes !== undefined ) {
		settings.attributes = {
			...settings.attributes,
			...config.attributes,
		};
	}

	return settings;
};

/**
 * Initializes the utility with a settings object. No-op if settings is null.
 *
 * @param {Object|null} settings - Config for blocks, controls, and attributes.
 */
const init = ( settings = null ) => {
	if ( ! settings ) {
		return;
	}

	configs.push( settings );
	mergeBlockTypeAttributes( settings );

	if ( filtersRegistered ) {
		return;
	}

	filtersRegistered = true;

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

	addFilter(
		'blocks.getSaveElement',
		'tribe/apply-save-element-classes',
		applySaveElementClasses
	);
};

export default init;
