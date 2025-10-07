/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText,
	InspectorControls,
} from '@wordpress/block-editor';
import { PanelBody, ExternalLink } from '@wordpress/components';
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { getSetting, ADMIN_URL } from '@woocommerce/settings';
import Noninteractive from '@germanized/base-components/noninteractive';

import LegalCheckbox from "./checkboxes/legal-checkbox";
import './editor.scss';

export const Edit = ({ attributes, setAttributes }) => {
	const { text } = attributes;
	const blockProps = useBlockProps();

	const checkbox = {
		'id': 'preview',
		'label': __( 'This is a label being printed next to your legal checkbox.', 'woocommerce-germanized' ),
		'hidden': false,
		'checked': false,
		'is_required': true,
		'name': 'preview',
		'has_checkbox': true,
        'error_message': '',
		'wrapper_classes': [],
	};

	return (
		<div {...blockProps}>
			<InspectorControls>
				<PanelBody
					title={ __('Checkboxes', 'woocommerce-germanized' ) }
				>
					<ExternalLink
						href={ `${ ADMIN_URL }admin.php?page=wc-settings&tab=germanized-checkboxes` }
					>
						{ __(
							'Manage checkboxes',
							'woocommerce-germanized'
						) }
					</ExternalLink>
				</PanelBody>
			</InspectorControls>
			<div className="wc-gzd-editor-checkboxes">
				<Noninteractive>
					<LegalCheckbox
						checkbox={ checkbox }
						key={ checkbox.id }
						onChangeCheckbox={ () => {} }
					/>
				</Noninteractive>
			</div>
		</div>
	);
};

export const Save = ({ attributes }) => {
	const { text } = attributes;

	return (
		<div {...useBlockProps.save()}>

		</div>
	);
};
