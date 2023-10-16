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
