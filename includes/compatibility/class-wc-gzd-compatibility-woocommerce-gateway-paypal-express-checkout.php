<?php

defined( 'ABSPATH' ) || exit;

/**
 * Woo Gateway PayPal Express Checkout Helper
 *
 * Specific compatibility for PayPal Express
 *
 * @class        WC_GZD_Compatibility_Woocommerce_Gateway_Paypal_Express_Checkout
 * @category    Class
 * @author        vendidero
 */
class WC_GZD_Compatibility_WooCommerce_Gateway_Paypal_Express_Checkout extends WC_GZD_Compatibility {

	public static function get_name() {
		return 'WooCommerce PayPal Checkout Gateway';
	}

	public static function get_path() {
		return 'woocommerce-gateway-paypal-express-checkout/woocommerce-gateway-paypal-express-checkout.php';
	}

	public function load() {
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'payment_gateways' ), 150, 1 );
	}

	/**
	 * Problem: Smart Button is bound to review_order_after_submit which gets executed earlier than GZD submit button.
	 * Leads to JS problems while rendering the PayPal button. That's why we need to move the functionality to the GZD hook.
	 *
	 * @param $gateways
	 *
	 * @return mixed
	 */
	public function payment_gateways( $gateways ) {
		$gateway = isset( $gateways['ppec_paypal'] ) ? $gateways['ppec_paypal'] : false;

		if ( $gateway && ( is_a( $gateway, 'WC_Gateway_PPEC_With_SPB' ) || is_a( $gateway, 'WC_Gateway_PPEC_With_SPB_Addons' ) ) ) {

			remove_action( 'woocommerce_review_order_after_submit', array( $gateway, 'display_paypal_button' ), 10 );
			remove_action(
				'woocommerce_gzd_review_order_before_submit',
				array(
					$gateway,
					'display_paypal_button',
				),
				10
			);

			add_action( 'woocommerce_gzd_review_order_before_submit', array( $gateway, 'display_paypal_button' ), 10 );
		}

		return $gateways;
	}
}
