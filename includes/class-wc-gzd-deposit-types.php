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

	protected function format_term_value( $value_to_extract, $term ) {
		if ( 'name' === $value_to_extract ) {
			$term_value = sprintf( _x( '%1$s (%2$s, %3$s)', 'deposit-type-title', 'woocommerce-germanized' ), $term->name, $this->get_packaging_type_title( $term ), wp_strip_all_tags( wc_price( $this->get_deposit( $term ) ) ) );
		} else {
			$term_value = parent::format_term_value( $value_to_extract, $term );
		}

		return $term_value;
	}

	public function get_deposit_types( $args = array() ) {
		return $this->get_terms( $args );
	}

	public function get_packaging_types() {
		return apply_filters(
			'woocommerce_gzd_deposit_packaging_types',
			array(
				'reusable'   => _x( 'Reusable', 'deposit-packaging-type', 'woocommerce-germanized' ),
				'disposable' => _x( 'Disposable', 'deposit-packaging-type', 'woocommerce-germanized' ),
			)
		);
	}

	public function get_tax_statuses() {
		return array(
			'taxable' => _x( 'Taxable', 'deposit-tax-status', 'woocommerce-germanized' ),
			'none'    => _x( 'None', 'deposit-tax-status', 'woocommerce-germanized' ),
		);
	}

	public function get_packaging_type( $term ) {
		$packaging_types = $this->get_packaging_types();
		$packaging_type  = false;

		if ( ! is_a( $term, 'WP_Term' ) ) {
			$term = $this->get_deposit_type_term( $term );
		}

		if ( $term ) {
			$packaging_type_term = get_term_meta( $term->term_id, 'deposit_packaging_type', true );

			if ( array_key_exists( $packaging_type_term, $packaging_types ) ) {
				$packaging_type = $packaging_type_term;
			}
		}

		return $packaging_type;
	}

	public function get_packaging_type_title( $type ) {
		if ( is_a( $type, 'WP_Term' ) ) {
			$type = $this->get_packaging_type( $type );
		}

		$packaging_types = $this->get_packaging_types();
		$title           = _x( 'None', 'deposit-packaging-type', 'woocommerce-germanized' );

		if ( array_key_exists( $type, $packaging_types ) ) {
			$title = $packaging_types[ $type ];
		}

		return apply_filters( 'woocommerce_gzd_deposit_packaging_type_title', $title, $type );
	}

	public function get_tax_status( $term ) {
		$tax_status = 'taxable';

		if ( ! is_a( $term, 'WP_Term' ) ) {
			$term = $this->get_deposit_type_term( $term );
		}

		if ( $term ) {
			$tax_status = get_term_meta( $term->term_id, 'deposit_tax_status', true );

			if ( empty( $tax_status ) ) {
				$tax_status = 'taxable';
			}
		}

		return $tax_status;
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
