<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The Price Labels Class stores labels to be added as price suffixes.
 *
 * @class WC_Germanized_Price_Labels
 * @version  1.0.0
 * @author   Vendidero
 */
class WC_GZD_Price_Labels extends WC_GZD_Taxonomy {

	/**
	 * Adds the units from i18n template
	 */
	public function __construct() {
		parent::__construct( 'product_price_label' );
	}

	public function get_label( $key, $by = 'slug' ) {
		return parent::get_term( $key, $by );
	}

	public function get_label_term( $key, $by = 'slug' ) {
		return parent::get_term_object( $key, $by );
	}

	public function get_labels( $args = array() ) {
		return $this->get_terms( $args );
	}
}
