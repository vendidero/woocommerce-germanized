/**
 * External dependencies
 */
import { SVG } from '@wordpress/components';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, info } from '@wordpress/icons';

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
				icon={ info }
				className="wc-block-editor-components-block-icon"
			/>
		)
	},
	edit: Edit,
	save: Save,
});
