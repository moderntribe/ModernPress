import { __ } from '@wordpress/i18n';
import { RawHTML, useMemo, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import {
	Button,
	Flex,
	RangeControl,
	SelectControl,
	Spinner,
	TabPanel,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import DynamicColorPicker from 'components/DynamicColorPicker';

/**
 * Map saved `icon-ai-sparkle` keys to registered `tribe/ai-sparkle` names.
 *
 * @param {string} name Saved icon attribute value.
 * @return {string} Namespaced icon name, or an empty string.
 */
export function normalizeIconName( name ) {
	if ( ! name ) {
		return '';
	}

	if ( name.includes( '/' ) ) {
		return name;
	}

	const slug = name.startsWith( 'icon-' ) ? name.slice( 5 ) : name;
	return `tribe/${ slug }`;
}

/**
 * @param {string} iconName Saved icon attribute value.
 * @return {Object|null} Registered icon entity, or null when none is selected.
 */
export function useRegisteredIcon( iconName ) {
	const name = normalizeIconName( iconName );

	return useSelect(
		( select ) =>
			name
				? select( coreDataStore ).getEntityRecord(
						'root',
						'icon',
						name
				  )
				: null,
		[ name ]
	);
}

function IconMarkup( { html } ) {
	if ( ! html ) {
		return null;
	}

	return <RawHTML>{ html }</RawHTML>;
}

export default function IconPicker( {
	selectedIcon,
	isRounded,
	iconPadding,
	iconLabel,
	iconSize,
	selectedIconColor,
	selectedBgColor,
	onChange,
} ) {
	const selectedName = normalizeIconName( selectedIcon );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ collectionSlug, setCollectionSlug ] = useState( '' );

	const collections = useSelect(
		( select ) =>
			select( coreDataStore ).getEntityRecords(
				'root',
				'iconCollection'
			),
		[]
	);

	const { icons, hasResolvedIcons } = useSelect(
		( select ) => {
			const query = collectionSlug ? { collection: collectionSlug } : {};
			const { getEntityRecords, hasFinishedResolution } =
				select( coreDataStore );

			return {
				icons: getEntityRecords( 'root', 'icon', query ),
				hasResolvedIcons: hasFinishedResolution( 'getEntityRecords', [
					'root',
					'icon',
					query,
				] ),
			};
		},
		[ collectionSlug ]
	);

	const filteredIcons = useMemo( () => {
		if ( ! icons ) {
			return [];
		}

		const query = searchQuery.trim().toLowerCase();
		const list = query
			? icons.filter(
					( { name, label } ) =>
						name.toLowerCase().includes( query ) ||
						( label || '' ).toLowerCase().includes( query )
			  )
			: icons;

		return [ ...list ].sort( ( a, b ) => a.name.localeCompare( b.name ) );
	}, [ icons, searchQuery ] );

	const collectionOptions = [
		{ label: __( 'All', 'tribe' ), value: '' },
		...( collections || [] ).map( ( collection ) => ( {
			label: collection.label,
			value: collection.slug,
		} ) ),
	];

	return (
		<TabPanel
			tabs={ [
				{ name: 'icon', title: __( 'Icon', 'tribe' ) },
				{ name: 'colors', title: __( 'Colors', 'tribe' ) },
				{ name: 'dimensions', title: __( 'Dimensions', 'tribe' ) },
			] }
		>
			{ ( tab ) => {
				if ( tab.name === 'icon' ) {
					return (
						<Flex direction="column" gap={ 4 } expanded={ false }>
							<SelectControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Collection', 'tribe' ) }
								value={ collectionSlug }
								options={ collectionOptions }
								onChange={ setCollectionSlug }
							/>
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Search Icons', 'tribe' ) }
								value={ searchQuery }
								onChange={ setSearchQuery }
							/>
							{ ! hasResolvedIcons ? (
								<Flex
									justify="center"
									role="status"
									aria-label={ __( 'Loading…', 'tribe' ) }
								>
									<Spinner />
								</Flex>
							) : (
								<div className="icon-grid">
									{ filteredIcons.map( ( icon ) => (
										<Button
											__next40pxDefaultSize
											key={ icon.name }
											className="icon-item"
											label={ icon.label }
											showTooltip
											isPressed={
												selectedName === icon.name
											}
											icon={
												<IconMarkup
													html={ icon.content }
												/>
											}
											onClick={ () =>
												onChange( {
													selectedIcon: icon.name,
												} )
											}
										/>
									) ) }
								</div>
							) }
							<TextControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Custom label', 'tribe' ) }
								value={ iconLabel }
								onChange={ ( value ) =>
									onChange( { iconLabel: value } )
								}
								help={ __(
									'Add a custom label to describe the icon to help screen reader users.',
									'tribe'
								) }
							/>
						</Flex>
					);
				}
				if ( tab.name === 'colors' ) {
					return (
						<Flex direction="column" gap={ 4 } expanded={ false }>
							<DynamicColorPicker
								controlLabel={ __( 'Icon Color', 'tribe' ) }
								colorAttribute={ 'selectedIconColor' }
								colorValue={ selectedIconColor }
								showTransparentOption={ false }
								onChange={ ( changed ) =>
									onChange( { ...changed } )
								}
							/>
							<DynamicColorPicker
								controlLabel={ __(
									'Background Color',
									'tribe'
								) }
								colorAttribute={ 'selectedBgColor' }
								colorValue={ selectedBgColor }
								onChange={ ( changed ) =>
									onChange( { ...changed } )
								}
							/>
						</Flex>
					);
				}
				if ( tab.name === 'dimensions' ) {
					return (
						<Flex direction="column" gap={ 4 } expanded={ false }>
							<RangeControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Container Padding', 'tribe' ) }
								value={ iconPadding }
								onChange={ ( value ) =>
									onChange( { iconPadding: value } )
								}
								min={ 0 }
								max={ 150 }
								afterIcon={ () => <span>px</span> }
							/>
							<RangeControl
								__next40pxDefaultSize
								__nextHasNoMarginBottom
								label={ __( 'Container Size', 'tribe' ) }
								value={ iconSize }
								onChange={ ( value ) =>
									onChange( { iconSize: value } )
								}
								min={ 20 }
								max={ 300 }
								step={ 1 }
								afterIcon={ () => <span>px</span> }
							/>
							<ToggleControl
								__nextHasNoMarginBottom
								label={ __( 'Rounded Icon', 'tribe' ) }
								checked={ isRounded }
								onChange={ ( value ) =>
									onChange( { isRounded: value } )
								}
							/>
						</Flex>
					);
				}
			} }
		</TabPanel>
	);
}
