/**
 * External dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls
} from '@wordpress/block-editor';
import classnames from "classnames";

import { PanelBody, SelectControl } from "@wordpress/components";
import { WC_GZD_ASSET_URL } from '@germanized/settings';

export const Edit = ({ attributes, setAttributes, className }) => {
	const { variant, align } = attributes;
	const blockProps = useBlockProps();

	const variants = {
		'preview': _x( 'Preview', 'eu-label-variant', 'woocommerce-germanized' ),
		'full': _x( 'Full', 'eu-label-variant', 'woocommerce-germanized' ),
		'link': _x( 'Link', 'eu-label-variant', 'woocommerce-germanized' ),
	};

	let formattedPreview = '';

	if ( 'full' === variant || 'preview' === variant ) {
		const maxWidth = 'full' === variant ? '100%' : '50%';

		formattedPreview = (
			<>
				<img style={{maxWidth: maxWidth}} src={ `${ WC_GZD_ASSET_URL }images/legal-guarantee/legal_guarantee_notice-EN.png` } />
			</>
		);
	} else if ( 'link' === variant ) {
		formattedPreview = (
			<>
				<p><a href="#">{ __( 'Your legal guarantee rights', 'woocommerce-germanized' ) }</a></p>
			</>
		);
	}

	const classes = classnames( 'wc-gzd-block-checkout__legal-guarantee', className, {
		[ `has-text-align-${ align }` ]: align
	} );

	return (
		<>
		<div {...blockProps}>
			<div className={ classes }>
				{ formattedPreview }
			</div>
		</div>
		<InspectorControls>
			<PanelBody>
				<SelectControl
					label={ __( 'Variant', 'woocommerce-germanized' ) }
					value={ variant }
					onChange={ ( value ) => setAttributes({ variant: value }) }
					options={ Object.keys( variants ).map( ( key ) => {
						return {
							'label': variants[ key ],
							'value': key
						}
					} ) }
					__next40pxDefaultSize={ true }
					__nextHasNoMarginBottom={ true }
				/>
			</PanelBody>
		</InspectorControls>
		</>
	);
};

export const Save = () => {
	return <div { ...useBlockProps.save() } />;
};
