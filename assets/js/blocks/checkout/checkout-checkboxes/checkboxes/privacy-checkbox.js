import { useEffect } from "@wordpress/element";
import { useSelect } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';
import { getSetting } from '@woocommerce/settings';

import LegalCheckbox from './legal-checkbox';

const PrivacyCheckbox = ( props ) => {
    const { onChangeCheckbox, checkbox } = props;
    const { shouldCreateAccount, customerId } = useSelect( ( select ) => {
        const store = select( CHECKOUT_STORE_KEY );
        return {
            customerId: store.getCustomerId(),
            shouldCreateAccount: store.getShouldCreateAccount(),
        };
    } );
    const forceShow = false === getSetting( 'checkoutAllowsGuest', false ) && ! customerId;

    useEffect( () => {
        if ( shouldCreateAccount || forceShow ) {
            onChangeCheckbox( { ...checkbox, hidden: false } );
        } else {
            onChangeCheckbox( { ...checkbox, hidden: true } );
        }
    }, [
        shouldCreateAccount
    ] );

    return (
        <LegalCheckbox
            { ...props }
        />
    );
};

export default PrivacyCheckbox;