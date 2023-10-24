/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { page, Icon } from '@wordpress/icons';
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
    title: __( 'Defect Description', 'woocommerce-germanized' ),
    description: __( 'Inserts the product\'s defect description.', 'woocommerce-germanized' ),
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ page }
            className="wc-block-editor-components-block-icon"
        /> },

    supports: {
        ...sharedConfig.supports,
        ...( {
            __experimentalSelector:
                '.wp-block-woocommerce-gzd-product-defect-description .wc-gzd-block-components-product-defect-description',
        } )
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-defect-description', blockConfig );
