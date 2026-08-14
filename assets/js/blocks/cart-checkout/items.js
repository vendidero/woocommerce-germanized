function getCartExtensionData( dataKey, itemIndex, cart ) {
    const item = cart.cartItems[ itemIndex ];

    if ( item && item.extensions.hasOwnProperty( 'woocommerce-germanized' ) && item.extensions['woocommerce-germanized'].hasOwnProperty( dataKey ) ) {
        return item.extensions['woocommerce-germanized'][dataKey];
    }

    return false;
}

export function replaceItemMeta( item, itemIndex, cart ) {
    const garanLabel = item.getElementsByClassName( "wc-block-components-product-details__gzd-garan-label" )[0];

    if ( garanLabel ) {
        const garanLabelHtml = getCartExtensionData( 'garan_label', itemIndex, cart );

        if ( garanLabelHtml ) {
            let tmp = document.createElement("DIV");
            tmp.innerHTML = garanLabelHtml;

            if ( garanLabel.innerHTML !== tmp.innerHTML ) {
                garanLabel.innerHTML = garanLabelHtml;
            }
        }
    }
}