/**
 * External dependencies
 */
import { registerCheckoutFilters } from '@woocommerce/blocks-checkout';
import { getSetting } from '@germanized/settings';

registerCheckoutFilters(
    'woocommerce-germanized',
    {
        placeOrderButtonLabel: ( value, extensions, args ) => {
            return getSetting( 'buyNowButtonText' );
        }
    }
);

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

registerCheckoutFilters( 'woocommerce-germanized', {
    additionalCartCheckoutInnerBlockTypes: adjustInnerBlockTemplate,
} );