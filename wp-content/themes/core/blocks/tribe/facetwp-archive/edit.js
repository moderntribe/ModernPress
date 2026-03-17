import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { PanelBody, RadioControl } from '@wordpress/components';

export default function Edit( { attributes, setAttributes, isSelected } ) {
	const { filterBarPosition } = attributes;

	const blockProps = useBlockProps( {
		className: `b-facetwp-archive b-facetwp-archive--filter-bar-${ filterBarPosition }`,
	} );
	const innerBlockProps = useInnerBlocksProps(
		{ className: 'b-facetwp-archive__inner' },
		{
			allowedBlocks: [ 'tribe/facetwp-filter-bar', 'tribe/facetwp-grid' ],
			template: [
				[
					'tribe/facetwp-filter-bar',
					{ lock: { move: true, remove: true } },
				],
				[
					'tribe/facetwp-grid',
					{ lock: { move: true, remove: true } },
				],
			],
			renderAppender: false,
		}
	);

	return (
		<div { ...blockProps }>
			{ isSelected && (
				<InspectorControls>
					<PanelBody title={ __( 'Block Settings', 'tribe' ) }>
						<RadioControl
							label={ __( 'Filter Bar Position', 'tribe' ) }
							options={ [
								{ label: __( 'Top', 'tribe' ), value: 'top' },
								{
									label: __( 'Sidebar', 'tribe' ),
									value: 'sidebar',
								},
							] }
							selected={ filterBarPosition }
							help={ __(
								'The position of the filter bar relative to the grid.',
								'tribe'
							) }
							onChange={ ( value ) =>
								setAttributes( { filterBarPosition: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>
			) }
			<div { ...innerBlockProps } />
		</div>
	);
}
