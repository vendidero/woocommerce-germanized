/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { shipping, Icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';

const { ancestor, ...configuration } = sharedConfig;

const blockConfig = {
    ...configuration,
    apiVersion: 2,
    title: __( 'Shipping Costs Notice', 'woocommerce-germanized' ),
    description: __( 'Inserts the product\'s shipping costs notice.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ shipping }
            className="wc-block-editor-components-block-icon"
        /> },

    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-shipping-costs-info .wc-gzd-block-components-product-shipping-costs-info',
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-shipping-costs-info', blockConfig );
