/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { Icon, customPostType } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { Edit, Save } from './edit';
import './style.scss';
import metadata from './block.json';

registerBlockType( metadata, {
	icon: {
		src: (
			<Icon
				icon={ customPostType }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	edit: Edit,
	save: Save,
});
