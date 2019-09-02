<?php
/**
 * Review order product table
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Before review order product table.
 *
 * Fires before rendering the checkout review order product table.
 * This additional template replaces Woo's default product table within review-order.php.
 *
 * @since 1.0.0
 */
do_action( 'woocommerce_gzd_review_order_before_cart_contents' );

foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
	$_product = $cart_item['data'];

	if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
		?>
		<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
			<td class="product-name">
				
				<?php if ( get_option( 'woocommerce_gzd_display_checkout_thumbnails' ) == 'yes' ) : ?>
				
					<div class="wc-gzd-product-name-left">
						<?php echo apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key ); ?>
					</div>
				
					<div class="wc-gzd-product-name-right">
				
				<?php endif; ?>

				    <?php echo apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) . '&nbsp;'; ?>
					<?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf( '&times; %s', $cart_item['quantity'] ) . '</strong>', $cart_item, $cart_item_key ); ?>

                    <?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>

				<?php if ( get_option( 'woocommerce_gzd_display_checkout_thumbnails' ) == 'yes' ) : ?>

					</div>
					<div class="clear"></div>

				<?php endif; ?>
			
			</td>
			<td class="product-total">
				<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); ?>
			</td>
		</tr>
		<?php
	}
}