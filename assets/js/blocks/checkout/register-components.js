/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

import metadata from './component-metadata';
import CheckoutCheckboxesFrontend from './checkout-checkboxes/frontend';
import CheckoutPhotovoltaicSystemNoticeFrontend from './checkout-photovoltaic-system-notice/frontend';
import CheckoutLegalGuaranteeFrontend from './checkout-legal-guarantee/frontend';

registerCheckoutBlock({
    metadata: metadata.CHECKOUT_CHECKBOXES,
    component: CheckoutCheckboxesFrontend,
});

registerCheckoutBlock({
    metadata: metadata.CHECKOUT_PHOTOVOLTAIC_SYSTEM_NOTICE,
    component: CheckoutPhotovoltaicSystemNoticeFrontend,
});

registerCheckoutBlock({
    metadata: metadata.CHECKOUT_LEGAL_GUARANTEE,
    component: CheckoutLegalGuaranteeFrontend,
});
