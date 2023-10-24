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