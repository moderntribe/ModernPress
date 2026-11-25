import { BlockControls, useBlockEditingMode } from '@wordpress/block-editor';
import { getBlockSupport, store as blocksStore } from '@wordpress/blocks';
import {
	Button,
	DropdownMenu,
	MenuGroup,
	MenuItem,
	ToolbarItem,
} from '@wordpress/components';
import { createHigherOrderComponent } from '@wordpress/compose';
import { dispatch } from '@wordpress/data';
import {
	useEffect,
	useRef,
	useState,
	isValidElement,
	forwardRef,
} from '@wordpress/element';
import { addFilter } from '@wordpress/hooks';
import { __, sprintf } from '@wordpress/i18n';
import * as icons from '@wordpress/icons';

const { layouts, include, exclude } = window.tribeCustomAlignments;
const slugs = layouts.map( ( layout ) => layout.slug );
const alignmentClasses = [
	'left',
	'center',
	'right',
	'wide',
	'full',
	...slugs,
];

const options = layouts.map( ( layout ) => {
	const icon = icons[ layout.icon ];
	if ( ! isValidElement( icon ) ) {
		console.error(
			`Custom alignment "${ layout.slug }" has an unknown WordPress icon "${ layout.icon }".`
		);
	}
	return { ...layout, icon: isValidElement( icon ) ? icon : icons.contents };
} );

/**
 * Find the custom widths a block may offer, respecting theme restrictions.
 *
 * @param {string} name Block type name.
 * @return {Object[]} Supported layout options.
 */
const supportedOptions = ( name ) => {
	const align = getBlockSupport( name, 'align', false );
	if (
		! align ||
		exclude.includes( name ) ||
		( include.length && ! include.includes( name ) )
	) {
		return [];
	}

	if ( align === true ) {
		return getBlockSupport( name, 'alignWide', true ) ? options : [];
	}

	if ( ! Array.isArray( align ) ) {
		return [];
	}

	return options.filter(
		( option ) =>
			align.includes( option.slug ) ||
			( name.startsWith( 'core/' ) && align.includes( 'wide' ) )
	);
};

/**
 * Extend the alignment schema and preserve paragraph text alignment.
 *
 * @param {Object} block Registered block settings.
 * @return {Object} Updated block settings.
 */
const extendAlignAttribute = ( block ) => {
	const align = block.attributes?.align;
	if ( ! getBlockSupport( block, 'align', false ) || ! align?.enum ) {
		return block;
	}

	let edit = block.edit;
	if ( block.name === 'core/paragraph' && edit ) {
		const ParagraphEdit = edit;
		edit = function CustomAlignmentParagraphEdit( props ) {
			const wasCustom = useRef( false );
			const current = props.attributes.align;
			if ( slugs.includes( current ) ) {
				wasCustom.current = true;
			} else if ( current ) {
				wasCustom.current = false;
			}

			// WordPress 7 migrates paragraph align values into text alignment.
			const hideAlign =
				slugs.includes( current ) || ( ! current && wasCustom.current );
			return (
				<ParagraphEdit
					{ ...props }
					attributes={
						hideAlign
							? { ...props.attributes, align: 'wide' }
							: props.attributes
					}
				/>
			);
		};
	}

	return {
		...block,
		edit,
		attributes: {
			...block.attributes,
			align: {
				...align,
				enum: [ ...new Set( [ ...align.enum, ...slugs ] ) ],
			},
		},
	};
};

/**
 * Find a block in either the editor document or its canvas iframe.
 *
 * @param {string} clientId Block client ID.
 * @return {HTMLElement|null|undefined} Block element, if present.
 */
const getBlockElement = ( clientId ) => {
	const id = `block-${ clientId }`;
	const canvas = document.querySelector( 'iframe[name="editor-canvas"]' );
	return (
		document.getElementById( id ) ||
		canvas?.contentDocument?.getElementById( id )
	);
};

/**
 * Sort widths by their measured size in the block's layout context.
 *
 * @param {Object[]}    available Supported layout options.
 * @param {HTMLElement} parent    Block's layout parent.
 * @return {Object[]} Options ordered from smallest to largest.
 */
const sortOptions = ( available, parent ) => {
	const probe = parent.ownerDocument.createElement( 'div' );
	probe.style.cssText =
		'position:relative;display:block;visibility:hidden;height:0;min-width:0;max-width:none;padding:0;border:0;margin:0;';
	parent.appendChild( probe );
	const measured = available.map( ( option ) => {
		probe.style.width = '';
		probe.style.width = option.width;
		if ( ! probe.style.width ) {
			console.error(
				`Custom alignment "${ option.slug }" has an invalid width "${ option.width }".`
			);
			return { option, width: Infinity };
		}
		return { option, width: probe.getBoundingClientRect().width };
	} );
	probe.remove();
	return measured
		.sort( ( a, b ) => a.width - b.width )
		.map( ( item ) => item.option );
};

/** Render the toolbar label and the active custom alignment. */
const CustomAlignmentToggle = forwardRef(
	( { text, 'data-active-alignment': activeAlignment, ...props }, ref ) => (
		<Button
			{ ...props }
			ref={ ref }
			className={ `${ props.className } has-text` }
		>
			<span className="tribe-custom-alignments__label">
				<span>{ text }</span>
				{ activeAlignment && (
					<span className="components-menu-item__info">
						{ activeAlignment }
					</span>
				) }
			</span>
		</Button>
	)
);

/** Add the custom alignment dropdown alongside native block controls. */
const withCustomAlignmentControl = createHigherOrderComponent(
	( BlockEdit ) => {
		return ( props ) => {
			const {
				name,
				clientId,
				attributes,
				setAttributes,
				__unstableParentLayout,
			} = props;
			const previousAlign = useRef( attributes.align );
			const editingMode = useBlockEditingMode();
			const [ isOpen, setIsOpen ] = useState( false );
			const [ ordered, setOrdered ] = useState( null );
			const available = supportedOptions( name );

			useEffect( () => {
				const previous = previousAlign.current;
				previousAlign.current = attributes.align;
				if (
					! previous ||
					previous === attributes.align ||
					! alignmentClasses.includes( previous ) ||
					! getBlockSupport( name, 'align', false ) ||
					! attributes.className
				) {
					return;
				}

				const classes = attributes.className.split( /\s+/ );
				if ( classes.includes( `align${ previous }` ) ) {
					setAttributes( {
						className:
							classes
								.filter(
									( className ) =>
										className !== `align${ previous }`
								)
								.join( ' ' ) || undefined,
					} );
				}
			}, [
				attributes.align,
				attributes.className,
				name,
				setAttributes,
			] );

			useEffect( () => {
				if ( ! isOpen ) {
					return;
				}
				const parent = getBlockElement( clientId )?.parentElement;
				if ( ! parent ) {
					console.error(
						'Cannot measure custom alignment widths in the editor.'
					);
					return;
				}
				const update = () =>
					setOrdered( {
						name,
						options: sortOptions(
							supportedOptions( name ),
							parent
						),
					} );
				update();
				const observer = new window.ResizeObserver( update );
				observer.observe( parent );
				const view = parent.ownerDocument.defaultView;
				view.addEventListener( 'resize', update );
				return () => {
					observer.disconnect();
					view.removeEventListener( 'resize', update );
				};
			}, [ clientId, isOpen, name ] );

			const layout = __unstableParentLayout;
			const isConstrained =
				layout?.type === 'constrained' &&
				( ! layout.alignments ||
					layout.alignments.includes( 'wide' ) ||
					layout.alignments.includes( 'full' ) );
			const showControl =
				available.length && isConstrained && editingMode === 'default';
			const selected = available.find(
				( option ) => option.slug === attributes.align
			);
			const controls = (
				ordered?.name === name ? ordered.options : available
			).map( ( option ) => ( {
				key: option.slug,
				title: option.name,
				info: sprintf(
					/* translators: %s: custom alignment width. */
					__( 'Max %s wide', 'tribe' ),
					option.width
				),
				icon: option.icon,
				isActive: attributes.align === option.slug,
				role: 'menuitemradio',
				onClick: () => setAttributes( { align: option.slug } ),
			} ) );

			return (
				<>
					<BlockEdit { ...props } />
					{ !! showControl && (
						<BlockControls group="block">
							<ToolbarItem className="tribe-custom-alignments__toggle">
								{ ( toolbarProps ) => (
									<DropdownMenu
										icon={
											selected?.icon || icons.contents
										}
										label={ __(
											'Custom Alignments',
											'tribe'
										) }
										text={ __(
											'Custom Alignments',
											'tribe'
										).replace( ' ', '\n' ) }
										toggleProps={ {
											...toolbarProps,
											as: CustomAlignmentToggle,
											'data-active-alignment':
												selected?.name,
											description: selected?.name,
										} }
										onToggle={ setIsOpen }
									>
										{ ( { onClose } ) => (
											<MenuGroup className="block-editor-block-alignment-control__menu-group">
												{ controls.map( ( control ) => (
													<MenuItem
														key={ control.key }
														icon={ control.icon }
														iconPosition="left"
														className={ `components-dropdown-menu__menu-item${ control.isActive ? ' is-active' : '' }` }
														isSelected={
															control.isActive
														}
														role={ control.role }
														info={ control.info }
														onClick={ () => {
															control.onClick();
															onClose();
														} }
													>
														{ control.title }
													</MenuItem>
												) ) }
											</MenuGroup>
										) }
									</DropdownMenu>
								) }
							</ToolbarItem>
						</BlockControls>
					) }
				</>
			);
		};
	},
	'withCustomAlignmentControl'
);

/** Apply the selected custom alignment class to the editor wrapper. */
const withCustomAlignmentEditorClass = createHigherOrderComponent(
	( BlockListBlock ) => {
		return ( props ) => (
			<BlockListBlock
				{ ...props }
				wrapperProps={ {
					...props.wrapperProps,
					className: [
						props.wrapperProps?.className,
						slugs.includes( props.attributes.align ) &&
							getBlockSupport( props.name, 'align', false ) &&
							`align${ props.attributes.align }`,
					]
						.filter( Boolean )
						.join( ' ' ),
				} }
			/>
		);
	},
	'withCustomAlignmentEditorClass'
);

/**
 * Add the custom alignment class to saved static block markup.
 *
 * @param {Object} props      Save element props.
 * @param {Object} block      Registered block settings.
 * @param {Object} attributes Block attributes.
 * @return {Object} Updated save element props.
 */
const addCustomAlignmentSaveClass = ( props, block, attributes ) => {
	const className = `align${ attributes.align }`;
	if (
		! slugs.includes( attributes.align ) ||
		! getBlockSupport( block, 'align', false ) ||
		( props.className || '' ).split( /\s+/ ).includes( className )
	) {
		return props;
	}

	return {
		...props,
		className: [ props.className, className ].filter( Boolean ).join( ' ' ),
	};
};

/** Register the custom alignment schema, editor controls, and save filters. */
const init = () => {
	addFilter(
		'blocks.registerBlockType',
		'tribe/custom-align-attribute',
		extendAlignAttribute
	);
	dispatch( blocksStore ).reapplyBlockTypeFilters();
	addFilter(
		'editor.BlockEdit',
		'tribe/custom-align-control',
		withCustomAlignmentControl
	);
	addFilter(
		'editor.BlockListBlock',
		'tribe/custom-align-editor-class',
		withCustomAlignmentEditorClass
	);
	addFilter(
		'blocks.getSaveContent.extraProps',
		'tribe/custom-align-save-class',
		addCustomAlignmentSaveClass
	);
};

export default init;
