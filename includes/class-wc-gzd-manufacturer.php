<?php

defined( 'ABSPATH' ) || exit;

class WC_GZD_Manufacturer {

	/**
	 * The actual manufacturer term object
	 *
	 * @var WP_Term
	 */
	protected $manufacturer;

	/**
	 * @param WP_Term $manufacturer
	 */
	public function __construct( $manufacturer ) {
		$this->manufacturer = $manufacturer;
	}

	/**
	 * Returns the manufacturer term object
	 *
	 * @return WP_Term
	 */
	public function get_manufacturer() {
		return $this->manufacturer;
	}

	public function get_id() {
		return $this->manufacturer->term_id;
	}

	public function get_slug() {
		return $this->manufacturer->slug;
	}

	public function get_name() {
		return $this->manufacturer->name;
	}

	protected function get_hook_prefix() {
		return 'woocommerce_gzd_manufacturer_get';
	}

	public function get_formatted_address() {
		$formatted_address = get_term_meta( $this->get_id(), 'formatted_address', true );

		return apply_filters( "{$this->get_hook_prefix()}_formatted_address", $formatted_address, $this );
	}

	public function get_formatted_eu_address() {
		$formatted_address = get_term_meta( $this->get_id(), 'formatted_eu_address', true );

		return apply_filters( "{$this->get_hook_prefix()}_formatted_eu_address", $formatted_address, $this );
	}

	public function has_eu_address() {
		return ! empty( $this->get_formatted_eu_address() );
	}

	public function __set( $option, $value ) {
		$this->manufacturer->{ $option } = $value;
	}

	public function __get( $option ) {
		return $this->manufacturer->{ $option };
	}

	public function __isset( $option ) {
		return isset( $this->manufacturer->{ $option } );
	}
}
