import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.pcss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	icon: (
		<svg
			width="24"
			height="24"
			viewBox="0 0 24 24"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<path
				fillRule="evenodd"
				clipRule="evenodd"
				d="M1.5 2.25H7.62488H9.125H13.3749H14.5024H20.2524V6.5H22.25V18.75C22.25 19.413 21.9866 20.0489 21.5178 20.5178C21.0489 20.9866 20.413 21.25 19.75 21.25H4C3.33696 21.25 2.70107 20.9866 2.23223 20.5178C1.76339 20.0489 1.5 19.413 1.5 18.75V2.25ZM18.7524 6.5V3.75H14.8749V6.5H18.7524ZM3 3.75H7.62488V7.25H7.625V8H20.75V18.75C20.75 19.0152 20.6446 19.2696 20.4571 19.4571C20.2696 19.6446 20.0152 19.75 19.75 19.75H4C3.73478 19.75 3.48043 19.6446 3.29289 19.4571C3.10536 19.2696 3 19.0152 3 18.75V3.75ZM9.125 6.5V3.75H13.0024V6.5H9.125Z"
				fill="black"
			/>
		</svg>
	),

	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * Saves only inner blocks so order is persisted. Front-end markup is from render.php.
	 * @param {Object} props
	 */
	save: ( props ) => <InnerBlocks.Content { ...props } />,
} );
