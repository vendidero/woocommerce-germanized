<?php

namespace Vendidero\Germanized\DHL\Label;

use Vendidero\Germanized\DHL\Package;

defined( 'ABSPATH' ) || exit;

/**
 * Deutsche Post Label class.
 */
class DeutschePost extends Label {

	/**
	 * Stores product data.
	 *
	 * @var array
	 */
	protected $extra_data = array(
		'page_format'    => '',
		'position_x'     => 1,
		'position_y'     => 1,
		'shop_order_id'  => '',
		'stamp_total'    => 0,
		'voucher_id'     => '',
		'original_url'   => '',
		'manifest_url'   => '',
		'wp_int_awb'     => '',
		'wp_int_barcode' => '',
	);

	public function __construct( $data = 0, $legacy = false ) {
		if ( $legacy ) {
			$this->extra_data['additional_services'] = array();
		}

		parent::__construct( $data, $legacy );
	}

	public function get_type() {
		return 'simple';
	}

	public function get_shipping_provider( $context = 'view' ) {
		return 'deutsche_post';
	}

	public function get_number( $context = 'view' ) {
		$number = parent::get_number( $context );

		return $number;
	}

	public function get_page_format( $context = 'view' ) {
		return $this->get_prop( 'page_format', $context );
	}

	public function get_position_x( $context = 'view' ) {
		return $this->get_prop( 'position_x', $context );
	}

	public function get_position_y( $context = 'view' ) {
		return $this->get_prop( 'position_y', $context );
	}

	public function get_wp_int_awb( $context = 'view' ) {
		return $this->get_prop( 'wp_int_awb', $context );
	}

	public function get_wp_int_barcode( $context = 'view' ) {
		return $this->get_prop( 'wp_int_barcode', $context );
	}

	public function get_services( $context = 'view' ) {
		if ( $this->legacy ) {
			return $this->get_additional_services( $context );
		}

		return parent::get_services( $context );
	}

	public function set_services( $services ) {
		if ( $this->legacy ) {
			$this->set_additional_services( $services );
		} else {
			parent::set_services( $services );
		}
	}

	public function get_additional_services( $context = 'view' ) {
		if ( $this->legacy ) {
			return $this->get_prop( 'additional_services', $context );
		} else {
			return $this->get_services( $context );
		}
	}

	public function set_additional_services( $value ) {
		if ( $this->legacy ) {
			$this->set_prop( 'additional_services', (array) $value );
		} else {
			$this->set_services( $value );
		}
	}

	public function set_page_format( $value ) {
		$this->set_prop( 'page_format', $value );
	}

	public function set_position_x( $value ) {
		$this->set_prop( 'position_x', absint( $value ) );
	}

	public function set_position_y( $value ) {
		$this->set_prop( 'position_y', absint( $value ) );
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
			return $api->is_warenpost_international( $this->get_product_id() );
		}

		return false;
	}

	public function is_trackable() {
		$voucher_id   = $this->get_voucher_id();
		$is_trackable = false;
		$services     = $this->get_additional_services();

		if ( ! empty( $voucher_id ) && $voucher_id !== $this->get_number() ) {
			$is_trackable = true;
		} elseif ( in_array( (int) $this->get_product_id(), array( 1, 21, 11, 31, 195, 196, 197, 198, 199, 200, 1007, 1017, 1027, 1037, 1047, 1057 ), true ) ) {
			$is_trackable = true;
		} elseif ( ! empty( $services ) && ! empty( array_intersect( array( 'ESEW', 'ESCH', 'ESEH' ), $services ) ) ) {
			$is_trackable = true;
		} elseif ( ! empty( $this->get_wp_int_barcode() ) && ( in_array( 'TRCK', $this->get_services(), true ) || 'RC' === strtoupper( substr( $this->get_wp_int_barcode(), 0, 2 ) ) ) ) {
			$is_trackable = true;
		}

		return apply_filters( "{$this->get_general_hook_prefix()}is_trackable", $is_trackable, $this );
	}

	/**
	 * @return \WP_Error|true
	 */
	public function fetch() {
		$result = new \WP_Error();

		try {
			Package::get_internetmarke_api()->get_label( $this );
		} catch ( \Exception $e ) {
			$result->add( 'deutsche-post-api-error', $e->getMessage() );
		}

		if ( wc_gzd_dhl_wp_error_has_errors( $result ) ) {
			return $result;
		} else {
			return true;
		}
	}

	public function delete( $force_delete = false ) {
		if ( $api = Package::get_internetmarke_api() ) {
			try {
				$api->delete_label( $this );
			} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
			}
		}

		return parent::delete( $force_delete );
	}
}
