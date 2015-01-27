<?php
/**
 * Adds payment method fee to WC_Payment_Gateway
 *
 * @class 		WC_GZD_Payment_Gateway
 * @version		1.0.0
 * @author 		Vendidero
 */
class WC_GZD_Payment_Gateway {

	public $gateway;
	public $extra_fields = array();

	public function __construct( WC_Payment_Gateway $gateway ) {
		$this->gateway = $gateway;
		$this->init_form_fields();
		if ( $this->get_option( 'fee' ) ) {
			add_filter( 'woocommerce_gateway_title', array( $this, 'manipulate_title' ), 0, 2 );
			$this->description .= ' ' . sprintf( __( 'Plus %s payment charge.', 'woocommerce-germanized' ), wc_price( $this->get_option( 'fee' ) ) );
		}
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this->gateway, 'process_admin_options' ), PHP_INT_MAX );
		if ( ! isset( $this->gateway->force_order_button_text ) || ! $this->gateway->force_order_button_text )
			$this->gateway->order_button_text = __( get_option( 'woocommerce_gzd_order_submit_btn_text' ), 'woocommerce-germanized' );
	}

	public function __get( $key ) {
		return $this->gateway->$key;
	}

	public function __set( $key, $value ) {
		$this->gateway->$key = $value;
	}

	public function __call( $method, $arguments ) {
		if ( method_exists( $this->gateway, $method ) )
			return call_user_func_array( array( $this->gateway, $method), $arguments );
	}

	/**
	 * Add default form fields to enable fee within payment method settings page
	 */
	public function init_form_fields() {
		$this->extra_fields[ 'fee' ] = array(
			'title'       => __( 'Fee', 'woocommerce-germanized' ),
			'type'        => 'decimal',
			'description' => __( 'This fee is being added if customer selects payment method within checkout.', 'woocommerce-germanized' ),
			'default'     => 0,
			'desc_tip'    => true,
		);
		$this->extra_fields[ 'fee_is_taxable' ] = array(
			'title'       => __( 'Fee is taxable?', 'woocommerce-germanized' ),
			'type'        => 'checkbox',
			'label' 	  => __( 'Check if fee is taxable.', 'woocommerce-germanized' ),
			'default'     => 'no',
		);
		foreach( $this->extra_fields as $key => $field )
			$this->gateway->form_fields[ $key ] = $field;
	}

	/**
	 * Adds the fee to current Cart. If fee is taxable calculate net price and add net price as fee.
	 */
	public function add_fee() {
		if ( $this->get_option( 'fee' ) ) {
			$is_taxable = ( $this->get_option( 'fee_is_taxable', 'no' ) == 'no' ? false : true );
			$fee = $this->get_option( 'fee' );
			if ( $is_taxable ) {
				$tax_rates = WC()->cart->tax->get_rates();
				$fee_taxes = WC()->cart->tax->calc_tax( $fee, $tax_rates, true );
				$fee = $fee - array_sum( $fee_taxes );
			}
			WC()->cart->add_fee( __( 'Payment charge', 'woocommerce-germanized' ), $fee, $is_taxable );
		}
	}

	/**
	 * Adds a fee notice to the payment method title
	 *  
	 * @param  string $title 
	 * @param  int $id    payment method id
	 */
	public function manipulate_title( $title, $id ) {
		if ( $id == $this->id && $this->get_option( 'fee' ) && ( is_checkout() || ( defined( 'DOING_AJAX' ) && isset( $_POST[ 'action' ] ) && $_POST[ 'action' ] == 'woocommerce_update_order_review' ) ) )
			$title = $this->title . ' <span class="small">(' . sprintf( __( 'plus %s payment charge', 'woocommerce-germanized' ), wc_price( $this->get_option( 'fee' ) ) ) . ')</span>';
		return $title;
	}

}

?>