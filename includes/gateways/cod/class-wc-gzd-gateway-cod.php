<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Cash on Delivery Gateway
 *
 * Provides a Cash on Delivery Payment Gateway. Extends base class to enable payment fee.
 *
 * @class 		WC_GZD_Gateway_COD
 * @extends		WC_Gateway_COD
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Gateway_COD extends WC_Gateway_COD {

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

	public function init_form_fields() {
		parent::init_form_fields();
	}

}