import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export default function save( props ) {
	const {
		attributes: { blockId },
	} = props;

	// omit tabindex — kses strips it from post content and breaks block validation
	const blockProps = useBlockProps.save( {
		id: blockId,
		role: 'tabpanel',
		hidden: true,
		'aria-labelledby': 'button-' + blockId,
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
}
