/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { getPaymentMethodData } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';
import { useEffect, useMemo, useRef } from '@wordpress/element';
import {
    ValidatedTextInput,
    ValidatedTextInputHandle,
} from '@woocommerce/blocks-checkout';
import { useDispatch, useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY, PAYMENT_STORE_KEY } from '@woocommerce/block-data';

const settings = getPaymentMethodData( 'direct-debit', {} );
const defaultLabel = __( 'Direct debit', 'woocommerce-germanized' );
const label = decodeEntities( settings?.title || '' ) || defaultLabel;

/**
 * Content component
 */
const Content = () => {
    return decodeEntities( settings.description || '' );
};

/**
 * Label component
 *
 * @param {*} props Props from payment API.
 */
const Label = ( props ) => {
    const { PaymentMethodLabel } = props.components;
    return <PaymentMethodLabel text={ label } />;
};

const DirectDebitForm = ( {
  billing,
  eventRegistration,
  emitResponse,
  components
} ) => {
    const {
        values
    } = useSelect( ( select ) => {
        const store = select( PAYMENT_STORE_KEY );

        return {
            values: store.getPaymentMethodData(),
        };
    }, [] );

    const {
        __internalSetPaymentMethodData
    } = useDispatch( PAYMENT_STORE_KEY );

    const { extensionData } = useSelect(
        ( select ) => {
            const store = select( CHECKOUT_STORE_KEY );
            const extensionData = store.getExtensionData();

            return {
                extensionData: extensionData.hasOwnProperty( 'woocommerce-germanized' ) ? extensionData['woocommerce-germanized'] : {},
            };
        }
    );

    const { __internalSetExtensionData } = useDispatch( CHECKOUT_STORE_KEY );

    const fields = [
        {
            key: 'direct_debit_account_holder',
            label: __( 'Account holder', 'woocommerce-germanized' ),
            type: 'text',
            required: true,
            errorMessage: '',
            autocomplete: '',
            autocapitalize: '',
        },
        {
            key: 'direct_debit_account_iban',
            label: __( 'IBAN', 'woocommerce-germanized' ),
            type: 'text',
            required: true,
            errorMessage: '',
            autocomplete: '',
            autocapitalize: '',
        },
        {
            key: 'direct_debit_account_bic',
            label: __( 'BIC/SWIFT', 'woocommerce-germanized' ),
            type: 'text',
            required: true,
            errorMessage: '',
            autocomplete: '',
            autocapitalize: '',
        }
    ];

    const fieldsRef = useRef( {} );

    return (
        <div className="wc-gzd-direct-debit-fields">
            { fields.map( ( field ) => {
                const fieldProps = {
                    id: `${ field.key }`,
                    errorId: `${ field.key }`,
                    label: field.required ? field.label : field.optionalLabel,
                    autoCapitalize: field.autocapitalize,
                    autoComplete: field.autocomplete,
                    errorMessage: field.errorMessage,
                    required: field.required,
                    className: `wc-gzd-direct-debit__${ field.key }`,
                };

                const value = values.hasOwnProperty( field.key ) ? values[ field.key ] : '';

                return (
                    <ValidatedTextInput
                        key={ field.key }
                        ref={ ( el ) =>
                            ( fieldsRef.current[ field.key ] = el )
                        }
                        { ...fieldProps }
                        value={ value }
                        onChange={ ( newValue ) => {
                            __internalSetPaymentMethodData({
                                ...values,
                                [ field.key ]: newValue,
                            });

                            return newValue;
                        } }
                        customFormatter={ ( value ) => {
                            if ( 'direct_debit_account_bic' === field.key ) {
                                value = value.toUpperCase();
                                value = value.replace( /[^\w]/g, '' );
                            } else if ( 'direct_debit_account_iban' === field.key ) {
                                value = value.toUpperCase();
                                value = value.replace( /[^\w\s]/g, '' );
                            }

                            return value;
                        } }
                        customValidation={ ( inputObject ) => {
                            if ( 'direct_debit_account_bic' === field.key ) {
                                const regSWIFT = /^([a-zA-Z]){4}([a-zA-Z]){2}([0-9a-zA-Z]){2}([0-9a-zA-Z]{3})?$/;

                                if ( ! regSWIFT.test( inputObject.value ) ) {
                                    inputObject.setCustomValidity(
                                        __(
                                            'Please enter a valid BIC/SWIFT',
                                            'woocommerce-germanized'
                                        )
                                    );

                                    return false;
                                }
                            } else if ( 'direct_debit_account_iban' === field.key ) {
                                const mod97 = function( string ) {
                                    let checksum = string.slice( 0, 2 ), fragment;

                                    for ( let offset = 2; offset < string.length; offset += 7 ) {
                                        fragment = String( checksum ) + string.substring( offset, offset + 7 );
                                        checksum = parseInt( fragment, 10 ) % 97;
                                    }

                                    return checksum;
                                };

                                const codeLengths = {
                                    AD: 24, AE: 23, AL: 28, AT: 20, AZ: 28, BA: 20, BE: 16, BG: 22, BH: 22, BR: 29, CH: 21, CR: 21, CY: 28, CZ: 24,
                                    DE: 22, DK: 18, DO: 28, EE: 20, ES: 24, LC: 30, FI: 18, FO: 18, FR: 27, GB: 22, GI: 23, GL: 18, GR: 27, GT: 28,
                                    HR: 21, HU: 28, IE: 22, IL: 23, IS: 26, IT: 27, JO: 30, KW: 30, KZ: 20, LB: 28, LI: 21, LT: 20, LU: 20, LV: 21,
                                    MC: 27, MD: 24, ME: 22, MK: 19, MR: 27, MT: 31, MU: 30, NL: 18, NO: 15, PK: 24, PL: 28, PS: 29, PT: 25, QA: 29,
                                    RO: 24, RS: 22, SA: 24, SE: 24, SI: 19, SK: 24, SM: 27, TN: 24, TR: 26
                                };

                                const iban = inputObject.value.toUpperCase().replace(/[^A-Z0-9]/g, '' );
                                const code = iban.match( /^([A-Z]{2})(\d{2})([A-Z\d]+)$/ );
                                let digits, isValid = true;

                                if ( ! code || iban.length !== codeLengths[ code[1] ] ) {
                                    isValid = false;
                                } else {
                                    digits = ( code[3] + code[1] + code[2] ).replace(/[A-Z]/g, ( letter) => {
                                        return letter.charCodeAt(0) - 55;
                                    });

                                    isValid = mod97( digits ) === 1;
                                }

                                if ( ! isValid ) {
                                    inputObject.setCustomValidity(
                                        __(
                                            'Please enter a valid IBAN',
                                            'woocommerce-germanized'
                                        )
                                    );

                                    return false;
                                }
                            }

                            return true;
                        } }
                    />
                )
            } ) }
        </div>
    );
}

/**
 * Cheque payment method config object.
 */
const DirectDebitPaymentMethod = {
    name: 'direct-debit',
    label: <Label />,
    content: <DirectDebitForm />,
    edit: <DirectDebitForm />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings?.supports ?? [],
    },
};

registerPaymentMethod( DirectDebitPaymentMethod );
