/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { page, Icon } from '@wordpress/icons';
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
    title: __( 'Safety instructions', 'woocommerce-germanized' ) + ( ! getSetting( 'isPro' ) ? ' (Pro)' : '' ),
    description: __( 'Inserts the product\'s safety instructions.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ page }
            className="wc-block-editor-components-block-icon"
        /> },

    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-safety-instructions .wc-gzd-block-components-product-safety-instructions',
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-safety-instructions', blockConfig );
