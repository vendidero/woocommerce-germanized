/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, grid } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import save from '../save';

/**
 * Holds default config for this collection of blocks.
 * attributes and title are omitted here as these are added on an individual block level.
 */
const sharedConfig = {
    category: 'woocommerce-product-elements',
    keywords: [ __( 'WooCommerce', 'woocommerce-germanized' ) ],
    icon: {
        src: (
            <Icon
                icon={ grid }
                className="wc-block-editor-components-block-icon"
            />
        ),
    },
    supports: {
        html: false,
    },
    ancestor: [ 'woocommerce/all-products', 'woocommerce/single-product' ],
    save
};

export default sharedConfig;
