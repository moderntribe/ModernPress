import { useSettings } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { Icon, rotateLeft } from '@wordpress/icons';
import { SelectControl, Stack } from '@wordpress/ui';

function AspectRatioPreview( { ratio } ) {
	const [ width, height = 1 ] = ratio.split( '/' ).map( Number );
	const scale = 18 / Math.max( width, height );
	const boxWidth = width * scale;
	const boxHeight = height * scale;

	return (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			aria-hidden="true"
			style={ { flexShrink: 0 } }
		>
			<rect
				x={ ( 24 - boxWidth ) / 2 }
				y={ ( 24 - boxHeight ) / 2 }
				width={ boxWidth }
				height={ boxHeight }
				fill="none"
				stroke="currentColor"
				strokeWidth="1.5"
			/>
		</svg>
	);
}

export default function ImageAspectRatioControl( { value, onChange } ) {
	const [ presets = [] ] = useSettings( 'dimensions.aspectRatios.theme' );
	const items = [
		{ label: __( 'Default', 'tribe' ), value: '' },
		...presets.map( ( { name, slug, ratio } ) => ( {
			label: name,
			value: slug,
			ratio,
		} ) ),
	];

	return (
		<SelectControl
			label={ __( 'Image Aspect Ratio', 'tribe' ) }
			items={ items }
			value={
				items.find( ( item ) => item.value === value ) ?? items[ 0 ]
			}
			onValueChange={ ( item ) => {
				if ( typeof item?.value === 'string' ) {
					onChange( item.value );
				}
			} }
		>
			{ items.map( ( item ) => (
				<SelectControl.Item
					key={ item.value }
					value={ item }
					label={ item.label }
				>
					<SelectControl.ItemLabel>
						<Stack
							direction="row"
							gap="sm"
							align="center"
							justify="space-between"
						>
							{ item.label }
							{ item.ratio ? (
								<AspectRatioPreview ratio={ item.ratio } />
							) : (
								<Icon
									icon={ rotateLeft }
									size={ 24 }
									aria-hidden="true"
									style={ { flexShrink: 0 } }
								/>
							) }
						</Stack>
					</SelectControl.ItemLabel>
				</SelectControl.Item>
			) ) }
		</SelectControl>
	);
}
