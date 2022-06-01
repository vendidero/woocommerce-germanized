<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Shipping_Rate extends WC_Shipping_Rate {

	public $tax_shares = array();

	public function __construct( WC_Shipping_Rate $rate ) {
		$num = 5;

		try {
			$method = new ReflectionMethod( 'WC_Shipping_Rate', '__construct' );
			$num    = $method->getNumberOfParameters();
		} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch

		}

		if ( 6 === $num ) {
			parent::__construct( $rate->id, $rate->label, $rate->cost, $rate->taxes, $rate->method_id, $rate->instance_id );
		} else {
			parent::__construct( $rate->id, $rate->label, $rate->cost, $rate->taxes, $rate->method_id );
		}

		wc_deprecated_function( 'WC_GZD_Shipping_Rate::__construct', '3.3.4' );
	}

	public function set_shared_taxes() {
		wc_deprecated_function( 'WC_GZD_Shipping_Rate::set_shares_taxes', '3.3.4' );
	}

	public function set_costs() {
		wc_deprecated_function( 'WC_GZD_Shipping_Rate::set_costs', '3.3.4' );
	}

	public function get_shared_taxes() {
		wc_deprecated_function( 'WC_GZD_Shipping_Rate::get_shared_taxes', '3.3.4' );
	}
}

