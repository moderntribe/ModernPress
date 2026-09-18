import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	/**
	 * Saves only inner blocks; front-end markup is rendered in PHP.
	 * @param {Object} props
	 */
	save: ( props ) => <InnerBlocks.Content { ...props } />,
} );
