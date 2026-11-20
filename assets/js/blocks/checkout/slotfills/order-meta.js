import { ExperimentalOrderMeta } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect } from "@wordpress/element";
import { dispatch, select } from '@wordpress/data';
import { CHECKOUT_STORE_KEY } from '@woocommerce/block-data';

import {
    waitUntilElementExists,
} from '../../../base/utils';

import {
    replaceItemMeta,
} from '../../cart-checkout/items';

import SmallBusinessInfo from "../checkout-small-business-info/frontend";

let isObservingOrderSummaries = false;

/**
 * Show the total only within the custom Germanized submit summary and hide it within the default summary.
 * Skip the fill wrapper as WooCommerce (11.3+) moves the content of the summary (temporarily) to that fill.
 * Only adjust classes in case they changed to prevent mutation (observer) loops.
 */
const updateTotalWrappers = () => {
    const totalWrappers = document.querySelectorAll( '.wp-block-woocommerce-checkout-order-summary-block:not(.checkout-order-summary-block-fill-wrapper)' );

    for ( const totalWrapper of totalWrappers ) {
        const totalItems = totalWrapper.getElementsByClassName( 'wc-block-components-totals-wrapper' );
        const isGzdWrapper = totalWrapper.parentNode.classList.contains( 'wc-gzd-checkout-submit' );
        let hide = isGzdWrapper;

        for ( const item of totalItems ) {
            const hasFooterTotal = item.querySelector( ".wc-block-components-totals-footer-item" ) !== null;

            if ( hasFooterTotal ) {
                hide = !hide;
            }

            const addClass = hide ? 'wc-gzd-hide-total-wrapper' : 'wc-gzd-show-total-wrapper';
            const removeClass = hide ? 'wc-gzd-show-total-wrapper' : 'wc-gzd-hide-total-wrapper';

            if ( item.classList.contains( removeClass ) ) {
                item.classList.remove( removeClass );
            }

            if ( ! item.classList.contains( addClass ) ) {
                item.classList.add( addClass );
            }
        }
    }
};

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

        if ( ! extensionsData.hasOwnProperty( 'woocommerce-germanized' ) ) {
            dispatch( CHECKOUT_STORE_KEY ).setExtensionData( 'woocommerce-germanized', {} );
        }
    }, [] );

    /**
     * Since WooCommerce 11.3 the order summary content is rendered only once and moved (via DOM)
     * between the (collapsible) summary and a fill placed above the place order button
     * depending on the open state of the summary. Germanized (and the multilevel checkout) hide that fill on smaller screens
     * and expect the summary content to be shown inline, e.g. by disabling the summary toggle.
     * Make sure these (non-toggleable) summaries are open. The toggle check is still needed, e.g. to leave
     * the (toggleable) sidebar summary of the multilevel checkout untouched.
     */
    useEffect(() => {
        const checkout = document.querySelector( '.wp-block-woocommerce-checkout' );

        if ( ! checkout || 'function' !== typeof window.MutationObserver || isObservingOrderSummaries ) {
            return;
        }

        isObservingOrderSummaries = true;
        let frame = null;

        const maybeOpenSummaries = () => {
            frame = null;

            /**
             * The summary content might have been moved (e.g. on resize), update total wrapper visibility.
             */
            updateTotalWrappers();

            /**
             * Only adjust the summary in case the checkout is being germanized (including the multilevel checkout).
             */
            if ( ! document.body.classList.contains( 'woocommerce-gzd-checkout' ) ) {
                return;
            }

            const titles = checkout.querySelectorAll( '.wp-block-woocommerce-checkout-order-summary-block > .wc-block-components-checkout-order-summary__title:not(.is-open)' );

            for ( const title of titles ) {
                const layout = title.closest( '.wc-block-components-sidebar-layout' );
                const isCollapsible = layout && [ 'is-mobile', 'is-small', 'is-medium' ].some( ( className ) => layout.classList.contains( className ) );

                if ( ! isCollapsible || title.closest( '.wc-gzd-checkout-submit' ) ) {
                    continue;
                }

                const titleStyle = window.getComputedStyle( title );

                /**
                 * Leave summaries which may be toggled by the customer untouched.
                 */
                if ( 'none' !== titleStyle.display && 'none' !== titleStyle.pointerEvents ) {
                    continue;
                }

                const content = title.parentNode.querySelector( ':scope > .wc-block-components-checkout-order-summary__content' );

                /**
                 * Older Woo versions render the summary content inline regardless of the open state.
                 */
                if ( content && 0 === content.childElementCount ) {
                    title.click();
                }
            }
        };

        const observer = new window.MutationObserver( () => {
            if ( null === frame ) {
                frame = window.requestAnimationFrame( maybeOpenSummaries );
            }
        } );

        observer.observe( checkout, { attributes: true, attributeFilter: [ 'class' ], childList: true, subtree: true } );

        maybeOpenSummaries();

        return () => {
            observer.disconnect();

            if ( null !== frame ) {
                window.cancelAnimationFrame( frame );
            }

            isObservingOrderSummaries = false;
        };
    }, [] );

    useEffect(() => {
        /**
         * Use a timeout as tweak to make sure DOM is available.
         */
        waitUntilElementExists( '.wc-block-components-product-details' ).then( ( elm ) => {
            const orderItems = document.getElementsByClassName( 'wc-block-components-order-summary-item' );
            let itemKey = 0;

            for ( let item of orderItems ) {
                const unitPrice   = item.getElementsByClassName( "wc-block-components-product-details__gzd-unit-price" )[0];
                const notGzdElements = item.querySelectorAll( "span:not([class*=__gzd])" )[0];

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

                replaceItemMeta( item, itemKey, cart );

                itemKey++;
            }
        } );
    }, [
        cart.cartItems
    ] );

    useEffect(() => {
        updateTotalWrappers();
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