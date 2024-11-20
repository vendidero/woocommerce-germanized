/**
 * External dependencies
 */
import { registerBlockComponent } from '@woocommerce/blocks-registry';
import { lazy } from '@wordpress/element';

registerBlockComponent( {
    blockName: 'woocommerce-germanized/product-unit-price',
    component: lazy( () =>
        import(
            /* webpackChunkName: "product-unit-price" */ './unit-price/frontend'
            )
    ),
} );

registerBlockComponent( {
    blockName: 'woocommerce-germanized/product-delivery-time',
    component: lazy( () =>
        import(
            /* webpackChunkName: "product-delivery-time" */ './delivery-time/frontend'
            )
    ),
} );

registerBlockComponent( {
    blockName: 'woocommerce-germanized/product-tax-info',
    component: lazy( () =>
        import(
            /* webpackChunkName: "product-tax-info" */ './tax-info/frontend'
            )
    ),
} );

registerBlockComponent( {
    blockName: 'woocommerce-germanized/product-shipping-costs-info',
    component: lazy( () =>
        import(
            /* webpackChunkName: "product-shipping-costs-info" */ './shipping-costs-info/frontend'
            )
    ),
} );

registerBlockComponent( {
    blockName: 'woocommerce-germanized/product-unit-product',
    component: lazy( () =>
        import(
            /* webpackChunkName: "product-unit-product" */ './unit-product/frontend'
            )
    ),
} );

registerBlockComponent( {
    blockName: 'woocommerce-germanized/product-nutri-score',
    component: lazy( () =>
        import(
            /* webpackChunkName: "product-nutri-score" */ './nutri-score/frontend'
            )
    ),
} );

registerBlockComponent( {
    blockName: 'woocommerce-germanized/product-deposit',
    component: lazy( () =>
        import(
            /* webpackChunkName: "product-deposit" */ './deposit/frontend'
            )
    ),
} );

registerBlockComponent( {
    blockName: 'woocommerce-germanized/product-deposit-packaging-type',
    component: lazy( () =>
        import(
            /* webpackChunkName: "product-deposit-packaging-type" */ './deposit-packaging-type/frontend'
            )
    ),
} );

registerBlockComponent( {
    blockName: 'woocommerce-germanized/product-defect-description',
    component: lazy( () =>
        import(
            /* webpackChunkName: "product-defect-description" */ './defect-description/frontend'
            )
    ),
} );

registerBlockComponent( {
    blockName: 'woocommerce-germanized/product-manufacturer',
    component: lazy( () =>
        import(
            /* webpackChunkName: "product-manufacturer" */ './manufacturer/frontend'
            )
    ),
} );

registerBlockComponent( {
    blockName: 'woocommerce-germanized/product-safety-attachments',
    component: lazy( () =>
        import(
            /* webpackChunkName: "product-safety-attachments" */ './product-safety-attachments/frontend'
            )
    ),
} );

registerBlockComponent( {
    blockName: 'woocommerce-germanized/product-safety-instructions',
    component: lazy( () =>
        import(
            /* webpackChunkName: "product-safety-instructions" */ './safety-instructions/frontend'
            )
    ),
} );