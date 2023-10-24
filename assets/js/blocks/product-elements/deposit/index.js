/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { currencyEuro, Icon } from '@wordpress/icons';
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
    title: __( 'Deposit amount', 'woocommerce-germanized' ) + ( ! getSetting( 'isPro' ) ? ' (Pro)' : '' ),
    description: __( 'Inserts the product\'s deposit amount.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ currencyEuro }
            className="wc-block-editor-components-block-icon"
        /> },

    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-deposit .wc-gzd-block-components-product-deposit',
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-deposit', blockConfig );
