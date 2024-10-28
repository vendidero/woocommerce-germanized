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

	public function get_formatted_address( $context = 'view' ) {
		$formatted_address = get_term_meta( $this->get_id(), 'formatted_address', true );

		if ( 'view' === $context && ! empty( $formatted_address ) ) {
			$formatted_address = apply_filters( "{$this->get_hook_prefix()}_formatted_address", $this->format_address( $formatted_address ), $this );
		}

		return $formatted_address;
	}

	private function trim_formatted_address_line( $line ) {
		return trim( $line, ', ' );
	}

	protected function format_address( $formatted_address ) {
		// Clean up white space.
		$formatted_address = preg_replace( '/  +/', ' ', trim( $formatted_address ) );
		$formatted_address = preg_replace( '/\n\n+/', "\n", $formatted_address );

		// Break newlines apart and remove empty lines/trim commas and white space.
		$formatted_address = array_filter( array_map( array( $this, 'trim_formatted_address_line' ), explode( "\n", $formatted_address ) ) );

		// Add html breaks.
		$formatted_address = implode( '<br/>', $formatted_address );
		$formatted_address = make_clickable( $formatted_address );

		return $formatted_address;
	}

	public function get_formatted_eu_address( $context = 'view' ) {
		$formatted_address = get_term_meta( $this->get_id(), 'formatted_eu_address', true );

		if ( 'view' === $context && ! empty( $formatted_address ) ) {
			$formatted_address = apply_filters( "{$this->get_hook_prefix()}_formatted_eu_address", $this->format_address( $formatted_address ), $this );
		}

		return $formatted_address;
	}

	public function get_html() {
		$html = '';

		if ( $this->get_formatted_address() ) {
			$html .= '<p class="wc-gzd-manufacturer-address">' . wp_kses_post( $this->get_formatted_address() ) . '</p>';
		}

		if ( $this->has_eu_address() ) {
			$html .= '<h4 class="wc-gzd-manufacturer-eu-title">' . __( 'Person responsible for the EU', 'woocommerce-germanized' ) . '</h4>';
			$html .= '<p class="wc-gzd-manufacturer-eu-address">' . wp_kses_post( $this->get_formatted_eu_address() ) . '</p>';
		}

		return $html;
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
