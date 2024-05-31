import { registerPlugin } from "@wordpress/plugins";
import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { extensionCartUpdate } from '@woocommerce/blocks-checkout';
import _ from 'lodash';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';

const render = () => {
    const {
        currentPaymentMethod
    } = useSelect( ( select ) => {
        const paymentStore = select( PAYMENT_STORE_KEY );

        return {
            currentPaymentMethod: paymentStore.getActivePaymentMethod(),
        }
    } );

    useEffect( () => {
        if ( currentPaymentMethod ) {
            extensionCartUpdate( {
                namespace: 'woocommerce-germanized-set-payment-method',
                data: {
                    'active_method': currentPaymentMethod,
                },
            } );
        }
    }, [
        currentPaymentMethod
    ] );

    return null;
};

registerPlugin( 'woocommerce-germanized-checkout-fees', {
    render,
    scope: 'woocommerce-checkout',
} );