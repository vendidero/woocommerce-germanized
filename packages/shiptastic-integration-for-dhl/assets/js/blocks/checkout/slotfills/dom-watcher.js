import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect } from "@wordpress/element";
import { dispatch, select } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';

const DomWatcher = ({
    extensions,
    cart
}) => {
    /**
     * Use this little helper which sets the default checkout data in case it
     * does not exist, e.g. the checkboxes block is missing to prevent extension errors.
     *
     * @see https://github.com/woocommerce/woocommerce-blocks/issues/11446
     */
    useEffect(() => {
        const extensionsData = select( CHECKOUT_STORE_KEY ).getExtensionData();

        if ( ! extensionsData.hasOwnProperty( 'woocommerce-stc-dhl' ) ) {
            dispatch( CHECKOUT_STORE_KEY ).setExtensionData( 'woocommerce-stc-dhl', {} );
        }
    }, [] );

    return null;
};

const render = () => {
    return (
        <ExperimentalOrderMeta>
            <DomWatcher />
        </ExperimentalOrderMeta>
    );
};

registerPlugin( 'woocommerce-stc-dhl-checkout-order-meta', {
    render,
    scope: 'woocommerce-checkout',
} );