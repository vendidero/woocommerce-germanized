<?php

class WC_GZD_Shipping_Rate extends WC_Shipping_Rate {

	public $tax_shares = array();

	public function __construct( WC_Shipping_Rate $rate ) {
		parent::__construct( $rate->id, $rate->label, $rate->cost, $rate->taxes, $rate->method_id );
		
		if ( get_option( 'woocommerce_gzd_shipping_tax' ) === 'yes' && ( ! empty( $rate->taxes ) || get_option( 'woocommerce_gzd_shipping_tax_force' ) === 'yes' ) )
			$this->set_taxes();
		
		$this->set_costs();
	}

	public function set_taxes() {

		$cart = WC()->cart;
		$this->tax_shares = wc_gzd_get_cart_tax_share();

		// Calculate tax class share
		if ( ! empty( $this->tax_shares ) ) {
		
			foreach ( $this->tax_shares as $rate => $class ) {
				$tax_rates  = WC_Tax::get_rates( $rate );
				$this->tax_shares[ $rate ][ 'shipping_tax_share' ] = $this->cost * $class[ 'share' ];
				$this->tax_shares[ $rate ][ 'shipping_tax' ] = WC_Tax::calc_tax( ( $this->cost * $class[ 'share' ] ), $tax_rates, true );
			}
		
			$this->taxes = array();
		
			foreach ( $this->tax_shares as $rate => $class ) {
				$this->taxes = $this->taxes + $class[ 'shipping_tax' ];
			}
		}
	}

	public function set_costs() {
		$this->cost = $this->cost - array_sum( $this->taxes );
	}

	public function get_taxes() {
		return $this->taxes;
	}

}

?>