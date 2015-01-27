<?php
/**
 * WooCommerce Payment Gateways class
 *
 * Loads payment gateways via hooks for use in the store.
 *
 * @class 		WC_Payment_Gateways
 * @version		2.2.0
 * @package		WooCommerce/Classes/Payment
 * @category	Class
 * @author 		WooThemes
 */
class WC_GZD_Payment_Gateways extends WC_Payment_Gateways {

	public static function instance() {
		if ( is_null( self::$_instance ) )
			self::$_instance = new self();
		return self::$_instance;
	}

	public function __construct() {
		$this->init();
	}

	public function init() {
		parent::init();
		if ( ! empty( $this->payment_gateways ) ) {
			foreach ( $this->payment_gateways as $gateway ) {
				$gateway->parent = new WC_GZD_Payment_Gateway( $gateway );
			}
		}
		do_action( 'woocommerce_gzd_payment_gateways_loaded' );
	}

}
