<?php

/**
 * Helper for PayPal Express Checkout Gateway Plugin
 *
 * https://de.wordpress.org/plugins/express-checkout-paypal-payment-gateway-for-woocommerce/
 * This plugin seems to disable default Woo checkout flow which might leads to missing confirmation mails.
 * Use the woocommerce_pre_payment_complete as a fallback instead.
 *
 * @class        WC_GZD_Compatibility_PayPal_Express_Checkout
 * @category     Class
 * @author       vendidero
 */
class WC_GZD_Compatibility_PayPal_Express_Checkout extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'PayPal Express Checkout Payment Gateway for WooCommerce';
	}

	public static function get_path() {
		return 'express-checkout-paypal-payment-gateway-for-woocommerce/express-checkout-paypal-payment-gateway-for-woocommerce.php';
	}

	public function load() {
		add_action( 'woocommerce_pre_payment_complete', array( $this, 'maybe_confirm_order' ), 10, 3 );
	}

	/**
	 * @param $order_id
	 */
	public function maybe_confirm_order( $order_id ) {
		if ( $order = wc_get_order( $order_id ) ) {
			if ( 'eh_paypal_express' === $order->get_payment_method() ) {
				WC_germanized()->emails->confirm_order( $order );
			}
		}
	}
}