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
import { createHigherOrderComponent } from '@wordpress/compose';
import { Fragment } from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { cleanForSlug } from '@wordpress/url';

const state = {
	settings: {},
};

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
 * Applies conditional props (classes, styles) to core blocks on save.
 *
 * @param {Object} props      Block wrapper props.
 * @param {Object} block      Block definition (with name).
 * @param {Object} attributes Block attributes.
 * @return {Object} Original or updated props.
 */
const applyBlockProps = ( props, block, attributes ) => {
	if ( ! state.settings.blocks.includes( block.name ) ) {
		return props;
	}

	const controls = state.settings.controls;

	// Replace managed classes so add/remove stays consistent.
	if ( props.className !== undefined ) {
		props.className = buildClassName(
			props.className,
			controls,
			attributes
		);
	}

	// Add styles only when the control value is active.
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
 * HOC (Higher Order Component) that assigns props/classes to the block in the editor (BlockListBlock).
 */
const applyEditorBlockProps = createHigherOrderComponent(
	( BlockListBlock ) => {
		return ( props ) => {
			const { name, attributes } = props;
			const controls = state.settings.controls;

			if ( ! state.settings.blocks.includes( name ) ) {
				return <BlockListBlock { ...props } />;
			}

			const baseClasses = attributes.classes
				? attributes.classes.split( ' ' ).filter( Boolean )
				: [];
			const baseClassName = baseClasses.join( ' ' );
			const className = buildClassName(
				baseClassName,
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

		if ( ! state.settings.blocks.includes( name ) ) {
			return <BlockEdit { ...props } />;
		}

		state.settings.controls.forEach( ( control ) => {
			if ( attributes[ control.attribute ] === undefined ) {
				attributes[ control.attribute ] = control.defaultValue;
			}
		} );

		const buckets = bucketControlsByGroupAndPanel(
			state.settings.controls,
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
 * Initializes the utility with a settings object. No-op if settings is null.
 *
 * @param {Object|null} settings - Config for blocks, controls, and attributes.
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
