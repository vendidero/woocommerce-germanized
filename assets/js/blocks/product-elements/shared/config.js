/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, grid } from '@wordpress/icons';
import { __experimentalGetSpacingClassesAndStyles } from '@wordpress/block-editor';

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
        color: {
            text: true,
            background: true,
            link: false,
            __experimentalSkipSerialization: true,
        },
        typography: {
            fontSize: true,
            lineHeight: true,
            __experimentalFontFamily: true,
            __experimentalFontWeight: true,
            __experimentalFontStyle: true,
            __experimentalSkipSerialization: true,
            __experimentalLetterSpacing: true,
        },
        ...( typeof __experimentalGetSpacingClassesAndStyles === 'function' && {
            spacing: {
                margin: true,
                padding: true,
            },
        } ),
    },
    attributes: {
        productId: {
            type: 'number',
            default: 0,
        },
        isDescendentOfQueryLoop: {
            type: 'boolean',
            default: false,
        },
        textAlign: {
            type: 'string',
            default: '',
        },
        isDescendentOfSingleProductTemplate: {
            type: 'boolean',
            default: false,
        },
        isDescendentOfSingleProductBlock: {
            type: 'boolean',
            default: false,
        }
    },
    ancestor: [ 'woocommerce/all-products', 'woocommerce/single-product' ],
    save
};

export default sharedConfig;
