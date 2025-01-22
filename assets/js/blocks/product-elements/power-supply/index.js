/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { plugins, Icon } from '@wordpress/icons';
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
    title: __( 'Power supply', 'woocommerce-germanized' ),
    description: __( 'Inserts information on power supply for wireless electronic devices.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ plugins }
            className="wc-block-editor-components-block-icon"
        /> },

    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-power-supply .wc-gzd-block-components-product-power-supply',
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-power-supply', blockConfig );
