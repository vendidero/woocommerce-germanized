import { useEffect, useState } from "@wordpress/element";
import { CART_STORE_KEY } from '@woocommerce/block-data';
import { useSelect } from '@wordpress/data';
import { PAYMENT_STORE_KEY } from '@woocommerce/block-data';

import LegalCheckbox from "./legal-checkbox";

const SepaCheckbox = (props ) => {
    const [ fields, setFields ] = useState({} );
    const { onChangeCheckbox, checkbox } = props;

    const { billingAddress, paymentData, currentPaymentMethod } = useSelect(
        ( select ) => {
            const store = select( CART_STORE_KEY );
            const paymentStore = select( PAYMENT_STORE_KEY );

            return {
                billingAddress: store.getCartData().billingAddress,
                paymentData: paymentStore.getPaymentMethodData(),
                currentPaymentMethod: paymentStore.getActivePaymentMethod(),
            };
        }
    );

    useEffect( () => {
        const fields = {
            'country': billingAddress['country'],
            'postcode': billingAddress['postcode'],
            'city': billingAddress['city'],
            'street': billingAddress['address_1'],
            'address_2': billingAddress['address_2'],
            'account_holder': paymentData.hasOwnProperty( 'direct_debit_account_holder' ) ? paymentData['direct_debit_account_holder'] : '',
            'account_iban': paymentData.hasOwnProperty( 'direct_debit_account_iban' ) ? paymentData['direct_debit_account_iban'] : '',
            'account_swift': paymentData.hasOwnProperty( 'direct_debit_account_bic' ) ? paymentData['direct_debit_account_bic'] : '',
        };

        setFields( fields );

        if ( currentPaymentMethod === 'direct-debit' && fields['account_holder'] && fields['account_iban'] && fields['account_swift'] ) {
            onChangeCheckbox( { ...checkbox, hidden: false } );
        } else {
            onChangeCheckbox( { ...checkbox, hidden: true } );
        }
    }, [
        billingAddress,
        paymentData,
        currentPaymentMethod
    ] );

    const setModalUrl = ( url ) => {
        const searchParams = new URLSearchParams( fields );
        url += '&' + searchParams.toString();

        props.setModalUrl( url );
    };

    return (
        <LegalCheckbox
            { ...props }
            setModalUrl={ setModalUrl }
        />
    );
};

export default SepaCheckbox;