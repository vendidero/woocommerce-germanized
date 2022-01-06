<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Units Class stores units/measurements data.
 *
 * @class WC_Germanized_Units
 * @version  1.0.0
 * @author   Vendidero
 */
class WC_GZD_Units extends WC_GZD_Taxonomy {

	/**
	 * Adds the units from i18n template
	 */
	public function __construct() {
		parent::__construct( 'product_unit' );
	}

	public function get_unit( $key, $by = 'slug' ) {
		return parent::get_term( $key, $by );
	}

	public function get_unit_term( $key, $by = 'slug' ) {
		return parent::get_term_object( $key, $by );
	}

	public function get_units( $args = array() ) {
		return $this->get_terms( $args );
	}
}
