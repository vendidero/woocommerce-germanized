<?php
/**
 * The Template for displaying the review order product table within checkout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/checkout/review-order-product-table.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 3.0.1
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
	$_product = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );

	if ( $_product && $_product->exists() && $cart_item['quantity'] > 0 && apply_filters( 'woocommerce_checkout_cart_item_visible', true, $cart_item, $cart_item_key ) ) {
		?>
		<tr class="<?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $cart_item, $cart_item_key ) ); ?>">
			<td class="product-name">
				<?php if ( 'yes' === get_option( 'woocommerce_gzd_display_checkout_thumbnails' ) ) : ?>

				<div class="wc-gzd-product-name-left">
					<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_thumbnail', $_product->get_image(), $cart_item, $cart_item_key ) ); ?>
				</div>

				<div class="wc-gzd-product-name-right">

					<?php endif; ?>

					<?php echo wp_kses_post( apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key ) ) . '&nbsp;'; ?>
					<?php echo apply_filters( 'woocommerce_checkout_cart_item_quantity', ' <strong class="product-quantity">' . sprintf( '&times; %s', esc_html( $cart_item['quantity'] ) ) . '</strong>', $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<?php echo wc_get_formatted_cart_item_data( $cart_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<?php if ( 'yes' === get_option( 'woocommerce_gzd_display_checkout_thumbnails' ) ) : ?>

				</div>
				<div class="clear"></div>

			<?php endif; ?>

			</td>
			<td class="product-total">
				<?php echo apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $_product, $cart_item['quantity'] ), $cart_item, $cart_item_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</td>
		</tr>
		<?php
	}
}
