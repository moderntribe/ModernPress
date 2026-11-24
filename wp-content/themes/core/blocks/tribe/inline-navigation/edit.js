import { __, _n, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	LinkControl,
} from '@wordpress/block-editor';
import { store as blocksStore } from '@wordpress/blocks';
import { Button, Modal, PanelBody } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useMemo, useState } from '@wordpress/element';
import { Icon } from '@wordpress/icons';
import { ServerSideRender } from '@wordpress/server-side-render';
import {
	Fieldset,
	InputControl,
	SearchableChipSelectControl,
	SelectControl,
	Stack,
} from '@wordpress/ui';

import blockSettings from './block.json';
import ManualNavigationControl from './components/manual-navigation-control';

import './editor.pcss';

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const blockProps = useBlockProps();
	const {
		mode,
		manualItems,
		shareServices,
		buttonLabel,
		buttonUrl,
		buttonOpensInNewTab,
		mobileMenuToggleLabel,
	} = attributes;

	const modeOptions = [
		{
			value: 'siblings',
			label: 'Siblings',
			helper: 'Show all posts that share the same parent of the current post',
		},
		{
			value: 'children',
			label: 'Children',
			helper: 'Show all child posts of the current post',
		},
		{
			value: 'manual',
			label: 'Manual',
			helper: 'Manually added links',
		},
	];

	const [ isManualModalOpen, setIsManualModalOpen ] = useState( false );

	const manualItemCount = manualItems.filter(
		( item ) => item.label?.trim() || item.url?.trim()
	).length;

	const shareOptions = useSelect(
		( select ) =>
			select( blocksStore ).getBlockVariations(
				'outermost/social-sharing-link',
				'inserter'
			),
		[]
	);
	const shareItems = ( shareOptions || [] ).map( ( option ) => ( {
		value: option.attributes.service,
		label: option.title,
		icon: option.icon,
	} ) );

	const buttonLink = useMemo(
		() => ( { url: buttonUrl, opensInNewTab: buttonOpensInNewTab } ),
		[ buttonUrl, buttonOpensInNewTab ]
	);

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block={ blockSettings.name }
				attributes={ attributes }
			/>
			{ isManualModalOpen && mode === 'manual' && (
				<Modal
					title={ __( 'Edit navigation items', 'tribe' ) }
					onRequestClose={ () => setIsManualModalOpen( false ) }
					size="large"
				>
					<ManualNavigationControl
						items={ manualItems }
						onChange={ ( items ) =>
							setAttributes( { manualItems: items } )
						}
					/>
				</Modal>
			) }
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<Stack direction="column" gap="lg">
							<SelectControl
								label={ __( 'Navigation Mode', 'tribe' ) }
								value={ mode }
								items={ modeOptions }
								popupWidth="anchor"
								onValueChange={ ( value ) =>
									setAttributes( { mode: value } )
								}
							>
								{ modeOptions.map( ( option ) => (
									<SelectControl.Item
										key={ option.value }
										value={ option.value }
										label={ option.label }
									>
										<SelectControl.ItemLabel>
											{ option.label }
										</SelectControl.ItemLabel>
										<SelectControl.ItemDescription>
											{ option.helper }
										</SelectControl.ItemDescription>
									</SelectControl.Item>
								) ) }
							</SelectControl>
							{ mode === 'manual' && (
								<Stack
									direction="column"
									gap="sm"
									align="flex-start"
								>
									<Button
										variant="secondary"
										onClick={ () =>
											setIsManualModalOpen( true )
										}
									>
										{ __(
											'Edit navigation items',
											'tribe'
										) }
									</Button>
									<span>
										{ sprintf(
											/* translators: %d is the number of navigation items. */
											_n(
												'%d navigation item',
												'%d navigation items',
												manualItemCount,
												'tribe'
											),
											manualItemCount
										) }
									</span>
								</Stack>
							) }
							{ shareItems.length ? (
								<SearchableChipSelectControl
									label={ __( 'Share services', 'tribe' ) }
									items={ shareItems }
									value={ shareServices.map(
										( service ) =>
											shareItems.find(
												( item ) =>
													item.value === service
											) || {
												value: service,
												label: service,
											}
									) }
									onValueChange={ ( items ) =>
										setAttributes( {
											shareServices: items.map(
												( item ) => item.value
											),
										} )
									}
									chipsContent={ ( items ) =>
										items.map( ( item ) => (
											<SearchableChipSelectControl.ChipWithRemove
												key={ item.value }
												prefix={
													item.icon && (
														<Icon
															icon={ item.icon }
															size={ 16 }
															aria-hidden="true"
														/>
													)
												}
											>
												{ item.label }
											</SearchableChipSelectControl.ChipWithRemove>
										) )
									}
								>
									{ ( item ) => (
										<SearchableChipSelectControl.Item
											key={ item.value }
											value={ item }
										>
											<SearchableChipSelectControl.ItemLabel>
												<Stack
													direction="row"
													gap="sm"
													justify="flex-start"
												>
													<Icon
														icon={ item.icon }
														size={ 24 }
													/>
													{ item.label }
												</Stack>
											</SearchableChipSelectControl.ItemLabel>
										</SearchableChipSelectControl.Item>
									) }
								</SearchableChipSelectControl>
							) : (
								<p>
									{ __(
										'No social sharing services are available.',
										'tribe'
									) }
								</p>
							) }
							<InputControl
								label={ __( 'Button label', 'tribe' ) }
								value={ buttonLabel }
								onValueChange={ ( value ) =>
									setAttributes( {
										buttonLabel: value,
									} )
								}
							/>
							<Fieldset.Root className="b-inline-navigation__link-control">
								<Fieldset.Legend>
									{ __( 'Button URL', 'tribe' ) }
								</Fieldset.Legend>
								<LinkControl
									value={ buttonLink }
									onChange={ ( value ) =>
										setAttributes( {
											buttonUrl: value.url,
											buttonOpensInNewTab:
												value.opensInNewTab ?? false,
										} )
									}
									onRemove={ () =>
										setAttributes( {
											buttonUrl: '',
											buttonOpensInNewTab: false,
										} )
									}
								/>
							</Fieldset.Root>
							<InputControl
								label={ __(
									'Mobile Menu Toggle Label',
									'tribe'
								) }
								value={ mobileMenuToggleLabel }
								onValueChange={ ( value ) =>
									setAttributes( {
										mobileMenuToggleLabel: value,
									} )
								}
							/>
						</Stack>
					</PanelBody>
				</InspectorControls>
			) }
		</div>
	);
}
