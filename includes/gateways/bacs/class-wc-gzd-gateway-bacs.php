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

	public function __construct() {
		parent::__construct();
		if ( $this->get_option( 'has_instructions' ) == 'no' )
			$this->instructions = '';
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