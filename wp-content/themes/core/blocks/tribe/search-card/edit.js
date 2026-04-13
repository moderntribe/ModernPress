import { useBlockProps } from '@wordpress/block-editor';
import { ServerSideRender } from '@wordpress/server-side-render';
import metadata from './block.json';

export default function Edit( { context } ) {
	const blockProps = useBlockProps();

	/**
	 * Set up a query string to pass the post id to the server side render.
	 * As far as I can tell, this is the only way to pass the post id from context
	 * to the server side render function for displaying in the editor.
	 */
	const urlQueryArgs = { editorPostId: context.postId };

	return (
		<div { ...blockProps }>
			<ServerSideRender
				block={ metadata.name }
				urlQueryArgs={ urlQueryArgs }
			/>
		</div>
	);
}
