import { ExperimentalOrderShippingPackages } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useCallback, useState, useMemo, useRef } from "@wordpress/element";
import { useSelect, useDispatch, select, dispatch } from '@wordpress/data';
import { __, _x, sprintf } from '@wordpress/i18n';
import triggerFetch from '@wordpress/api-fetch';
import { CART_STORE_KEY, CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import classnames from 'classnames';
import {
    ValidatedTextInput,
    ValidatedTextInputHandle,
} from '@woocommerce/blocks-checkout';

import { decodeEntities } from '@wordpress/html-entities';
import { getSelectedShippingProviders, Combobox, hasShippingProvider, getCheckoutData, hasPickupLocation, Spinner } from '@woocommerceShiptastic/blocks-checkout';
import { useDebouncedCallback, useDebounce } from 'use-debounce';

import './style.scss';

const PickupLocationDomWatcher = ({
    currentPickupLocation,
    shippingAddress
}) => {
    useEffect(() => {
        let addressFormWrapper = null;
        let blockElement = null;

        if ( domNodeRef.current !== null ) {
            const { ownerDocument } = domNodeRef.current;
            const { defaultView } = ownerDocument;

            blockElement = defaultView.document.getElementsByClassName( 'wp-block-woocommerce-checkout-shipping-address-block' )[0];

            if ( blockElement ) {
                addressFormWrapper = blockElement.getElementsByClassName( 'wc-block-components-address-form' )[0];

                if ( ! addressFormWrapper ) {
                    addressFormWrapper = blockElement.getElementsByClassName( 'wc-block-components-address-form-wrapper' )[0];
                }
            }
        }

        if ( currentPickupLocation ) {
            const hasReplacements = Object.keys( currentPickupLocation.address_replacements ).length > 0;

            if ( blockElement && hasReplacements ) {
                const hasNotice = blockElement.getElementsByClassName( 'managed-by-pickup-location-notice' )[0];

                if ( ! hasNotice ) {
                    const header = blockElement.getElementsByClassName( 'wc-block-components-title' )[0];

                    if ( header ) {
                        header.innerHTML += '<span class="managed-by-pickup-location-notice">' + _x( 'Managed by&nbsp;<a href="#current-pickup-location">pickup location</a>', 'shipments', 'woocommerce-germanized' ) + '</span>';
                    }
                }
            }

            Object.keys( currentPickupLocation.address_replacements ).forEach( addressField => {
                const value = currentPickupLocation.address_replacements[ addressField ];

                if ( addressFormWrapper ) {
                    const fieldWrapper = addressFormWrapper.getElementsByClassName( 'wc-block-components-address-form__' + addressField )[0];

                    if ( fieldWrapper ) {
                        fieldWrapper.classList.add( 'managed-by-pickup-location' );

                        let input = fieldWrapper.getElementsByTagName( 'input' );

                        if ( input.length > 0 ) {
                            input[0].readOnly = true;
                        }
                    }
                }
            });
        } else {
            if ( blockElement ) {
                const hasNotice = blockElement.getElementsByClassName( 'managed-by-pickup-location-notice' )[0];

                if ( hasNotice ) {
                    hasNotice.remove();
                }
            }

            if ( addressFormWrapper ) {
                const fields = addressFormWrapper.getElementsByTagName( 'div' );

                for (let i = 0; i < fields.length; i++) {
                    const item = fields[i];

                    if ( Array.from( item.classList ).includes("managed-by-pickup-location") ) {
                        item.classList.remove( 'managed-by-pickup-location' );
                        let input = item.getElementsByTagName( 'input' );

                        if ( input.length > 0 ) {
                            input[0].readOnly = false;
                        }
                    }
                }
            }
        }
    }, [
        currentPickupLocation,
        shippingAddress
    ] );

    const domNodeRef = useRef( null );

    return (
        <div ref={ domNodeRef }></div>
    );
};

const CurrentPickupLocationHeader = ({
    currentPickupLocation,
    onRemovePickupLocation,
}) => {
    return (
        <h4 className="current-pickup-location" id="current-pickup-location">
            <span className="currently-shipping-to-title">
                {sprintf(_x('Currently shipping to: %s', 'shipments', 'woocommerce-germanized'), currentPickupLocation['label'])}
            </span>
            <a
                className="pickup-location-remove"
                href="#"
                onClick={(e) => {
                    e.preventDefault();
                    onRemovePickupLocation();
                }}
            >
                <svg
                    width="24"
                    height="24"
                    viewBox="0 0 24 24"
                    fill="none"
                    xmlns="http://www.w3.org/2000/svg"
                >
                    <path
                        d="M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z"
                        fill="currentColor"
                    />
                </svg>
            </a>
        </h4>
    );
};

const PickupLocationSelectForm = ({
      isAvailable,
      pickupLocationOptions,
      currentPickupLocation,
      getPickupLocationByCode,
      onChangePickupLocation,
      onRemovePickupLocation,
      onChangePickupLocationCustomerNumber,
      currentPickupLocationCustomerNumber,
      isSearching,
      pickupLocationSearchAddress,
      onChangePickupLocationSearch
}) => {
    const hasSearchResults = pickupLocationOptions.length > 0;

    if (isAvailable) {
        return (
            <div className="wc-shiptastic-pickup-location-delivery">
                {!currentPickupLocation && (
                    <h4>
                        <span
                            className="pickup-location-notice-title">{_x('Not at home? Choose a pickup location', 'shipments', 'woocommerce-germanized')}</span>
                    </h4>
                )}
                {currentPickupLocation && (
                    <CurrentPickupLocationHeader
                        currentPickupLocation={ currentPickupLocation }
                        onRemovePickupLocation={ onRemovePickupLocation }
                    />
                ) }
                <div className="pickup-location-search-fields">
                    <ValidatedTextInput
                        key="pickup_location_search_address"
                        value={pickupLocationSearchAddress['address_1'] ? pickupLocationSearchAddress['address_1'] : ''}
                        id="pickup-location-search-address"
                        label={_x('Address', 'shipments', 'woocommerce-germanized')}
                        name="pickup_location_search_address"
                        onChange={(address) => {
                            onChangePickupLocationSearch({'address_1': address})
                        }}
                    />

                    <ValidatedTextInput
                        key="pickup_location_search_postcode"
                        value={pickupLocationSearchAddress['postcode'] ? pickupLocationSearchAddress['postcode'] : ''}
                        id="pickup-location-search-postcode"
                        label={_x('Postcode', 'shipments', 'woocommerce-germanized')}
                        name="pickup_location_search_postcode"
                        onChange={(postcode) => {
                            onChangePickupLocationSearch({'postcode': postcode})
                        }}
                    />
                </div>

                <div
                    className={ classnames(
                        'pickup-location-search-results',
                        {
                            'is-searching': isSearching,
                        }
                    ) }
                >
                    { isSearching && <Spinner /> }

                    { hasSearchResults && (
                        <Combobox
                            options={ pickupLocationOptions }
                            id="pickup-location-search"
                            key="pickup-location-search"
                            name="pickup_location-search"
                            label={_x('Choose a pickup location', 'shipments', 'woocommerce-germanized')}
                            errorId="pickup-location-search"
                            allowReset={ currentPickupLocation ? true : false }
                            value={ currentPickupLocation ? currentPickupLocation.code : '' }
                            onChange={ ( pickupLocationCode ) => {
                                onChangePickupLocation( pickupLocationCode );
                            } }
                            required={ false }
                        />
                    )}

                    {!hasSearchResults && (
                        <p>{_x('Sorry, we did not find any pickup locations nearby.', 'shipments', 'woocommerce-germanized')}</p>
                    )}
                </div>

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
    const [ isSearchingPickupLocation, setIsSearchingPickupLocation ] = useState( false );
    const [ currentShippingProvider, setCurrentShippingProvider ] = useState( "" );
    const [ pickupLocationSearchResults, setPickupLocationSearchResults ] = useState( null );
    const [ pickupLocationSearchAddress, setPickupLocationSearchAddress ] = useState( {
        'postcode': null,
        'address_1': null
    } );

    const {
        shippingRates,
        cartDataLoaded,
        needsShipping,
        defaultPickupLocations,
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
        const shipmentsData = cartData.extensions.hasOwnProperty( 'woocommerce-shiptastic' ) ? cartData.extensions['woocommerce-shiptastic'] : defaultData;

        return {
            shippingRates: rates,
            cartDataLoaded: store.hasFinishedResolution( 'getCartData' ),
            customerData: store.getCustomerData(),
            needsShipping: store.getNeedsShipping(),
            isLoadingRates: store.isCustomerDataUpdating(),
            isSelectingRate: store.isShippingRateBeingSelected(),
            pickupLocationDeliveryAvailable: shipmentsData['pickup_location_delivery_available'],
            defaultPickupLocations: shipmentsData['pickup_locations'],
            defaultPickupLocation: shipmentsData['default_pickup_location'],
            defaultCustomerNumber: shipmentsData['default_pickup_location_customer_number']
        };
    } );

    const {
        __internalSetUseShippingAsBilling
    } = useDispatch( CHECKOUT_STORE_KEY );

    const shippingAddress = customerData.shippingAddress;
    const { setShippingAddress, updateCustomerData } = useDispatch( CART_STORE_KEY );

    const checkoutOptions = getCheckoutData();

    const isAvailable = pickupLocationDeliveryAvailable && needsShipping;

    const availableLocations = useMemo(
        () => {
            let locations = null == pickupLocationSearchResults ? defaultPickupLocations : pickupLocationSearchResults;

            if ( currentPickupLocation ) {
                locations.push( currentPickupLocation );
            }

            return locations;
        },
        [
            pickupLocationSearchResults,
            defaultPickupLocations,
            currentPickupLocation
        ]
    );

    const availableLocationsByCode = useMemo(
        () =>
            Object.fromEntries( availableLocations.map( ( location ) => [ location.code, location ] ) ),
        [ availableLocations ]
    );

    const getLocationByCode = useCallback( ( code ) => {
        return availableLocationsByCode.hasOwnProperty( code ) ? availableLocationsByCode[ code ] : null;
    }, [ availableLocationsByCode ] );

    const pickupLocationOptions = useMemo(
        () => {
            const locationCodes = [];
            let newLocations = [];

            for ( const location of availableLocations ) {
                if ( ! locationCodes.includes( location.code ) ) {
                    newLocations.push({
                        value: location.code,
                        label: decodeEntities(location.formatted_address),
                    });
                }

                locationCodes.push( location.code );
            }

            return newLocations;
        },
        [ availableLocations ]
    );

    const formattedPickupLocationSearchAddress = useMemo(
        () => {
            let formattedAddress = {
                'address_1': shippingAddress['address_1'],
                'postcode': shippingAddress['postcode'],
            }

            if ( currentPickupLocation && formattedAddress['address_1'] === currentPickupLocation['label'] ) {
                formattedAddress['address_1'] = '';
            }

            if ( null != pickupLocationSearchAddress['address_1'] ) {
                formattedAddress['address_1'] = pickupLocationSearchAddress['address_1'];
            }
            if ( null != pickupLocationSearchAddress['postcode'] ) {
                formattedAddress['postcode'] = pickupLocationSearchAddress['postcode'];
            }

            return formattedAddress;
        },
        [
            shippingAddress,
            pickupLocationSearchAddress,
            currentPickupLocation
        ]
    );

    const setOptions = useCallback( ( values ) => {
        const updatedOptions = { ...checkoutOptions, ...values };

        if ( ! updatedOptions['pickup_location'] ) {
            updatedOptions['pickup_location_customer_number'] = '';
        }

        dispatch( CHECKOUT_STORE_KEY ).setExtensionData( 'woocommerce-shiptastic', updatedOptions );
    }, [ checkoutOptions ] );

    const setOption = useCallback( ( option, value ) => {
        setOptions( { [option]: value } );
    }, [ checkoutOptions, setOptions ] );

    const onRemovePickupLocation = useCallback( () => {
        setOption( 'pickup_location', '' );
        dispatch( 'core/notices' ).createNotice(
            'warning',
            _x( 'Please review your shipping address.', 'shipments', 'woocommerce-germanized' ),
            {
                id: 'wc-shiptastic-review-shipping-address',
                context: 'wc/checkout/shipping-address',
            }
        );
    }, [
        availableLocationsByCode,
        shippingAddress,
        checkoutOptions
    ] );

    const onChangePickupLocation = useCallback( ( pickupLocation ) => {
        if ( availableLocationsByCode.hasOwnProperty( pickupLocation ) ) {
            setOption( 'pickup_location', pickupLocation );
            setPickupLocationSearchAddress( { 'address_1': '' } );

            __internalSetUseShippingAsBilling( false );

            const { removeNotice } = dispatch( 'core/notices' );

            removeNotice( 'wc-shiptastic-review-shipping-address', 'wc/checkout/shipping-address' );
            removeNotice( 'wc-shiptastic-pickup-location-missing', 'wc/checkout/shipping-address' );
        } else if ( ! pickupLocation ) {
            onRemovePickupLocation();
        } else {
            setOption( 'pickup_location', '' );
        }
    }, [
        availableLocationsByCode,
        setPickupLocationSearchAddress,
        shippingAddress,
        checkoutOptions
    ] );

    const onChangePickupLocationCustomerNumber = useCallback( ( customerNumber ) => {
        setOption( 'pickup_location_customer_number', customerNumber );
    }, [ checkoutOptions ] );

    useEffect(() => {
        if ( pickupLocationDeliveryAvailable && getLocationByCode( defaultPickupLocation ) ) {
            setOptions( {
                'pickup_location': defaultPickupLocation,
                'pickup_location_customer_number': defaultCustomerNumber
            } );
        }
    }, [
        defaultPickupLocation
    ] );

    useEffect(() => {
        setIsChangingPickupLocation( () => { return true } );

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
                    setShippingAddress( shippingAddress );
                    // Prevent overridden data from other hooks/extensions by persisting customer address
                    updateCustomerData( {
                        'shipping_address': newShippingAddress,
                    }, false );
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

            if ( showNotice ) {
                setOption( 'pickup_location', '' );

                dispatch( 'core/notices' ).createNotice(
                    'warning',
                    _x( 'Your pickup location chosen is not available any longer. Please review your shipping address.', 'shipments', 'woocommerce-germanized' ),
                    {
                        id: 'wc-shiptastic-pickup-location-missing',
                        context: 'wc/checkout/shipping-address',
                    }
                );
            }
        }
    }, [
        pickupLocationDeliveryAvailable
    ] );

    const searchPickupLocations = useCallback( ( address ) => {
        const fetchData = {
            address: address,
            provider: currentShippingProvider
        };

        triggerFetch( {
            path: '/wc/store/v1/cart/search-pickup-locations',
            method: 'POST',
            data: fetchData,
            cache: 'no-store',
            parse: false,
        } ).then( ( fetchResponse ) => {
            // Update nonce.
            triggerFetch.setNonce( fetchResponse.headers );

            // Handle response.
            fetchResponse.json().then( function ( response ) {
                setPickupLocationSearchResults( response.pickup_locations );
                setIsSearchingPickupLocation( false );
            } );
        } ).catch( ( error ) => {
        } );
    }, [
        currentShippingProvider,
        setPickupLocationSearchResults,
        setIsSearchingPickupLocation
    ] );

    const onSearchPickupLocations = useDebouncedCallback(
        ( address ) => {
            searchPickupLocations(address);
    }, 1000 );

    const onChangePickupLocationSearch = useCallback( ( searchAddress ) => {
        setPickupLocationSearchAddress( ( oldAddress ) => {
            let newSearchAddress = { ...oldAddress, ...searchAddress };

            if ( null == newSearchAddress['address_1'] ) {
                newSearchAddress['address_1'] = shippingAddress['address_1'];
            }
            if ( null == newSearchAddress['postcode'] ) {
                newSearchAddress['postcode'] = shippingAddress['postcode'];
            }

            return newSearchAddress;
        } );
    }, [
        setPickupLocationSearchAddress,
        pickupLocationSearchAddress,
        shippingAddress
    ] );

    useEffect(() => {
        if ( isAvailable && pickupLocationSearchAddress['postcode'] ) {
            setIsSearchingPickupLocation( true );
            const searchAddress = {
                ...pickupLocationSearchAddress,
                'country': shippingAddress['country'],
                'city': shippingAddress['city'],
                'state': shippingAddress['state'],
            }

            onSearchPickupLocations( searchAddress );
        }
    }, [
        isAvailable,
        shippingAddress,
        pickupLocationSearchAddress,
        setIsSearchingPickupLocation
    ] );

    useEffect(() => {
        const currentShippingProviders = getSelectedShippingProviders( shippingRates );
        const newShippingProvider = Object.keys( currentShippingProviders ).length > 0 ? currentShippingProviders[0] : "";

        if ( newShippingProvider !== currentShippingProvider ) {
            setCurrentShippingProvider( ( oldProvider ) => {
                if ( oldProvider !== "" && newShippingProvider !== oldProvider ) {
                    setPickupLocationSearchResults( null );

                    if ( currentPickupLocation ) {
                        onRemovePickupLocation();
                    }
                }

                return newShippingProvider;
            } );
        }
    }, [
        shippingRates
    ] );

    const domNodeRef = useRef( null );

    return (
        <ExperimentalOrderShippingPackages>
            <PickupLocationSelectForm
                pickupLocationOptions={pickupLocationOptions}
                getPickupLocationByCode={getLocationByCode}
                isAvailable={isAvailable}
                isSearching={ isSearchingPickupLocation }
                onRemovePickupLocation={onRemovePickupLocation}
                currentPickupLocation={currentPickupLocation}
                onChangePickupLocation={onChangePickupLocation}
                onChangePickupLocationSearch={onChangePickupLocationSearch}
                pickupLocationSearchAddress={formattedPickupLocationSearchAddress}
                onChangePickupLocationCustomerNumber={onChangePickupLocationCustomerNumber}
                currentPickupLocationCustomerNumber={currentPickupLocation ? checkoutOptions.pickup_location_customer_number : ''}
            />
            <PickupLocationDomWatcher
                currentPickupLocation={ currentPickupLocation }
                shippingAddress={ shippingAddress }
            />
        </ExperimentalOrderShippingPackages>
    );
};

registerPlugin('woocommerce-shiptastic-pickup-location-select', {
    render,
    scope: 'woocommerce-checkout',
});