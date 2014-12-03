<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Bank Transfer Payment Gateway
 *
 * Provides a Bank Transfer Payment Gateway. Extends base class to allow empty instructions.
 *
 * @class 		WC_GZD_Gateway_BACS
 * @extends		WC_Gateway_BACS
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Gateway_BACS extends WC_Gateway_BACS {

	public $parent;

	public function __construct() {
		parent::__construct();
		if ( $this->get_option( 'has_instructions' ) == 'no' )
			$this->instructions = '';
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
		$this->form_fields[ 'has_instructions' ] = array(
			'title'   => __( 'Print instructions?', 'woocommerce-germanized' ),
			'type'    => 'checkbox',
			'label'   => __( 'Show instructions', 'woocommerce-germanized' ),
			'default' => 'no'
		);
	}

}