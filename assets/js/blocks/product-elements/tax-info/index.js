/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { shield, Icon } from '@wordpress/icons';
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
    title: __( 'Tax Notice', 'woocommerce-germanized' ),
    description: __( 'Inserts the product\'s tax notice.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ shield }
            className="wc-block-editor-components-block-icon"
        /> },

    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-tax-info .wc-gzd-block-components-product-tax-info',
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-tax-info', blockConfig );
