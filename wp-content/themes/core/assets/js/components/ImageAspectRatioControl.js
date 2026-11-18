import { useSettings } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/ui';

export default function ImageAspectRatioControl( { value, onChange } ) {
	const [ presets = [] ] = useSettings( 'dimensions.aspectRatios.theme' );
	const items = [
		{ label: __( 'Default', 'tribe' ), value: '' },
		...presets.map( ( { name, slug } ) => ( {
			label: name,
			value: slug,
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
		/>
	);
}
