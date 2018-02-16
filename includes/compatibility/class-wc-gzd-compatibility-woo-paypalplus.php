<?php
/**
 * PayPal Plus Helper for Inpsyde
 *
 * Specific configuration for Woo PayPal Plus by Inspyde
 *
 * @class 		WC_GZD_Compatibility_Woo_Paypalplus
 * @category	Class
 * @author 		vendidero
 */
class WC_GZD_Compatibility_Woo_Paypalplus extends WC_GZD_Compatibility {

	public function __construct() {
		parent::__construct(
			'PayPal Plus for WooCommerce',
			'woo-paypalplus/paypalplus-woocommerce.php'
		);
	}

	public function load() {
		// Clear session on updating order review via AJAX
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'clear_paypal_session' ) );
	}

	/**
	 * This method tries to clear PayPal Plus session data to allow our plugin to manipulate item data within checkout.
	 * Needed e.g. for payment gateway fees or vat_exempts.
	 */
	public function clear_paypal_session() {
		$gateways = WC_Payment_Gateways::instance()->get_available_payment_gateways();
		$current = empty( $_POST['payment_method'] ) ? '' : wc_clean( $_POST['payment_method'] );

		foreach( $gateways as $gateway ) {
			if ( $current === $gateway->id && 'paypal_plus' === $gateway->id ) {
				if ( is_callable( array( $gateway, 'clear_session_data' ) ) ) {
					$gateway->clear_session_data();
				}
			}
		}
	}
}