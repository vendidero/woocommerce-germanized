/**
 * External dependencies
 */
import { registerPaymentMethod } from '@woocommerce/blocks-registry';
import { __ } from '@wordpress/i18n';
import { getPaymentMethodData } from '@woocommerce/settings';
import { decodeEntities } from '@wordpress/html-entities';

const settings = getPaymentMethodData( 'invoice', {} );
const defaultLabel = __( 'Pay by Invoice', 'woocommerce-germanized' );
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

/**
 * Cheque payment method config object.
 */
const InvoicePaymentMethod = {
    name: 'invoice',
    label: <Label />,
    content: <Content />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings?.supports ?? [],
    },
};

registerPaymentMethod( InvoicePaymentMethod );
