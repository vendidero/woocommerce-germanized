<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper to retrieve deposit types.
 *
 * @class    WC_GZD_Deposit_Types
 * @version  3.9.0
 * @author   vendidero
 */
class WC_GZD_Deposit_Types extends WC_GZD_Taxonomy {

	public function __construct() {
		parent::__construct( 'product_deposit_type' );
	}

	public function get_deposit_type( $key, $by = 'slug' ) {
		return parent::get_term( $key, $by );
	}

	public function get_deposit_type_term( $key, $by = 'slug' ) {
		return parent::get_term_object( $key, $by );
	}

	public function get_deposit_types( $args = array() ) {
		return $this->get_terms( $args );
	}

	public function get_deposit( $term ) {
		$deposit = 0;

		if ( ! is_a( $term, 'WP_Term' ) ) {
			$term = $this->get_deposit_type_term( $term );
		}

		if ( ! $term ) {
			return $deposit;
		}

		$deposit = get_term_meta( $term->term_id, 'deposit', true );

		if ( empty( $deposit ) ) {
			$deposit = 0;
		}

		return wc_format_decimal( $deposit, '' );
	}
}
