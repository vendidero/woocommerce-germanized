/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { info, Icon } from '@wordpress/icons';
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
    title: __( 'Deposit packaging type', 'woocommerce-germanized' ) + ( ! getSetting( 'isPro' ) ? ' (Pro)' : '' ),
    description: __( 'Inserts the product\'s deposit packaging type.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ info }
            className="wc-block-editor-components-block-icon"
        /> },

    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-deposit-packaging-type .wc-gzd-block-components-product-deposit-packaging-type',
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-deposit-packaging-type', blockConfig );
