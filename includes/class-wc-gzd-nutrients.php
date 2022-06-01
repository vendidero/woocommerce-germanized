<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper to retrieve nutrients.
 *
 * @class    WC_GZD_Nutrients
 * @version  3.9.0
 * @author   vendidero
 */
class WC_GZD_Nutrients extends WC_GZD_Taxonomy {

	public function __construct() {
		parent::__construct( 'product_nutrient' );
	}

	public function get_nutrient( $key, $by = 'slug' ) {
		return parent::get_term( $key, $by );
	}

	public function get_nutrient_term( $key, $by = 'slug' ) {
		return parent::get_term_object( $key, $by );
	}

	public function get_nutrients( $args = array() ) {
		return $this->get_terms( $args );
	}

	public function get_nutrient_object( $term ) {
		return apply_filters( 'woocommerce_gzd_get_nutrient_object', false, $term );
	}
}
