/**
 * External dependencies
 */
import { registerCheckoutBlock } from '@woocommerce/blocks-checkout';

import metadata from './component-metadata';
import CheckoutLegalGuaranteeFrontend from '../checkout/checkout-legal-guarantee/frontend';

registerCheckoutBlock({
    metadata: metadata.CHECKOUT_LEGAL_GUARANTEE,
    component: CheckoutLegalGuaranteeFrontend,
});

