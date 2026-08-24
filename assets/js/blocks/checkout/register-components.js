/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';
import { lazy } from '@wordpress/element';

import metadata from './component-metadata';

registerCheckoutBlock({
    metadata: metadata.CHECKOUT_CHECKBOXES,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "checkout-blocks/checkout-checkboxes" */ './checkout-checkboxes/frontend'
                )
    ),
});

registerCheckoutBlock({
    metadata: metadata.CHECKOUT_PHOTOVOLTAIC_SYSTEM_NOTICE,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "checkout-blocks/checkout-photovoltaic-system-notice" */ './checkout-photovoltaic-system-notice/frontend'
                )
    ),
});

registerCheckoutBlock({
    metadata: metadata.CHECKOUT_LEGAL_GUARANTEE,
    component: lazy(
        () =>
            import(
                /* webpackChunkName: "checkout-blocks/checkout-legal-guarantee" */ './checkout-legal-guarantee/frontend'
                )
    ),
});
