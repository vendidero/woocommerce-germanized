/**
 * External dependencies
 */
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';
import { getSetting } from '@germanized/settings';

const adjustInnerBlockTemplate = (
    defaultValue,
    extensions,
    args,
    validation
) => {
    if ( args?.block === 'woocommerce/cart-items-block' || args?.block === 'woocommerce/cart-totals-block' ) {
        defaultValue.push( 'woocommerce-germanized/checkout-legal-guarantee' );
    }
    R
    return defaultValue;
};

registerCheckoutFilters(
    'woocommerce-germanized',
    {
        placeOrderButtonLabel: ( value, extensions, args ) => {
            return getSetting( 'buyNowButtonText' );
        },
        additionalCartCheckoutInnerBlockTypes: adjustInnerBlockTemplate,
    }
);