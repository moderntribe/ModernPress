import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { useDebouncedInput } from '@wordpress/compose';
import { FormTokenField, Spinner } from '@wordpress/components';
import { store as coreDataStore } from '@wordpress/core-data';
import { __ } from '@wordpress/i18n';

const DEFAULT_EXCLUDED_TYPES = [
	'attachment',
	'wp_block',
	'wp_template',
	'wp_template_part',
	'wp_navigation',
];

/**
 * A reusable post search field that queries posts on-demand as the user types,
 * rather than preloading all posts (which is limited to 100 per request).
 *
 * @param {Object}   props
 * @param {Array}    props.value          - Currently selected posts as [{id, value}] objects.
 * @param {Function} props.onChange       - Callback with updated selected posts array.
 * @param {string}   props.label          - Field label.
 * @param {string}   props.placeholder    - Input placeholder text.
 * @param {Array}    props.excludedTypes  - Post type slugs to exclude from search.
 * @param {Array}    props.includedTypes  - Optional allowlist of post type slugs.
 * @param {Array}    props.excludeIds     - Post IDs to exclude from results.
 * @param {number}   props.perPage        - Number of results per post type per query.
 * @param {number}   props.maxSuggestions - Maximum suggestions shown in dropdown.
 */
export default function PostSearchField( {
	value = [],
	onChange,
	label = __( 'Search Posts', 'tribe' ),
	placeholder = __( 'Start typing to search…', 'tribe' ),
	excludedTypes = DEFAULT_EXCLUDED_TYPES,
	includedTypes = [],
	excludeIds = [],
	perPage = 20,
	maxSuggestions = 20,
} ) {
	const [ , setInput, debouncedInput ] = useDebouncedInput( '' );

	// Create a set of excluded types for quick lookup
	const excludedSet = useMemo(
		() => new Set( excludedTypes ),
		[ excludedTypes ]
	);

	// Filter out null and undefined IDs to avoid API errors
	const validExcludeIds = useMemo(
		() => excludeIds.filter( ( id ) => id !== null && id !== undefined ),
		[ excludeIds ]
	);

	const includedSet = useMemo(
		() => ( includedTypes.length > 0 ? new Set( includedTypes ) : null ),
		[ includedTypes ]
	);

	// Get all post types that are viewable and not excluded
	const selectableTypes = useSelect(
		( select ) => {
			const allPostTypes =
				select( coreDataStore ).getPostTypes( { per_page: -1 } ) ?? [];

			// "viewable" is a property of the post type object that is true if the post type has `publicly_queryable` or `public` set to true and has a rest_base.
			return allPostTypes.filter(
				( type ) =>
					type.viewable === true &&
					type.rest_base &&
					! excludedSet.has( type.slug ) &&
					( ! includedSet || includedSet.has( type.slug ) )
			);
		},
		[ excludedSet, includedSet ]
	);

	// Query posts on-demand as the user types
	const { suggestions, isResolving } = useSelect(
		( select ) => {
			if ( ! debouncedInput || debouncedInput.length < 2 ) {
				return { suggestions: [], isResolving: false };
			}

			const results = [];
			let resolving = false;

			for ( const type of selectableTypes ) {
				const query = {
					per_page: perPage,
					search: debouncedInput,
					exclude: validExcludeIds,
					orderby: 'relevance',
				};
				const selectorArgs = [ 'postType', type.slug, query ];

				const records =
					select( coreDataStore ).getEntityRecords(
						...selectorArgs
					) ?? [];
				const hasResolved = select(
					coreDataStore
				).hasFinishedResolution( 'getEntityRecords', selectorArgs );

				if ( ! hasResolved ) {
					resolving = true;
				}

				for ( const post of records ) {
					results.push( {
						id: post.id,
						pickerLabel: `${ post.title.rendered } (${ type.labels.singular_name })`,
					} );
				}
			}

			return { suggestions: results, isResolving: resolving };
		},
		[ debouncedInput, selectableTypes, validExcludeIds, perPage ]
	);

	// Map the suggestions to their picker labels
	const suggestionLabels = suggestions.map( ( s ) => s.pickerLabel );

	// Map the token values to their values or picker labels
	const tokenValues = value.map( ( item ) => {
		if ( typeof item === 'string' ) {
			return item;
		}
		return item.value || item.pickerLabel || `Post #${ item.id }`;
	} );

	// Handle the change of the token values
	const handleChange = ( tokens ) => {
		const newValue = tokens
			.map( ( token ) => {
				if ( typeof token !== 'string' ) {
					return token;
				}

				const existing = value.find(
					( v ) => ( v.value || v.pickerLabel ) === token
				);
				if ( existing ) {
					return existing;
				}

				const found = suggestions.find(
					( s ) => s.pickerLabel === token
				);
				if ( ! found ) {
					return false;
				}

				return {
					value: found.pickerLabel,
					id: found.id,
				};
			} )
			.filter( Boolean );

		onChange( newValue );
	};

	return (
		<div className="post-search-field" style={ { marginBottom: '16px' } }>
			<FormTokenField
				__next40pxDefaultSize
				__nextHasNoMarginBottom
				__experimentalShowHowTo={ false }
				label={ label }
				placeholder={ placeholder }
				value={ tokenValues }
				suggestions={ suggestionLabels }
				maxSuggestions={ maxSuggestions }
				onChange={ handleChange }
				onInputChange={ setInput }
			/>
			{ isResolving && (
				<div
					style={ {
						display: 'flex',
						alignItems: 'center',
						gap: '8px',
						marginTop: '4px',
					} }
				>
					<Spinner />
					<span className="components-base-control__help">
						{ __( 'Searching…', 'tribe' ) }
					</span>
				</div>
			) }
		</div>
	);
}
