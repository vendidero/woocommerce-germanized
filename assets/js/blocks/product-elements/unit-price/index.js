/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import { currencyDollar, Icon } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';

const { ancestor, ...configuration } = sharedConfig;

const blockConfig = {
    ...configuration,
    apiVersion: 2,
    title: 'Unit Price',
    description: 'Unit Price',
    usesContext: [ 'query', 'queryId', 'postId' ],
    icon: { src: <Icon
            icon={ currencyDollar }
            className="wc-block-editor-components-block-icon"
        /> },
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
    supports: {
        ...sharedConfig.supports,
        ...( {
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
            __experimentalSelector:
                '.wp-block-woocommerce-product-price .wc-block-components-product-price',
        } ),
        ...( typeof __experimentalGetSpacingClassesAndStyles === 'function' && {
            spacing: {
                margin: true,
                padding: true,
            },
        } ),
    },
    edit,
};

registerBlockType( 'woocommerce-germanized/product-unit-price', blockConfig );
