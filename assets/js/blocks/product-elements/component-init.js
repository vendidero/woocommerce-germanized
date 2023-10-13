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
