/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { postDate, Icon } from '@wordpress/icons';
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
    title: __( 'Delivery Time', 'woocommerce-germanized' ),
    description: __( 'Inserts the product\'s delivery time.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ postDate }
            className="wc-block-editor-components-block-icon"
        /> },

    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-delivery-time .wc-gzd-block-components-product-delivery-time',
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-delivery-time', blockConfig );
