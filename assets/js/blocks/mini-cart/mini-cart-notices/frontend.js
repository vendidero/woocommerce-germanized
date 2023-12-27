import { useEffect, useState } from "@wordpress/element";
import { getSetting } from '@germanized/settings';
import { __, sprintf } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { CART_STORE_KEY } from '@woocommerce/block-data';

import './style.scss';

/**
 * External dependencies
 */
const Block = ({
   className
}) => {
    const displayCartPricesIncludingTax = getSetting(
        'displayCartPricesIncludingTax',
        false
    );

    const showTaxNotice = getSetting(
        'showMiniCartTaxNotice',
        true
    );

    const showShippingCostsNotice = getSetting(
        'showMiniCartShippingCostsNotice',
        true
    );

    const isSmallBusiness = getSetting(
        'isSmallBusiness',
        false
    );

    const smallBusinessNotice = getSetting(
        'smallBusinessNotice',
        ''
    );

    const cart = useSelect(
        ( select, { dispatch } ) => {
            const store = select( CART_STORE_KEY );
            const cartData = store.getCartData();
            const cartErrors = store.getCartErrors();
            const cartTotals = store.getCartTotals();
            const cartIsLoading =
                ! store.hasFinishedResolution( 'getCartData' );

            const { receiveCart, receiveCartContents } = dispatch( CART_STORE_KEY );
            const gzdDefaultData = {
                'shipping_costs_notice': ''
            };

            const gzdData = cartData.extensions.hasOwnProperty( 'woocommerce-germanized' ) ? cartData.extensions['woocommerce-germanized'] : gzdDefaultData;
            const gzdTaxNotice = displayCartPricesIncludingTax ? __( 'incl. VAT', 'woocommerce-germanized' ) : __( 'excl. VAT', 'woocommerce-germanized' );

            return {
                cartItems: cartData.items,
                crossSellsProducts: cartData.crossSells,
                cartItemsCount: cartData.itemsCount,
                cartItemsWeight: cartData.itemsWeight,
                cartNeedsPayment: cartData.needsPayment,
                cartNeedsShipping: cartData.needsShipping,
                cartItemErrors: cartData.errors,
                cartTotals,
                cartIsLoading,
                cartErrors,
                extensions: cartData.extensions,
                shippingRates: cartData.shippingRates,
                cartHasCalculatedShipping: cartData.hasCalculatedShipping,
                paymentRequirements: cartData.paymentRequirements,
                shippingCostsNotice: cartData.needsShipping ? gzdData.shipping_costs_notice : '',
                taxNotice: cartTotals.total_tax > 0 ? gzdTaxNotice : '',
                receiveCart,
                receiveCartContents,
            };
        },
        [ displayCartPricesIncludingTax ]
    );

    return (
        <div className="wc-gzd-block-mini-cart-notices">
            { isSmallBusiness && smallBusinessNotice && ! showTaxNotice &&
                <div className="wc-gzd-block-mini-cart-notices__notice wc-gzd-block-mini-cart-notices__small-business-notice"
                     dangerouslySetInnerHTML={ {
                         __html: smallBusinessNotice,
                     } }
                >
                </div>
            }
            <div className="wc-gzd-block-mini-cart-notices__notice-wrap">
                { cart.taxNotice && showTaxNotice &&
                    <div className="wc-gzd-block-mini-cart-notices__notice wc-gzd-block-mini-cart-notices__tax-notice"
                         dangerouslySetInnerHTML={ {
                             __html: cart.taxNotice,
                         } }
                    >
                    </div>
                }
                { cart.shippingCostsNotice && showShippingCostsNotice &&
                    <div className="wc-gzd-block-mini-cart-notices__notice wc-gzd-block-mini-cart-notices__shipping-notice"
                         dangerouslySetInnerHTML={ {
                             __html: cart.shippingCostsNotice,
                         } }
                    >
                    </div>
                }
            </div>
        </div>
    );
};
export default Block;