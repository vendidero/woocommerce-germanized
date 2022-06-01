<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper to retrieve allergenic.
 *
 * @class    WC_GZD_Allergenic
 * @version  3.9.0
 * @author   vendidero
 */
class WC_GZD_Allergenic extends WC_GZD_Taxonomy {

	public function __construct() {
		parent::__construct( 'product_allergen' );
	}

	public function get_allergen( $key, $by = 'slug' ) {
		return parent::get_term( $key, $by );
	}

	public function get_allergen_term( $key, $by = 'slug' ) {
		return parent::get_term_object( $key, $by );
	}

	public function get_allergenic( $args = array() ) {
		return $this->get_terms( $args );
	}

	public function get_allergen_object( $term ) {
		return apply_filters( 'woocommerce_gzd_get_allergen_object', false, $term );
	}
}
