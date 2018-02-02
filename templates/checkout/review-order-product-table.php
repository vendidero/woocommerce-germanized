<?php
/**
 * Review order product table
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) )
	exit;

do_action( 'woocommerce_gzd_review_order_before_cart_contents' );

foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
	$_product     = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

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

				    <?php echo apply_filters( 'woocommerce_cart_item_name', wc_gzd_get_product_name( $_product ), $cart_item, $cart_item_key ) . '&nbsp;'; ?>
					<?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf( '&times; %s', $cart_item['quantity'] ) . '</strong>', $cart_item, $cart_item_key ); ?>
                    <?php if ( function_exists( 'wc_get_formatted_cart_item_data' ) ) : ?>
                        <?php echo wc_get_formatted_cart_item_data( $cart_item ); ?>
                    <?php else: ?>
                        <?php echo WC()->cart->get_item_data( $cart_item ); ?>
                    <?php endif; ?>
				
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