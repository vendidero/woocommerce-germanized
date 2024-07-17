import { CHECKOUT_STORE_KEY, CART_STORE_KEY } from '@woocommerce/block-data';
import { useSelect, useDispatch, select, dispatch } from '@wordpress/data';
import {useCallback} from "@wordpress/element";

export const getSelectedShippingProviders = (
    shippingRates = null
) => {
    if ( null === shippingRates ) {
        shippingRates = useSelect( ( select ) => {
            const isEditor = !! select( 'core/editor' );
            const store = select( CART_STORE_KEY );

            return isEditor ? [] : store.getShippingRates();
        } );
    }

    return Object.fromEntries( shippingRates.map( ( { package_id: packageId, shipping_rates: packageRates } ) => {
        const meta_data = packageRates.find( ( rate ) => rate.selected )?.meta_data || [];
        let provider = '';

        meta_data.map( ( metaField ) => {
            if ( 'shipping_provider' === metaField.key || '_shipping_provider' === metaField.key ) {
                provider = metaField.value;
            }
        } );

        return [
            packageId,
            provider
        ];
    } ) );
};

export const hasShippingProvider = ( shippingProvider, shippingProviders = null ) => {
    shippingProviders = null === shippingProviders ? getSelectedShippingProviders() : shippingProviders;

    return Object.values( shippingProviders ).includes( shippingProvider );
};

export const hasPickupLocation = () => {
    const checkoutData = getCheckoutData();

    return !!checkoutData.pickup_location;
};

export const getCheckoutData = () => {
    const extensionsData = select( CHECKOUT_STORE_KEY ).getExtensionData();

    const defaultData    = {
        'pickup_location': '',
        'pickup_location_customer_number': ''
    };

    return extensionsData.hasOwnProperty( 'woocommerce-gzd-shipments' ) ? extensionsData['woocommerce-gzd-shipments'] : defaultData;
};

export const getCartData = () => {
    const extensionsData = select( CART_STORE_KEY ).getCartData().extensions;

    const defaultData    = {
        'pickup_location_delivery_available': false,
        'pickup_locations': [],
        'default_pickup_location': '',
        'default_pickup_location_customer_number': '',
    };

    return extensionsData.hasOwnProperty( 'woocommerce-gzd-shipments' ) ? extensionsData['woocommerce-gzd-shipments'] : defaultData;
};