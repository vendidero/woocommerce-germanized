/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { button, Icon } from '@wordpress/icons';
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
    title: __( 'Nutri-Score', 'woocommerce-germanized' ) + ( ! getSetting( 'isPro' ) ? ' (Pro)' : '' ),
    description: __( 'Inserts the product\'s Nutri-Score.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ button }
            className="wc-block-editor-components-block-icon"
        /> },

    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-nutri-score .wc-gzd-block-components-product-nutri-score',
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-nutri-score', blockConfig );
