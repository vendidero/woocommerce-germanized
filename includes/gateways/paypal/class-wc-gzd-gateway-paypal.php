<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * PayPal Standard Payment Gateway
 *
 * Provides a PayPal Standard Payment Gateway.
 *
 * @class 		WC_Paypal
 * @extends		WC_Gateway_Paypal
 * @version		2.0.0
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_GZD_Gateway_Paypal extends WC_Gateway_Paypal {

	public $parent;

	public function __construct() {
		parent::__construct();
		$this->parent = new WC_GZD_Payment_Gateway( $this );
	}

	public function __call( $method, $arguments ) {
		if ( method_exists( $this->parent, $method ) )
			return call_user_func_array( array( $this->parent, $method), $arguments );
	}

	public function __get( $key ) {
		if ( isset( $this->parent->$key ) )
			return $this->parent->$key;
	}

}