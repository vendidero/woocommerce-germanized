<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper to retrieve delivery times.
 *
 * @class    WC_GZD_Delivery_Times
 * @version  3.8.0
 * @author   vendidero
 */
class WC_GZD_Delivery_Times extends WC_GZD_Taxonomy {

	public function __construct() {
		parent::__construct( 'product_delivery_time' );
	}

	public function get_delivery_time( $key, $by = 'slug' ) {
		return parent::get_term( $key, $by );
	}

	public function get_delivery_time_term( $key, $by = 'slug' ) {
		return parent::get_term_object( $key, $by );
	}

	public function get_delivery_times( $args = array() ) {
		return $this->get_terms( $args );
	}
}
