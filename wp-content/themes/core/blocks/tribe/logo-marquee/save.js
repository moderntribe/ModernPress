import { useBlockProps } from '@wordpress/block-editor';

export default function save( props ) {
	const {
		attributes: { marqueeSpeed, images },
	} = props;
	const blockProps = useBlockProps.save();

	return (
		<div { ...blockProps }>
			<div className="logo-list" data-marquee-speed={ marqueeSpeed }>
				{ images.map( ( img, index ) => (
					<div key={ index } className="gallery-item">
						<img
							src={ img.url }
							alt={ img.alt }
							data-id={ img.id }
						/>
					</div>
				) ) }
			</div>
		</div>
	);
}
