<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Helper to retrieve manufacturers.
 *
 * @class    WC_GZD_Manufacturers
 * @version  3.18.0
 * @author   vendidero
 */
class WC_GZD_Manufacturers extends WC_GZD_Taxonomy {

	public function __construct() {
		parent::__construct( 'product_manufacturer' );
	}

	/**
	 * @param $key
	 * @param $by
	 *
	 * @return false|WC_GZD_Manufacturer
	 */
	public function get_manufacturer( $key, $by = 'slug' ) {
		$term = parent::get_term( $key, $by );

		if ( ! is_a( $term, 'WP_Term' ) ) {
			return false;
		}

		return wc_gzd_get_manufacturer( $term );
	}

	public function get_manufacturer_term( $key, $by = 'slug' ) {
		return parent::get_term_object( $key, $by );
	}

	public function get_manufacturers( $args = array() ) {
		return $this->get_terms( $args );
	}
}
