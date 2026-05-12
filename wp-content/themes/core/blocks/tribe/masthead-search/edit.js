import { useBlockProps } from '@wordpress/block-editor';
import { ServerSideRender } from '@wordpress/server-side-render';

import './editor.pcss';

export default function Edit() {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<ServerSideRender block="tribe/masthead-search" />
		</div>
	);
}
