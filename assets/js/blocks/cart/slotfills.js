import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect } from "@wordpress/element";

import SmallBusinessInfo from "../checkout/checkout-small-business-info/frontend";

const DomWatcher = ({
    extensions,
    cart
}) => {
    useEffect(() => {
        const orderItems = document.getElementsByClassName( 'wc-block-cart-items__row' );

        for ( let item of orderItems ) {
            const notGzdElements = item.querySelectorAll( "li:not([class*=__gzd])" )[0];

            if ( notGzdElements ) {
                notGzdElements.classList.add( "wc-not-gzd-summary-item-first" );
            }
        }
    }, [
        cart.cartItems
    ] );

    return null;
};

const render = () => {
    return (
        <ExperimentalOrderMeta>
            <DomWatcher />
            <SmallBusinessInfo />
        </ExperimentalOrderMeta>
    );
};

registerPlugin( 'woocommerce-germanized-cart', {
    render,
    scope: 'woocommerce-checkout',
} );