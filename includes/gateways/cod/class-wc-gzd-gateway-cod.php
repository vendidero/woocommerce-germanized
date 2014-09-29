<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Cash on Delivery Gateway
 *
 * Provides a Cash on Delivery Payment Gateway.
 *
 * @class 		WC_Gateway_COD
 * @extends		WC_Payment_Gateway
 * @version		2.1.0
 * @package		WooCommerce/Classes/Payment
 * @author 		WooThemes
 */
class WC_GZD_Gateway_COD extends WC_Gateway_COD {

	public function __construct() {
		parent::__construct();
		if ( $this->get_option( 'fee' ) ) {
			$this->title .= ' <span class="small">(' . sprintf( __( 'plus %s payment charge', 'woocommerce-germanized' ), wc_price( $this->get_option( 'fee' ) ) ) . ')</span>';
			$this->description .= ' ' . sprintf( __( 'Plus %s payment charge.', 'woocommerce-germanized' ), wc_price( $this->get_option( 'fee' ) ) );
		}
	}

	public function init_form_fields() {
		parent::init_form_fields();
		$this->form_fields[ 'fee' ] = array(
			'title'       => __( 'Fee', 'woocommerce-germanized' ),
			'type'        => 'decimal',
			'description' => __( 'This fee is being added if customer selects payment method within checkout.', 'woocommerce-germanized' ),
			'default'     => 0,
			'desc_tip'    => true,
		);
		$this->form_fields[ 'fee_is_taxable' ] = array(
			'title'       => __( 'Fee is taxable?', 'woocommerce-germanized' ),
			'type'        => 'checkbox',
			'label' 	  => 'Check if fee is taxable.',
			'default'     => 'no',
		);
	}

	public function add_fee() {
		if ( $this->get_option( 'fee' ) ) {
			$is_taxable = ( $this->get_option( 'fee_is_taxable', 'no' ) == 'no' ? false : true );
			WC()->cart->add_fee( __( 'Payment charge', 'woocommerce-germanized' ), $this->get_option( 'fee' ), $is_taxable );
		}
	}

}