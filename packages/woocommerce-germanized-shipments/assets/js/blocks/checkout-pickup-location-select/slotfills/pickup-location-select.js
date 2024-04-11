import { ExperimentalOrderShippingPackages } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useCallback, useState, useMemo, useRef } from "@wordpress/element";
import { useSelect, useDispatch, select, dispatch } from '@wordpress/data';
import { __, _x, sprintf } from '@wordpress/i18n';
import { CART_STORE_KEY, CHECKOUT_STORE_KEY } from '@woocommerce/block-data';

import {
    ValidatedTextInput,
    ValidatedTextInputHandle,
} from '@woocommerce/blocks-checkout';

import { decodeEntities } from '@wordpress/html-entities';
import { getSelectedShippingProviders, Combobox, hasShippingProvider, getCheckoutData, hasPickupLocation } from '@woocommerceGzdShipments/blocks-checkout';

import './style.scss';

const PickupLocationSelect = ({
    isAvailable,
    pickupLocations,
    currentPickupLocation,
    onChangePickupLocation,
    onChangePickupLocationCustomerNumber,
    currentPickupLocationCustomerNumber
}) => {
    if ( isAvailable ) {
        return (
            <div className="wc-gzd-shipments-pickup-location-delivery">
                <h4>
                    { _x('Not at home? Choose a pickup location', 'shipments', 'woocommerce-germanized') }
                </h4>
                <Combobox
                    options={ pickupLocations }
                    id="pickup-location"
                    key="pickup-location"
                    name="pickup_location"
                    label={ _x( 'Pickup location', 'shipments', 'woocommerce-germanized' ) }
                    errorId="pickup-location"
                    allowReset={ currentPickupLocation ? true : false }
                    value={ currentPickupLocation ? currentPickupLocation.code : '' }
                    required={ false }
                    onChange={ onChangePickupLocation }
                />

                { currentPickupLocation && currentPickupLocation.supports_customer_number && (
                    <ValidatedTextInput
                        key="pickup_location_customer_number"
                        value={ currentPickupLocationCustomerNumber }
                        id="pickup-location-customer-number"
                        label={ currentPickupLocation.customer_number_field_label }
                        name="pickup_location_customer_number"
                        required={ currentPickupLocation.customer_number_is_mandatory }
                        maxLength="20"
                        onChange={ onChangePickupLocationCustomerNumber }
                    />
                ) }
            </div>
        );
    }

    return null;
};

const render = () => {
    const [ currentPickupLocation, setCurrentPickupLocation ] = useState( null );
    const [ isChangingPickupLocation, setIsChangingPickupLocation ] = useState( false );

    const {
        shippingRates,
        cartDataLoaded,
        needsShipping,
        pickupLocations,
        pickupLocationDeliveryAvailable,
        defaultPickupLocation,
        defaultCustomerNumber,
        customerData
    } = useSelect( ( select ) => {
        const isEditor = !! select( 'core/editor' );
        const store = select( CART_STORE_KEY );
        const rates = isEditor
            ? []
            : store.getShippingRates();

        const cartData = store.getCartData();
        const defaultData    = {
            'pickup_location_delivery_available': false,
            'pickup_locations': [],
            'default_pickup_location': '',
            'default_pickup_location_customer_number': '',
        };
        const shipmentsData = cartData.extensions.hasOwnProperty( 'woocommerce-gzd-shipments' ) ? cartData.extensions['woocommerce-gzd-shipments'] : defaultData;

        return {
            shippingRates: rates,
            cartDataLoaded: store.hasFinishedResolution( 'getCartData' ),
            customerData: store.getCustomerData(),
            needsShipping: store.getNeedsShipping(),
            isLoadingRates: store.isCustomerDataUpdating(),
            isSelectingRate: store.isShippingRateBeingSelected(),
            pickupLocationDeliveryAvailable: shipmentsData['pickup_location_delivery_available'],
            pickupLocations: shipmentsData['pickup_locations'],
            defaultPickupLocation: shipmentsData['default_pickup_location'],
            defaultCustomerNumber: shipmentsData['default_pickup_location_customer_number']
        };
    } );

    const shippingAddress = customerData.shippingAddress;
    const { setShippingAddress } = useDispatch( CART_STORE_KEY );
    const checkoutOptions = getCheckoutData();

    const availableLocations = useMemo(
        () =>
            Object.fromEntries( pickupLocations.map( ( location ) => [ location.code, location ] ) ),
        [ pickupLocations ]
    );

    const getLocationByCode = useCallback( ( code ) => {
        return availableLocations.hasOwnProperty( code ) ? availableLocations[ code ] : null;
    }, [ availableLocations ] );

    const locationOptions = useMemo(
        () =>
            pickupLocations.map(
                ( location ) => ( {
                    value: location.code,
                    label: decodeEntities( location.formatted_address ),
                } )
            ),
        [ pickupLocations ]
    );

    const setOption = useCallback( ( option, value ) => {
        checkoutOptions[ option ] = value;

        if ( ! checkoutOptions['pickup_location'] ) {
            checkoutOptions['pickup_location_customer_number'] = '';
        }

        dispatch( CHECKOUT_STORE_KEY ).__internalSetExtensionData( 'woocommerce-gzd-shipments', checkoutOptions );
    }, [ checkoutOptions ] );

    useEffect(() => {
        if ( cartDataLoaded && shippingAddress.address_1 && ! isChangingPickupLocation ) {
            // Reset pickup location on manual shipping address change
            setOption( 'pickup_location', '' );
        }

        setIsChangingPickupLocation( false );
    }, [
        shippingAddress.address_1,
        shippingAddress.postcode,
        shippingAddress.country,
        cartDataLoaded
    ] );

    useEffect(() => {
        if ( cartDataLoaded ) {
            if ( pickupLocationDeliveryAvailable && getLocationByCode( defaultPickupLocation ) ) {
                setOption( 'pickup_location', defaultPickupLocation );
                setOption( 'pickup_location_customer_number', defaultCustomerNumber );
            }
        }
    }, [
        cartDataLoaded
    ] );

    useEffect(() => {
        setIsChangingPickupLocation( true );

        if ( checkoutOptions.pickup_location ) {
            const currentLocation = getLocationByCode( checkoutOptions.pickup_location );

            if ( currentLocation ) {
                setCurrentPickupLocation( () => { return currentLocation } );

                const newShippingAddress = { ...shippingAddress };

                Object.keys( currentLocation.address_replacements ).forEach( addressField => {
                    const value = currentLocation.address_replacements[ addressField ];

                    if ( value ) {
                        newShippingAddress[ addressField ] = value;
                    }
                });

                if ( newShippingAddress !== shippingAddress ) {
                    setShippingAddress( newShippingAddress );
                }
            } else {
                setCurrentPickupLocation( () => { return null } );
            }
        } else {
            setCurrentPickupLocation( () => { return null } );
        }
    }, [
        checkoutOptions.pickup_location
    ] );

    /**
     * Show a notice in case availability changes or location is not available any longer.
     */
    useEffect(() => {
        const currentLocation = getLocationByCode( checkoutOptions.pickup_location );

        if ( ! pickupLocationDeliveryAvailable || ! currentLocation ) {
            let showNotice = checkoutOptions.pickup_location ? true : false;

            setOption( 'pickup_location', '' );

            if ( showNotice ) {
                dispatch( 'core/notices' ).createNotice(
                    'warning',
                    _x( 'Your pickup location chosen is not available any longer. Please review your shipping address.', 'shipments', 'woocommerce-germanized' ),
                    {
                        id: 'wc-gzd-shipments-pickup-location-missing',
                        context: 'wc/checkout/shipping-address',
                    }
                );
            }
        }
    }, [
        pickupLocationDeliveryAvailable,
        locationOptions
    ] );

    const onChangePickupLocation = useCallback( ( pickupLocation ) => {
        if ( availableLocations.hasOwnProperty( pickupLocation ) ) {
            setOption( 'pickup_location', pickupLocation );

            const { removeNotice } = dispatch( 'core/notices' );

            removeNotice( 'wc-gzd-shipments-review-shipping-address', 'wc/checkout/shipping-address' );
            removeNotice( 'wc-gzd-shipments-pickup-location-missing', 'wc/checkout/shipping-address' );
        } else if ( ! pickupLocation ) {
            setOption( 'pickup_location', '' );

            dispatch( 'core/notices' ).createNotice(
                'warning',
                _x( 'Please review your shipping address.', 'shipments', 'woocommerce-germanized' ),
                {
                    id: 'wc-gzd-shipments-review-shipping-address',
                    context: 'wc/checkout/shipping-address',
                }
            );
        } else {
            setOption( 'pickup_location', '' );
        }
    }, [ availableLocations, shippingAddress, checkoutOptions ] );

    const onChangePickupLocationCustomerNumber = useCallback( ( customerNumber ) => {
        setOption( 'pickup_location_customer_number', customerNumber );
    }, [ checkoutOptions ] );

    return (
        <ExperimentalOrderShippingPackages>
            <PickupLocationSelect
                pickupLocations={ locationOptions }
                isAvailable={ pickupLocationDeliveryAvailable && needsShipping }
                currentPickupLocation={ currentPickupLocation }
                onChangePickupLocation={ onChangePickupLocation }
                onChangePickupLocationCustomerNumber={ onChangePickupLocationCustomerNumber }
                currentPickupLocationCustomerNumber={ currentPickupLocation ? checkoutOptions.pickup_location_customer_number : '' }
            />
        </ExperimentalOrderShippingPackages>
    );
};

registerPlugin('woocommerce-gzd-shipments-pickup-location-select', {
    render,
    scope: 'woocommerce-checkout',
});