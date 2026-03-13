import { registerBlockType } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';

import './style.pcss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	/**
	 * @see ./edit.js
	 */
	edit: Edit,

	save: ( props ) => {
		return <InnerBlocks.Content { ...props } />;
	},
} );
