/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { currencyEuro, Icon } from '@wordpress/icons';
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
    title: __( 'Unit Price', 'woocommerce-germanized' ),
    description: __( 'Inserts the product\'s price per unit.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ currencyEuro }
            className="wc-block-editor-components-block-icon"
        /> },

    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-unit-price .wc-gzd-block-components-product-unit-price',
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-unit-price', blockConfig );
