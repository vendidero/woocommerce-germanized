import { ExperimentalOrderShippingPackages } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useCallback, useState } from "@wordpress/element";
import { useSelect, useDispatch, select, dispatch } from '@wordpress/data';
import { extensionCartUpdate } from '@woocommerce/blocks-checkout';
import classnames from 'classnames';
import { getSetting } from '@woocommerce/settings';
import { __, _x, sprintf } from '@wordpress/i18n';
import { SVG } from '@wordpress/components';
import _ from 'lodash';
import { CART_STORE_KEY, CHECKOUT_STORE_KEY, PAYMENT_STORE_KEY, VALIDATION_STORE_KEY } from '@woocommerce/block-data';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useDebouncedCallback, useDebounce } from 'use-debounce';

import {
    __experimentalRadio as Radio,
    __experimentalRadioGroup as RadioGroup,
} from 'wordpress-components';

import {
    ValidatedTextInput,
    ValidatedTextInputHandle,
} from '@woocommerce/blocks-checkout';

import './style.scss';
import {
    getSelectedShippingProviders,
    hasShippingProvider,
    hasPickupLocation,
    RadioControlAccordion,
    FormattedMonetaryAmount
} from '@woocommerceShiptastic/blocks-checkout';

const getDhlCheckoutData = ( checkoutData ) => {
    return checkoutData.hasOwnProperty( 'woocommerce-stc-dhl' ) ? checkoutData['woocommerce-stc-dhl'] : {};
};

const setDhlCheckoutData = ( checkoutData ) => {
    dispatch( CHECKOUT_STORE_KEY ).__internalSetExtensionData( 'woocommerce-stc-dhl', checkoutData );
};

const DhlPreferredDaySelect = ({
    preferredDays,
    setPreferredOption,
    preferredOptions,
    preferredDayCost,
    currency
}) => {
    const preferredDay = preferredOptions.hasOwnProperty( 'preferred_day' ) ? preferredOptions['preferred_day'] : '';
    const costValue = parseInt( preferredDayCost, 10 );

    return (
        <div className="wc-stc-dhl-preferred-days">
            <p className="wc-block-components-checkout-step__description">
                { _x( 'Choose a delivery day', 'dhl', 'woocommerce-germanized' ) }

                { costValue > 0 &&
                    <span className="dhl-cost"> (+ <FormattedMonetaryAmount
                            currency={ currency }
                            value={ costValue }
                        />)
                    </span>
                }
            </p>
            <div className="wc-stc-dhl-preferred-day-select">
                { preferredDays.map( ( preferred ) => {
                    const checked = preferredDay === preferred.date;

                    return (
                        <Radio
                            value={ preferred.date }
                            key={ preferred.date }
                            onClick={ ( event ) => {
                                setPreferredOption( 'preferred_day', preferred.date );
                            } }
                            checked={ checked }
                            className={ classnames(
                                `wc-stc-dhl-preferred-day`,
                                {
                                    active: checked
                                }
                            ) }
                        >
                        <span className="inner">
                            <span className="day">
                            { preferred.day }
                            </span>
                            <span className="week-day">
                                { preferred.week_day }
                            </span>
                        </span>
                        </Radio>
                    );
                } ) }
            </div>
        </div>
    );
};

const DhlPreferredLocation = ( props ) => {
    const {
        setPreferredOption,
        preferredOptions,
    } = props;

    const location = preferredOptions.hasOwnProperty( 'preferred_location' ) ? preferredOptions['preferred_location'] : '';

    return (
        <>
            { _x( 'Choose a weather-protected and non-visible place on your property, where we can deposit the parcel in your absence.', 'dhl', 'woocommerce-germanized' ) }

            <ValidatedTextInput
                key="dhl-location"
                value={ location }
                id="dhl-location"
                label={ _x( "e.g. Garage, Terrace", 'dhl', 'woocommerce-germanized' ) }
                name="dhl_location"
                required={ true }
                maxLength="80"
                onChange={ ( newValue ) => {
                    setPreferredOption( 'preferred_location', newValue );
                } }
            />
        </>
    )
}

const DhlPreferredNeighbor = ( props ) => {
    const {
        setPreferredOption,
        preferredOptions,
    } = props;

    const neighborName = preferredOptions.hasOwnProperty( 'preferred_location_neighbor_name' ) ? preferredOptions['preferred_location_neighbor_name'] : '';
    const neighborAddress = preferredOptions.hasOwnProperty( 'preferred_location_neighbor_address' ) ? preferredOptions['preferred_location_neighbor_address'] : '';

    return (
        <>
            { _x( 'Determine a person in your immediate neighborhood whom we can hand out your parcel in your absence. This person should live in the same building, directly opposite or next door.', 'dhl', 'woocommerce-germanized' ) }

            <ValidatedTextInput
                key="dhl-preferred-neighbor-name"
                value={ neighborName }
                id="dhl-preferred-neighbor-name"
                label={ _x( "First name, last name of neighbor", 'dhl', 'woocommerce-germanized' ) }
                required={ true }
                maxLength="25"
                onChange={ ( newValue ) => {
                    setPreferredOption( 'preferred_location_neighbor_name', newValue );
                } }
            />
            <ValidatedTextInput
                key="dhl-preferred-neighbor-address"
                value={ neighborAddress }
                id="dhl-preferred-neighbor-address"
                required={ true }
                maxLength="55"
                label={ _x( "Street, number, postal code, city", 'dhl', 'woocommerce-germanized' ) }
                onChange={ ( newValue ) => {
                    setPreferredOption( 'preferred_location_neighbor_address', newValue );
                } }
            />
        </>

    )
}

const DhlPreferredLocationSelect = ( props ) => {
    const {
        setPreferredOption,
        preferredOptions,
        preferredNeighborEnabled,
        preferredLocationEnabled
    } = props;

    const preferredLocationType = preferredOptions.hasOwnProperty( 'preferred_location_type' ) ? preferredOptions['preferred_location_type'] : '';

    const options = [
        {
            value: '',
            label: _x( 'None', 'dhl location context', 'woocommerce-germanized' ),
            content: '',
        },
        preferredLocationEnabled ?
        {
            value: 'place',
            label: _x( 'Drop-off location', 'dhl', 'woocommerce-germanized' ),
            content: (
                <DhlPreferredLocation { ...props } />
            ),
        } : {},
        preferredNeighborEnabled ?
        {
            value: 'neighbor',
            label: _x( 'Neighbor', 'dhl', 'woocommerce-germanized' ),
            content: (
                <DhlPreferredNeighbor { ...props } />
            ),
        } : {},
    ].filter( value => Object.keys( value ).length !== 0 );

    return (
        <div className="wc-stc-dhl-preferred-location">
            <p className="wc-block-components-checkout-step__description">{ _x( 'Choose a preferred location', 'dhl', 'woocommerce-germanized' ) }</p>
            <RadioControlAccordion
                id={ 'wc-stc-dhl-preferred-location-options' }
                selected={ preferredLocationType }
                onChange={ ( value ) => {
                    setPreferredOption( 'preferred_location_type', value );
                } }
                options={ options }
            />
        </div>
    );
};

const DhlCdpOptions = (
    props
) => {
    const { preferredOptions, setPreferredOption, homeDeliveryCost, currency } = props;
    const preferredDeliveryType = preferredOptions.hasOwnProperty( 'preferred_delivery_type' ) ? preferredOptions['preferred_delivery_type'] : '';
    const costValue = parseInt( homeDeliveryCost, 10 );

    const options = [
        {
            value: 'cdp',
            label: _x( 'Shop', 'dhl', 'woocommerce-germanized' ),
            content: (
                _x( 'Delivery to nearby parcel store/locker or to the front door.', 'dhl', 'woocommerce-germanized' )
            ),
            secondaryLabel: costValue > 0 ? (
                <FormattedMonetaryAmount
                    currency={ currency }
                    value={ 0 }
                />
            ) : ''
        },
        {
            value: 'home',
            label: _x( 'Home Delivery', 'dhl', 'woocommerce-germanized' ),
            content: (
                _x( 'Delivery usually to the front door.', 'dhl', 'woocommerce-germanized' )
            ),
            secondaryLabel: costValue > 0 ? (
                <FormattedMonetaryAmount
                    currency={ currency }
                    value={ costValue }
                />
            ) : ''
        }
    ];

    return (
        <div className="wc-stc-dhl-preferred-delivery">
            <p className="wc-block-components-checkout-step__description">{ _x( 'Choose a delivery type', 'dhl', 'woocommerce-germanized' ) }</p>

            <RadioControlAccordion
                id={ 'wc-stc-dhl-preferred-delivery-types' }
                selected={ preferredDeliveryType }
                onChange={ ( value ) => {
                    setPreferredOption( 'preferred_delivery_type', value );
                } }
                options={ options }
            />
        </div>
    );
};

const DhlPreferredOptions = (
    props
) => {
    const { preferredDayEnabled, preferredLocationEnabled, preferredNeighborEnabled } = props;

    return (
        <div>
            { preferredDayEnabled ? (
                <DhlPreferredDaySelect { ...props } />
            ) : '' }
            { preferredLocationEnabled || preferredNeighborEnabled ? (
                <DhlPreferredLocationSelect { ...props } />
            ) : '' }
        </div>
    );
};

const DhlPreferredWrapper = ({
    props,
    children
}) => {
    return (
        <div className="wc-stc-checkout-dhl">
            <h4 className="wc-stc-checkout-dhl-title">
                <span className="dhl-title">{ _x( 'DHL Preferred Delivery. Delivered just as you wish.', 'dhl', 'woocommerce-germanized' ) }</span>
                <SVG className="dhl-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 175.748 38.786">
                    <path d="M175.748 0v38.786H0V0z" fill="#fecc00"/>
                    <path d="M56.665 16.206c-.768 1.04-2.053 2.848-2.835 3.904-.397.537-1.114 1.512 1.263 1.512h12.515s2.017-2.744 3.708-5.039c2.3-3.122.199-9.618-8.024-9.618H30.908l-5.615 7.629h30.603c1.545 0 1.524.588.769 1.612zm-9.194 7.298c-2.377 0-1.66-.977-1.263-1.514.782-1.056 2.088-2.845 2.856-3.885.756-1.024.776-1.612-.771-1.612H34.297L23.02 31.819h27.501c9.083 0 14.14-6.178 15.699-8.314l-18.749-.001zm17.89 8.315h16.133l6.116-8.316-16.131.002c-.005-.001-6.118 8.314-6.118 8.314zm41.625-24.854l-6.188 8.405h-7.2l6.185-8.405H83.655l-10.79 14.657h39.46l10.787-14.657zM88.694 31.819h16.127l6.119-8.314H94.813c-.006-.001-6.119 8.314-6.119 8.314zM0 26.784v1.766h22.468l1.298-1.766zm26.181-3.28H0v1.764h24.88zM0 31.819h20.061l1.292-1.756H0zm152.072-3.27h23.676v-1.766h-22.376zm-2.405 3.27h26.081v-1.756h-24.79zm6.116-8.315l-1.297 1.766h21.262v-1.766zm-21.124-1.882l10.789-14.657h-17.081c-.006 0-10.797 14.657-10.797 14.657zm-18.472 1.882s-1.179 1.611-1.752 2.387c-2.025 2.736-.234 5.928 6.376 5.928h25.901l6.119-8.314h-36.644z" fill="#d50029"/>
                </SVG>
            </h4>
            { children }
        </div>
    );
};

const DhlPreferredDeliveryOptions = ({
    extensions,
    cart,
    components
}) => {
    const {
        shippingRates,
        needsShipping,
        isLoadingRates,
        isSelectingRate,
    } = useSelect( ( select ) => {
        const isEditor = !! select( 'core/editor' );
        const store = select( CART_STORE_KEY );
        const rates = isEditor
            ? []
            : store.getShippingRates();
        return {
            shippingRates: rates,
            needsShipping: store.getNeedsShipping(),
            isLoadingRates: store.isCustomerDataUpdating(),
            isSelectingRate: store.isShippingRateBeingSelected(),
        };
    } );

    const [ needsFeeUpdate, setNeedsFeeUpdate ] = useState( false );
    const shippingProviders = getSelectedShippingProviders( shippingRates );
    const hasDhlProvider = hasShippingProvider( 'dhl', shippingProviders );
    const { __internalSetExtensionData } = useDispatch( CHECKOUT_STORE_KEY );

    const { isCustomerDataUpdating } = useSelect(
        ( select ) => {
            return {
                isCustomerDataUpdating: select( CART_STORE_KEY ).isCustomerDataUpdating(),
            };
        }
    );

    const {
        activePaymentMethod
    } = useSelect( ( select ) => {
        const store = select( PAYMENT_STORE_KEY );

        return {
            activePaymentMethod: store.getActivePaymentMethod()
        };
    }, [] );

    const { preferredOptions } = useSelect( ( select ) => {
        const store = select( CHECKOUT_STORE_KEY );

        return {
            preferredOptions: getDhlCheckoutData( store.getExtensionData() )
        };
    } );

    const dhlOptions            = getDhlCheckoutData( extensions );
    const preferredDayCost = parseInt( dhlOptions.hasOwnProperty( 'preferred_day_cost' ) ? dhlOptions['preferred_day_cost'] : 0, 10 );
    const homeDeliveryCost = parseInt( dhlOptions.hasOwnProperty( 'preferred_home_delivery_cost' ) ? dhlOptions['preferred_home_delivery_cost'] : 0, 10 );

    const setDhlOption = ( option, value, updateCart = true ) => {
        const checkoutOptions = { ...preferredOptions };
        checkoutOptions[ option ] = value;

        setDhlCheckoutData( checkoutOptions );

        if ( updateCart ) {
            if ( 'preferred_day' === option && preferredDayCost > 0 ) {
                extensionCartUpdate( {
                    namespace: 'woocommerce-stc-dhl-checkout-fees',
                    data: checkoutOptions,
                } );
            } else if ( 'preferred_delivery_type' === option && homeDeliveryCost > 0 ) {
                extensionCartUpdate( {
                    namespace: 'woocommerce-stc-dhl-checkout-fees',
                    data: checkoutOptions,
                } );
            }
        }
    };

    const setPreferredOption = useCallback(
        ( option, value ) => {
            setDhlOption( option, value );
        },
        [ setDhlOption, preferredOptions ]
    );

    const totalsCurrency = getCurrencyFromPriceResponse( cart.cartTotals );
    const excludedPaymentGateways = getSetting( 'dhlExcludedPaymentGateways', [] );
    const isGatewayExcluded = _.includes( excludedPaymentGateways, activePaymentMethod );

    const preferredDayEnabled = dhlOptions.preferred_day_enabled && dhlOptions.preferred_days.length > 0;
    const preferredLocationEnabled = dhlOptions.preferred_location_enabled && ! hasPickupLocation();
    const preferredNeighborEnabled = dhlOptions.preferred_neighbor_enabled && ! hasPickupLocation();
    const preferredDeliveryTypeEnabled = dhlOptions.preferred_delivery_type_enabled;
    const cdpCountries = getSetting( 'dhlCdpCountries', [] );

    const preferredOptionsAvailable = 'DE' === cart.shippingAddress.country && ( preferredDayEnabled || preferredNeighborEnabled || preferredLocationEnabled );
    const isCdpAvailable = preferredDeliveryTypeEnabled && _.includes( cdpCountries, cart.shippingAddress.country );
    const isFeeAvailable = hasDhlProvider && ! isGatewayExcluded && ( ( 'DE' === cart.shippingAddress.country && preferredDayEnabled && preferredDayCost > 0 ) || ( isCdpAvailable && homeDeliveryCost > 0 ) );

    const isAvailable = hasDhlProvider && ! isGatewayExcluded && ( isCdpAvailable || preferredOptionsAvailable );

    useEffect(() => {
        if ( isAvailable ) {
            const currentData = getDhlCheckoutData( select( CHECKOUT_STORE_KEY ).getExtensionData() );

            if ( ! preferredOptionsAvailable ) {
                // Reset data
                const checkoutOptions = Object.keys( currentData ).reduce(
                    ( accumulator, current ) => {
                        accumulator[current] = '';
                        return accumulator
                    }, {} );

                if ( isCdpAvailable ) {
                    checkoutOptions['preferred_delivery_type'] = dhlOptions['preferred_delivery_type'];
                }

                setDhlCheckoutData( checkoutOptions );
            } else {
                const checkoutOptions = { ...currentData,
                    'preferred_day': preferredOptionsAvailable && preferredDayEnabled ? dhlOptions['preferred_day'] : '',
                    'preferred_delivery_type': '',
                };

                setDhlCheckoutData( checkoutOptions );
            }
        }
    }, [
        preferredOptionsAvailable,
        isCdpAvailable,
        __internalSetExtensionData
    ] );

    // Debounce re-disable since disabling process itself will incur additional mutations which should be ignored.
    const maybeUpdateFee = useDebouncedCallback( () => {
        const currentData = getDhlCheckoutData( select( CHECKOUT_STORE_KEY ).getExtensionData() );

        if ( ! isCustomerDataUpdating ) {
            extensionCartUpdate( {
                namespace: 'woocommerce-stc-dhl-checkout-fees',
                data: currentData,
            } );

            setNeedsFeeUpdate( () => { return false } );
        }
    }, 2000 );

    useEffect(() => {
        if ( isAvailable ) {
            const currentData = getDhlCheckoutData( select( CHECKOUT_STORE_KEY ).getExtensionData() );

            const checkoutOptions = { ...currentData,
                'preferred_day': preferredOptionsAvailable && preferredDayEnabled ? dhlOptions['preferred_day'] : '',
                'preferred_delivery_type': isCdpAvailable ? dhlOptions['preferred_delivery_type'] : '',
            };

            setDhlCheckoutData( checkoutOptions );
        } else {
            const currentData = getDhlCheckoutData( select( CHECKOUT_STORE_KEY ).getExtensionData() );

            // Reset data
            const checkoutOptions = Object.keys( currentData ).reduce(
                ( accumulator, current ) => {
                    accumulator[current] = '';
                    return accumulator
                }, {} );

            setDhlCheckoutData( checkoutOptions );
        }
    }, [
        isAvailable
    ] );

    useEffect(() => {
        setNeedsFeeUpdate( () => { return true } );
    }, [ isFeeAvailable ] );

    /**
     * Maybe delay the extensionCartUpdate in case shipping data is invalid
     * to prevent (dirty data) race conditions with the update-customer call as
     * the update-customer call will only be triggered when the data is errorless.
     *
     * Be careful: Calling the extensionCartUpdate overrides the current checkout data
     * with data from the customer session. If that data has not yet been persisted, the current
     * checkout data will be replaced with old data.
     */
    useEffect(() => {
        if ( needsFeeUpdate ) {
            maybeUpdateFee.cancel();

            const validationStore = select( VALIDATION_STORE_KEY );
            const invalidProps = [
                ...Object.keys( cart.shippingAddress ).filter( ( key ) => {
                    return (
                        validationStore.getValidationError( 'shipping_' + key ) !== undefined
                    );
                } ),
            ].filter( Boolean );

            if ( invalidProps.length === 0 ) {
                // No errors found, lets update.
                maybeUpdateFee();
            }
        }

        return () => {
            maybeUpdateFee.cancel();
        };
    }, [
        needsFeeUpdate,
        setNeedsFeeUpdate,
        cart.shippingAddress,
        isAvailable,
        maybeUpdateFee
    ] );

    if ( ! isAvailable || ( ! preferredOptionsAvailable && ! isCdpAvailable ) ) {
        return null;
    }

    return (
        <div className="wc-stc-shipping-provider-options">
            <DhlPreferredWrapper>
                { preferredOptionsAvailable &&
                    <DhlPreferredOptions
                        preferredDayEnabled={ preferredDayEnabled }
                        preferredDays={ dhlOptions.preferred_days }
                        setPreferredOption={ setPreferredOption }
                        currency={ totalsCurrency }
                        preferredDayCost={ preferredDayCost }
                        preferredOptions={ preferredOptions }
                        preferredLocationEnabled={ preferredLocationEnabled }
                        preferredNeighborEnabled={ preferredNeighborEnabled }
                    />
                }
                { isCdpAvailable &&
                    <DhlCdpOptions
                        setPreferredOption={ setPreferredOption }
                        preferredOptions={ preferredOptions }
                        homeDeliveryCost={ homeDeliveryCost }
                        currency={ totalsCurrency }
                    />
                }
            </DhlPreferredWrapper>
        </div>
    );
};

const render = () => {
    return (
        <ExperimentalOrderShippingPackages>
            <DhlPreferredDeliveryOptions />
        </ExperimentalOrderShippingPackages>
    );
};

registerPlugin( 'woocommerce-stc-dhl-preferred-services', {
    render,
    scope: 'woocommerce-checkout',
} );