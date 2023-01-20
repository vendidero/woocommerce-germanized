<?php
/**
 * The Template for inserting the static order submit button within checkout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce-germanized/checkout/order-submit.php.
 *
 * HOWEVER, on occasion Germanized will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://github.com/vendidero/woocommerce-germanized/wiki/Overriding-Germanized-Templates
 * @package Germanized/Templates
 * @version 2.4.3
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wc-gzd-order-submit">
	<div class="form-row place-order wc-gzd-place-order">
		<noscript>
			<?php printf( esc_html__( 'Since your browser does not support JavaScript, or it is disabled, please ensure you click the %1$sUpdate Totals%2$s button before placing your order. You may be charged more than the amount stated above if you fail to do so.', 'woocommerce-germanized' ), '<em>', '</em>' ); ?>
			<br/>
			<button type="submit" class="button alt" name="woocommerce_checkout_update_totals" value="<?php esc_attr_e( 'Update totals', 'woocommerce' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?>"><?php esc_html_e( 'Update totals', 'woocommerce' ); // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch ?></button>
		</noscript>

		<?php do_action( 'woocommerce_review_order_before_submit' ); ?>

		<?php
		/**
		 * Before review order submit button.
		 *
		 * This hooks fires right before outputting the order submit button.
		 *
		 * @since 1.0.0
		 */
		do_action( 'woocommerce_gzd_review_order_before_submit' );
		?>

		<?php echo apply_filters( 'woocommerce_order_button_html', '<button type="submit" class="button alt' . esc_attr( wc_gzd_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_gzd_wp_theme_get_element_class_name( 'button' ) : '' ) . '" name="woocommerce_checkout_place_order" id="place_order" value="' . esc_attr( $order_button_text ) . '" data-value="' . esc_attr( $order_button_text ) . '">' . esc_html( $order_button_text ) . '</button>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<?php if ( $include_nonce ) : ?>
			<?php wp_nonce_field( 'woocommerce-process_checkout' ); ?>
		<?php endif; ?>

		<?php do_action( 'woocommerce_review_order_after_submit' ); ?>
	</div>
</div>
