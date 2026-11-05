import { ServerSideRender } from '@wordpress/server-side-render';

/**
 * Server-rendered preview of the comparison table using current editor state.
 *
 * @param {Object} root0
 * @param {Object} root0.attributes  Table block attributes passed to `render.php`.
 * @param {Array}  root0.previewRows Row data bridged from inner blocks for SSR.
 */
export default function TablePreviewPanel( { attributes, previewRows } ) {
	return (
		<div className="b-comparison-table-editor__preview">
			<ServerSideRender
				block="tribe/comparison-table"
				attributes={ {
					...attributes,
					previewRows,
				} }
				httpMethod="POST"
			/>
		</div>
	);
}
