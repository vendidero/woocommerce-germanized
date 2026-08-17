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

const { ancestor, ...configuration } = sharedConfig;

console.log(sharedConfig.attributes);

const blockConfig = {
    ...configuration,
    apiVersion: 3,
    title: __( 'EU GARAN label', 'woocommerce-germanized' ),
    description: __( 'Inserts the EU GARAN label for the product in case available.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ info }
            className="wc-block-editor-components-block-icon"
        /> },
    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-garan-label .wc-gzd-block-components-product-garan-label',
        } )
    },
    attributes: {
        ...sharedConfig.attributes,
        ...( {
            variant: {
                type: "string",
                default: "folded"
            }
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-garan-label', blockConfig );
