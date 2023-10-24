import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect } from "@wordpress/element";

import SmallBusinessInfo from "../checkout-small-business-info/frontend";

const DomWatcher = ({
                        extensions,
                        cart
                    }) => {
    useEffect(() => {
        const orderItems = document.getElementsByClassName( 'wc-block-components-order-summary-item' );

        for ( let item of orderItems ) {
            const unitPrice   = item.getElementsByClassName( "wc-block-components-product-details__gzd-unit-price" )[0];
            const notGzdElements = item.querySelectorAll( "li:not([class*=__gzd])" )[0];

            if ( notGzdElements ) {
                notGzdElements.classList.add( "wc-not-gzd-summary-item-first" );
            }

            if ( unitPrice ) {
                const priceNode = item.getElementsByClassName( "wc-block-components-order-summary-item__total-price" )[0];
                const unitPriceNew = priceNode.getElementsByClassName( "wc-gzd-unit-price" )[0];

                if ( unitPriceNew ) {
                    priceNode.removeChild( unitPriceNew );
                }

                const newUnitPrice = document.createElement("div" );
                newUnitPrice.className = 'wc-gzd-unit-price';
                newUnitPrice.innerHTML = unitPrice.innerHTML;

                unitPrice.classList.add( "wc-gzd-unit-price-moved" );

                priceNode.appendChild( newUnitPrice );
            }
        }
    }, [
        cart.cartItems
    ] );

    useEffect(() => {
        const totalWrappers = document.getElementsByClassName( 'wp-block-woocommerce-checkout-order-summary-block' );

        for ( let totalWrapper of totalWrappers ) {
            const totalItems = totalWrapper.getElementsByClassName( 'wc-block-components-totals-wrapper' );
            const isGzdWrapper = totalWrapper.parentNode.classList.contains( 'wc-gzd-checkout-submit' );
            let hide = isGzdWrapper;

            for ( let item of totalItems ) {
                const hasFooterTotal = item.querySelector( ".wc-block-components-totals-footer-item" ) != null;

                if ( hasFooterTotal ) {
                    hide = !hide;
                }

                if ( hide ) {
                    item.classList.remove( "wc-gzd-show-total-wrapper" );
                    item.classList.add( "wc-gzd-hide-total-wrapper" );
                } else {
                    item.classList.remove( "wc-gzd-hide-total-wrapper" );
                    item.classList.add( "wc-gzd-show-total-wrapper" );
                }
            }
        }
    }, [
        cart.cartTotals
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

registerPlugin( 'woocommerce-germanized-checkout-order-meta', {
    render,
    scope: 'woocommerce-checkout',
} );