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

	public function __construct() {
		parent::__construct();
		if ( $this->get_option( 'fee' ) ) {
			add_filter( 'woocommerce_gateway_title', array( $this, 'manipulate_title' ), 0, 2 );
			$this->description .= ' ' . sprintf( __( 'Plus %s payment charge.', 'woocommerce-germanized' ), wc_price( $this->get_option( 'fee' ) ) );
		}
	}

	/**
	 * Manipulate title if is within svn_checkout
	 *  
	 * @param  string $title
	 * @param  payment gateway $id 
	 * @return string
	 */
	public function manipulate_title( $title, $id ) {
		if ( $id == $this->id && $this->get_option( 'fee' ) && ( is_checkout() || ( defined( 'DOING_AJAX' ) && isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'woocommerce_update_order_review' ) ) )
			$title = __( 'Cash on Delivery', 'woocommerce' ) . ' <span class="small">(' . sprintf( __( 'plus %s payment charge', 'woocommerce-germanized' ), wc_price( $this->get_option( 'fee' ) ) ) . ')</span>';
		return $title;
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
			'label' 	  => __( 'Check if fee is taxable.', 'woocommerce-germanized' ),
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