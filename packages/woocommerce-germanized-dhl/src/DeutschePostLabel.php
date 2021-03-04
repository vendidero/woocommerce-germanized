<?php

namespace Vendidero\Germanized\DHL;

defined( 'ABSPATH' ) || exit;

/**
 * Deutsche Post Label class.
 */
class DeutschePostLabel extends Label {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'page_format'         => '',
		'shop_order_id'       => '',
		'stamp_total'         => 0,
		'voucher_id'          => '',
		'original_url'        => '',
		'manifest_url'        => '',
		'additional_services' => array(),
		'wp_int_awb'          => '',
		'wp_int_barcode'      => '',
	);

	public function get_type() {
		return 'deutsche_post';
	}

	public function get_number( $context = 'view' ) {
		$number = parent::get_number( $context );

		return $number;
	}

	public function get_page_format( $context = 'view' ) {
		return $this->get_prop( 'page_format', $context );
	}

	public function get_wp_int_awb( $context = 'view' ) {
		return $this->get_prop( 'wp_int_awb', $context );
	}

	public function get_wp_int_barcode( $context = 'view' ) {
		return $this->get_prop( 'wp_int_barcode', $context );
	}

	public function get_additional_services( $context = 'view' ) {
		return $this->get_prop( 'additional_services', $context );
	}

	public function set_additional_services( $value ) {
		$this->set_prop( 'additional_services', (array) $value );
	}

	public function set_page_format( $value ) {
		$this->set_prop( 'page_format', $value );
	}

	public function set_wp_int_awb( $value ) {
		$this->set_prop( 'wp_int_awb', $value );
	}

	public function set_wp_int_barcode( $value ) {
		$this->set_prop( 'wp_int_barcode', $value );
	}

	public function get_stamp_total( $context = 'view' ) {
		return $this->get_prop( 'stamp_total', $context );
	}

	public function set_stamp_total( $value ) {
		$this->set_prop( 'stamp_total', absint( $value ) );
	}

	public function get_shop_order_id( $context = 'view' ) {
		return $this->get_prop( 'shop_order_id', $context );
	}

	public function set_shop_order_id( $value ) {
		$this->set_prop( 'shop_order_id', $value );
	}

	public function set_dhl_product( $product ) {
		$this->set_prop( 'dhl_product', $product );
	}

	public function get_voucher_id( $context = 'view' ) {
		return $this->get_prop( 'voucher_id', $context );
	}

	public function set_voucher_id( $value ) {
		$this->set_prop( 'voucher_id', $value );
	}

	public function get_original_url( $context = 'view' ) {
		return $this->get_prop( 'original_url', $context );
	}

	public function set_original_url( $value ) {
		$this->set_prop( 'original_url', $value );
	}

	public function get_manifest_url( $context = 'view' ) {
		return $this->get_prop( 'manifest_url', $context );
	}

	public function set_manifest_url( $value ) {
		$this->set_prop( 'manifest_url', $value );
	}

	public function is_warenpost_international() {
		if ( ! empty( $this->get_wp_int_awb() ) ) {
			return true;
		} elseif ( $api = Package::get_internetmarke_api() ) {
			return $api->is_warenpost_international( $this->get_dhl_product() );
		}

		return false;
	}

	public function is_trackable() {
		$voucher_id   = $this->get_voucher_id();
		$is_trackable = false;

		if ( ! empty( $voucher_id ) && $voucher_id !== $this->get_number() ) {
			$is_trackable = true;
		} elseif ( in_array( $this->get_dhl_product(), [ 195, 196, 197, 198, 199, 200, 1007, 1017, 1027, 1037, 1047, 1057 ] ) ) {
			$is_trackable = true;
		} elseif( ! empty( $this->get_wp_int_barcode() ) && in_array( 'TRCK', $this->get_additional_services() ) ) {
			$is_trackable = true;
		}

		return apply_filters( 'woocommerce_gzd_deutsche_post_label_is_trackable', $is_trackable, $this );
	}
}
