/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
import { lazy } from '@wordpress/element';

import metadata from './component-metadata';

registerCheckoutBlock({
    metadata: metadata.MINI_CART_NOTICES,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "mini-cart-blocks/mini-cart-notices" */ './mini-cart-notices/frontend'
                )
    ),
});
