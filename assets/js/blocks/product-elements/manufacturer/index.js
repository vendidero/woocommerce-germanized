/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { store, Icon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { getSetting } from '@germanized/settings';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';

const { ancestor, ...configuration } = sharedConfig;

const blockConfig = {
    ...configuration,
    apiVersion: 2,
    title: __( 'Manufacturer', 'woocommerce-germanized' ) + ( ! getSetting( 'isPro' ) ? ' (Pro)' : '' ),
    description: __( 'Inserts the product\'s manufacturer information.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ store }
            className="wc-block-editor-components-block-icon"
        /> },

    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-manufacturer .wc-gzd-block-components-product-manufacturer',
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-manufacturer', blockConfig );
