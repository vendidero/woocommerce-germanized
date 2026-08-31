/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { info, Icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';

const { configuration } = sharedConfig;

const blockConfig = {
    ...configuration,
    apiVersion: 3,
    title: __( 'EU Legal Guarantee', 'woocommerce-germanized' ),
    description: __( 'Inserts the legal guarantee label for the product in case available.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ info }
            className="wc-block-editor-components-block-icon"
        /> },
    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-legal-guarantee .wc-gzd-block-components-product-legal-guarantee',
        } )
    },
    attributes: {
        ...sharedConfig.attributes,
        ...( {
            variant: {
                type: "string",
                default: "preview"
            }
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-legal-guarantee', blockConfig );
