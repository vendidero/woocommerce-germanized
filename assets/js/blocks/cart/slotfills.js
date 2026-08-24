import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect } from "@wordpress/element";

import {
    waitUntilElementExists,
} from '../../base/utils';

import {
    replaceItemMeta,
} from '../cart-checkout/items';

import SmallBusinessInfo from "../checkout/checkout-small-business-info/frontend";

const DomWatcher = ({
    extensions,
    cart
}) => {
    useEffect(() => {
        waitUntilElementExists( '.wc-block-components-product-details' ).then( ( elm ) => {
            const orderItems = document.getElementsByClassName( 'wc-block-cart-items__row' );
            let itemIndex = 0;

            for ( let item of orderItems ) {
                const productDetails = item.getElementsByClassName( 'wc-block-components-product-details' )[0];

                if ( productDetails ) {
                    const notGzdElements = productDetails.querySelectorAll( "span:not([class*=__gzd])" )[0];

                    if ( notGzdElements ) {
                        notGzdElements.classList.add( "wc-not-gzd-summary-item-first" );
                    }
                }

                replaceItemMeta( item, itemIndex, cart );

                itemIndex++;
            }
        } );
    }, [
        cart.cartItems
    ] );

    return null;
};

const render = () => {
    return (
        <ExperimentalOrderMeta>
            <SmallBusinessInfo />
            <DomWatcher />
        </ExperimentalOrderMeta>
    );
};

registerPlugin( 'woocommerce-germanized-cart', {
    render,
    scope: 'woocommerce-checkout',
} );