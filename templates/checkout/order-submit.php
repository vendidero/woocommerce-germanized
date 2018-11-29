<?php
/**
 * Order submit button template
 *
 * @author 		Vendidero
 * @package 	WooCommerceGermanized/Templates
 * @version     2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wc-gzd-order-submit">

	<div class="form-row place-order wc-gzd-place-order">

        <noscript>
			<?php esc_html_e( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the <em>Update Totals</em> button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce' ); ?>
            <br/><button type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'woocommerce' ); ?>"><?php esc_html_e( 'Update totals', 'woocommerce' ); ?></button>
        </noscript>

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<?php do_action( 'woocommerce_gzd_review_order_before_submit' ); ?>

		<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); ?>

        <?php if ( $include_nonce ) :
	        wp_nonce_field( 'woocommerce-process_checkout', '_wpnonce' ); ?>
        <?php endif; ?>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>

	</div>

</div>