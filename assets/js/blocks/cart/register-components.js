/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
import { lazy } from '@wordpress/element';

import metadata from './component-metadata';

registerCheckoutBlock({
    metadata: metadata.CHECKOUT_LEGAL_GUARANTEE,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "checkout-blocks/checkout-legal-guarantee" */ '../checkout/checkout-legal-guarantee/frontend'
                )
    ),
});

