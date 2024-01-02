/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	RichText
} from '@wordpress/block-editor';
import './editor.scss';

export const Edit = ({ attributes, setAttributes }) => {
	const { text, title } = attributes;
	const blockProps = useBlockProps();

	const currentText = text || sprintf( __( 'To benefit from the tax exemption, please confirm the VAT exemption according to {legal_text} by activating the checkbox.', 'woocommerce-germanized' ) );
	const currentTitle = title || __( 'Your shopping cart is eligible for VAT exemption', 'woocommerce-germanized' );

	return (
		<div {...blockProps}>
			<div className="wc-gzd-block-checkout__photovoltaic-system-notice wc-block-components-notice-banner is-info">
				<RichText
					tagName="h4"
					className="wc-block-components-title"
					value={ currentTitle }
					onChange={ ( value ) =>
						setAttributes( { title: value } )
					}
				/>
				<RichText
					tagName="p"
					value={ currentText }
					onChange={ ( value ) =>
						setAttributes( { text: value } )
					}
				/>
			</div>
		</div>
	);
};

export const Save = () => {
	return <div { ...useBlockProps.save() } />;
};
