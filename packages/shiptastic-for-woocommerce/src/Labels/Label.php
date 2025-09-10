<?php

namespace Vendidero\Shiptastic\Labels;

use Vendidero\Shiptastic\Interfaces\ShipmentLabel;
use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\ShipmentError;
use WC_Data;
use WC_Data_Store;
use Exception;
use WC_DateTime;

defined( 'ABSPATH' ) || exit;

/**
 * Label class.
 */
class Label extends WC_Data implements ShipmentLabel {

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'shipment_label';

	/**
	 * Contains the data store name.
	 *
	 * @var string
	 */
	protected $data_store_name = 'shipment-label';

	/**
	 * Stores meta in cache for future reads.
	 * A group must be set to to enable caching.
	 *
	 * @var string
	 */
	protected $cache_group = 'shipment-labels';

	/**
	 * @var Shipment
	 */
	private $shipment = null;

	/**
	 * Stores shipment data.
	 *
	 * @var array
	 */
	protected $data = array(
		'date_created'            => null,
		'shipment_id'             => 0,
		'product_id'              => '',
		'parent_id'               => 0,
		'number'                  => '',
		'shipping_provider'       => '',
		'weight'                  => '',
		'net_weight'              => '',
		'length'                  => '',
		'width'                   => '',
		'height'                  => '',
		'path'                    => '',
		'plain_path'              => '',
		'created_via'             => '',
		'services'                => array(),
		'print_format'            => '',
		'export_reference_number' => '',
	);

	public function __construct( $data = 0 ) {
		parent::__construct( $data );

		if ( $data instanceof ShipmentLabel ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) ) {
			$this->set_id( $data );
		}

		$this->data_store = WC_Data_Store::load( $this->data_store_name );

		// If we have an ID, load the user from the DB.
		if ( $this->get_id() ) {
			try {
				$this->data_store->read( $this );
			} catch ( Exception $e ) {
				$this->set_id( 0 );
				$this->set_object_read( true );
			}
		} else {
			$this->set_object_read( true );
		}
	}

	/**
	 * This method overwrites the base class's clone method to make it a no-op. In base class WC_Data, we are unsetting the meta_id to clone.
	 *
	 * @see WC_Abstract_Order::__clone()
	 */
	public function __clone() {}

	public function get_type() {
		return 'simple';
	}

	/**
	 * Merge changes with data and clear.
	 * Overrides WC_Data::apply_changes.
	 * array_replace_recursive does not work well for license because it merges domains registered instead
	 * of replacing them.
	 *
	 */
	public function apply_changes() {
		if ( function_exists( 'array_replace' ) ) {
			$this->data = array_replace( $this->data, $this->changes ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctions.array_replaceFound
		} else { // PHP 5.2 compatibility.
			foreach ( $this->changes as $key => $change ) {
				$this->data[ $key ] = $change;
			}
		}
		$this->changes = array();
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_general_hook_prefix() {
		$prefix = 'simple' === $this->get_type() ? '' : $this->get_type() . '_';

		return "woocommerce_shiptastic_shipment_{$prefix}label_";
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @return string
	 */
	protected function get_hook_prefix() {
		return $this->get_general_hook_prefix() . 'get_';
	}

	/**
	 * Return the date this license was created.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	public function get_shipment_id( $context = 'view' ) {
		return $this->get_prop( 'shipment_id', $context );
	}

	public function get_shipping_provider( $context = 'view' ) {
		return $this->get_prop( 'shipping_provider', $context );
	}

	public function get_shipping_provider_instance() {
		$provider = $this->get_shipping_provider();

		if ( ! empty( $provider ) ) {
			return wc_stc_get_shipping_provider( $provider );
		}

		return false;
	}

	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	public function get_created_via( $context = 'view' ) {
		return $this->get_prop( 'created_via', $context );
	}

	public function get_product_id( $context = 'view' ) {
		return $this->get_prop( 'product_id', $context );
	}

	public function get_number( $context = 'view' ) {
		return $this->get_prop( 'number', $context );
	}

	public function get_print_format( $context = 'view' ) {
		return $this->get_prop( 'print_format', $context );
	}

	public function get_export_reference_number( $context = 'view' ) {
		return $this->get_prop( 'export_reference_number', $context );
	}

	public function has_number() {
		$number = $this->get_number();

		return empty( $number ) ? false : true;
	}

	/**
	 * Returns the weight in kg
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_weight( $context = 'view' ) {
		return $this->get_prop( 'weight', $context );
	}

	public function get_net_weight( $context = 'view' ) {
		$weight = $this->get_prop( 'net_weight', $context );

		if ( 'view' === $context && '' === $weight ) {
			$weight = $this->get_weight( $context );
		}

		return $weight;
	}

	/**
	 * Returns the length in cm
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_length( $context = 'view' ) {
		return $this->get_prop( 'length', $context );
	}

	/**
	 * Returns the width in cm
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_width( $context = 'view' ) {
		return $this->get_prop( 'width', $context );
	}

	/**
	 * Returns the height in cm
	 *
	 * @param string $context
	 *
	 * @return string
	 */
	public function get_height( $context = 'view' ) {
		return $this->get_prop( 'height', $context );
	}

	public function get_dimensions( $context = 'view' ) {
		return array(
			'length' => $this->get_length( $context ),
			'width'  => $this->get_width( $context ),
			'height' => $this->get_height( $context ),
		);
	}

	public function has_dimensions() {
		$width  = $this->get_width();
		$length = $this->get_length();
		$height = $this->get_height();

		return ( ! empty( $width ) && ! empty( $length ) && ! empty( $height ) );
	}

	public function get_path( $context = 'view', $file_path = '' ) {
		$path_name = empty( $file_path ) ? 'path' : "{$file_path}_path";

		return $this->get_prop( $path_name, $context );
	}

	public function get_plain_path( $context = 'view' ) {
		return $this->get_path( $context, 'plain' );
	}

	public function get_services( $context = 'view' ) {
		return $this->get_prop( 'services', $context );
	}

	public function has_service( $service ) {
		return ( in_array( $service, $this->get_services(), true ) );
	}

	/**
	 * Retrieve additional data for a certain service, e.g.
	 * retrieve the min_age for the DHL VisualCheckOfAge service.
	 *
	 * @param $service
	 * @param $prop
	 * @param $default_value
	 *
	 * @return mixed
	 */
	public function get_service_prop( $service, $prop, $default_value = null, $context = 'view' ) {
		$meta_key = "service_{$service}_{$prop}";

		if ( $this->get_meta( $meta_key, true, $context ) ) {
			return $this->get_meta( $meta_key, true, $context );
		} else {
			return $default_value;
		}
	}

	public function get_shipment() {
		if ( is_null( $this->shipment ) ) {
			$this->shipment = ( $this->get_shipment_id() > 0 ? wc_stc_get_shipment( $this->get_shipment_id() ) : false );
		}

		return $this->shipment;
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set the date this license was last updated.
	 *
	 * @param  string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	public function set_number( $number ) {
		$this->set_prop( 'number', $number );
	}

	public function set_product_id( $number ) {
		$this->set_prop( 'product_id', $number );
	}

	public function set_print_format( $format ) {
		$this->set_prop( 'print_format', $format );
	}

	public function set_export_reference_number( $ref_number ) {
		$this->set_prop( 'export_reference_number', $ref_number );
	}

	public function set_shipping_provider( $slug ) {
		$this->set_prop( 'shipping_provider', $slug );
	}

	public function set_parent_id( $id ) {
		$this->set_prop( 'parent_id', absint( $id ) );
	}

	public function set_created_via( $created_via ) {
		$this->set_prop( 'created_via', $created_via );
	}

	public function set_weight( $weight ) {
		$this->set_prop( 'weight', '' !== $weight ? wc_format_decimal( $weight ) : '' );
	}

	public function set_net_weight( $weight ) {
		$this->set_prop( 'net_weight', '' !== $weight ? wc_format_decimal( $weight ) : '' );
	}

	public function set_width( $width ) {
		$this->set_prop( 'width', '' !== $width ? wc_format_decimal( $width ) : '' );
	}

	public function set_length( $length ) {
		$this->set_prop( 'length', '' !== $length ? wc_format_decimal( $length ) : '' );
	}

	public function set_height( $height ) {
		$this->set_prop( 'height', '' !== $height ? wc_format_decimal( $height ) : '' );
	}

	public function set_path( $path, $file_type = '' ) {
		$path_name = empty( $file_type ) ? 'path' : "{$file_type}_path";

		$this->set_prop( $path_name, $path );
	}

	public function set_plain_path( $path ) {
		$this->set_path( $path, 'plain' );
	}

	public function set_services( $services ) {
		$this->set_prop( 'services', empty( $services ) ? array() : (array) $services );
	}

	/**
	 * Returns linked children labels.
	 *
	 * @return ShipmentLabel[]
	 */
	public function get_children() {
		return wc_stc_get_shipment_labels( array( 'parent_id' => $this->get_id() ) );
	}

	public function has_children() {
		$children = $this->get_children();

		return count( $children ) > 0 ? true : false;
	}

	public function add_service( $service ) {
		$services           = (array) $this->get_services();
		$available_services = array();

		if ( $provider = $this->get_shipping_provider_instance() ) {
			if ( $shipment = $this->get_shipment() ) {
				$available_services = $provider->get_available_label_services( $shipment );
			}
		}

		if ( ! in_array( $service, $services, true ) && in_array( $service, $available_services, true ) ) {
			$services[] = $service;
			$this->set_services( $services );

			return true;
		}

		return false;
	}

	public function remove_service( $service ) {
		$services = (array) $this->get_services();

		if ( in_array( $service, $services, true ) ) {
			$services = array_diff( $services, array( $service ) );

			$this->set_services( $services );
			return true;
		}

		return false;
	}

	public function supports_additional_file_type( $file_type ) {
		return in_array( $file_type, $this->get_additional_file_types(), true );
	}

	public function get_additional_file_types() {
		return array(
			'plain',
		);
	}

	public function get_plain_file() {
		if ( ! $this->get_path( 'view', 'plain' ) ) {
			return $this->get_file();
		}

		return $this->get_file( 'plain' );
	}

	public function get_file( $file_type = '' ) {
		if ( ! $path = $this->get_path( 'view', $file_type ) ) {
			return false;
		}

		return $this->get_file_by_path( $path );
	}

	public function get_stream( $file_type = '' ) {
		if ( ! $this->get_file( $file_type ) ) {
			return '';
		}

		try {
			$result = file_get_contents( $this->get_path( $file_type ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		} catch ( \Exception $e ) {
			$result = '';
		}

		if ( ! is_string( $result ) ) {
			$result = '';
		}

		return $result;
	}

	protected function get_new_filename( $file_type = '' ) {
		$file_parts = array(
			$this->get_shipping_provider(),
		);

		if ( ! empty( $file_type ) ) {
			$file_parts[] = $file_type;
		}

		if ( 'simple' !== $this->get_type() ) {
			$file_parts[] = $this->get_type();
		}

		$file_parts[] = $this->get_shipment_id();

		$filename_default = implode( '-', $file_parts );
		$filename_default = $filename_default . '.pdf';
		$filename         = apply_filters( "{$this->get_hook_prefix()}filename", $filename_default, $this, $file_type );

		return sanitize_file_name( $filename );
	}

	public function get_filename( $file_type = '' ) {
		if ( ! $path = $this->get_path( 'view', $file_type ) ) {
			return $this->get_new_filename( $file_type );
		}

		return basename( $path );
	}

	protected function get_file_by_path( $file ) {
		// If the file is relative, prepend upload dir.
		if ( $file && 0 !== strpos( $file, '/' ) && ( ( $uploads = Package::get_upload_dir() ) && false === $uploads['error'] ) ) {
			$file = $uploads['basedir'] . "/$file";

			return $file;
		} else {
			return false;
		}
	}

	public function set_shipment_id( $shipment_id ) {
		// Reset order object
		$this->shipment = null;

		$this->set_prop( 'shipment_id', absint( $shipment_id ) );
	}

	/**
	 * @param Shipment $shipment
	 */
	public function set_shipment( &$shipment ) {
		$this->shipment = $shipment;

		$this->set_prop( 'shipment_id', absint( $shipment->get_id() ) );
	}

	public function get_download_url( $args = array() ) {
		$base_url     = is_admin() ? admin_url() : trailingslashit( home_url() );
		$download_url = add_query_arg(
			array(
				'action'      => 'wc-stc-download-shipment-label',
				'shipment_id' => $this->get_shipment_id(),
			),
			wp_nonce_url( $base_url, 'download-shipment-label' )
		);

		foreach ( $args as $arg => $val ) {
			if ( is_bool( $val ) ) {
				$args[ $arg ] = wc_bool_to_string( $val );
			}
		}

		$download_url = add_query_arg( $args, $download_url );

		/**
		 * Filter for shipping providers to adjust the label download URL.
		 *
		 * The dynamic portion of this hook, `$this->get_hook_prefix()` is used to construct a
		 * unique hook for a shipment type. `$provider` is related to the current shipping provider
		 * for the shipment (slug).
		 *
		 * Example hook name: `woocommerce_stc_return_shipment_get_dhl_label_download_url`
		 *
		 * @param string $url The download URL.
		 * @param Label  $label The current shipment instance.
		 *
		 * @package Vendidero/Shiptastic
		 */
		return esc_url_raw( apply_filters( "{$this->get_hook_prefix()}download_url", $download_url, $this ) );
	}

	/**
	 * @return ShipmentError|true
	 */
	public function fetch() {
		$result = new ShipmentError( 'label-fetch-error', _x( 'This label misses the API implementation', 'shipments', 'woocommerce-germanized' ) );

		return $result;
	}

	/**
	 * @param $stream
	 * @param string $file_type
	 *
	 * @return false|string
	 */
	public function upload_label_file( $stream, $file_type = '' ) {
		try {
			Package::set_upload_dir_filter();
			$filename = $this->get_filename( $file_type );

			$GLOBALS['stc_unique_filename'] = $filename;
			add_filter( 'wp_unique_filename', '_wc_shiptastic_keep_force_filename', 10, 1 );

			$tmp = wp_upload_bits( $this->get_filename( $file_type ), null, $stream );

			unset( $GLOBALS['stc_unique_filename'] );
			remove_filter( 'wp_unique_filename', '_wc_shiptastic_keep_force_filename', 10 );

			Package::unset_upload_dir_filter();

			if ( isset( $tmp['file'] ) ) {
				$path = $tmp['file'];
				$path = Package::get_relative_upload_dir( $path );

				$this->set_path( $path, $file_type );

				return $path;
			} else {
				throw new Exception( _x( 'Error while uploading label.', 'shipments', 'woocommerce-germanized' ) );
			}
		} catch ( Exception $e ) {
			return false;
		}
	}

	/**
	 * @param $url
	 * @param string $file_type
	 *
	 * @return false|string
	 */
	public function download_label_file( $url, $file_type = '' ) {
		$timeout_seconds = 5;

		try {
			if ( ! function_exists( 'download_url' ) ) {
				include_once ABSPATH . 'wp-admin/includes/file.php';
			}

			if ( ! function_exists( 'download_url' ) ) {
				throw new \Exception( esc_html_x( 'Error while downloading the PDF file.', 'shipments', 'woocommerce-germanized' ) );
			}

			// Download file to temp dir.
			$temp_file = download_url( $url, $timeout_seconds );

			if ( is_wp_error( $temp_file ) ) {
				throw new \Exception( esc_html_x( 'Error while downloading the PDF file.', 'shipments', 'woocommerce-germanized' ) );
			}

			$file = array(
				'name'     => $this->get_filename( $file_type ),
				'type'     => 'application/pdf',
				'tmp_name' => $temp_file,
				'error'    => 0,
				'size'     => filesize( $temp_file ),
			);

			$overrides = array(
				'test_type' => false,
				'test_form' => false,
				'test_size' => true,
			);

			// Move the temporary file into the uploads directory.
			Package::set_upload_dir_filter();
			$results = wp_handle_sideload( $file, $overrides );
			Package::unset_upload_dir_filter();

			if ( empty( $results['error'] ) ) {
				$path = Package::get_relative_upload_dir( $results['file'] );

				$this->set_path( $path, $file_type );

				return $path;
			} else {
				throw new \Exception( $results['error'] );
			}
		} catch ( \Exception $e ) {
			Package::log( sprintf( 'Error while downloading label file from URL %1$s: %2$s', esc_url( $url ), $e->getMessage() ), 'error' );

			return false;
		}
	}

	public function is_trackable() {
		return true;
	}

	public function supports_status_refresh() {
		return false;
	}

	public function supports_third_party_email_notification() {
		$supports_email_notification = false;

		if ( ( $shipment = $this->get_shipment() ) && ( $order = $shipment->get_order() ) ) {
			$supports_email_notification = wc_stc_get_shipment_order( $order )->supports_third_party_email_transmission();
		}

		return apply_filters( "{$this->get_general_hook_prefix()}supports_third_party_email_notification", $supports_email_notification, $this );
	}

	/**
	 * Gets a prop for a getter method.
	 *
	 * @param  string $prop Name of prop to get.
	 * @param  string $address billing or shipping.
	 * @param  string $context What the value is for. Valid values are view and edit.
	 * @return mixed
	 */
	protected function get_address_prop( $prop, $address = 'sender_address', $context = 'view' ) {
		$value = null;

		if ( isset( $this->changes[ $address ][ $prop ] ) || isset( $this->data[ $address ][ $prop ] ) ) {
			$value = isset( $this->changes[ $address ][ $prop ] ) ? $this->changes[ $address ][ $prop ] : $this->data[ $address ][ $prop ];

			if ( 'view' === $context ) {
				/**
				 * Filter to adjust a specific address property for a DHL label.
				 *
				 * The dynamic portion of the hook name, `$this->get_hook_prefix()` constructs an individual
				 * hook name which uses `woocommerce_stc_dhl_label_get_` as a prefix. Additionally
				 * `$address` contains the current address type e.g. sender_address and `$prop` contains the actual
				 * property e.g. street.
				 *
				 * Example hook name: `woocommerce_stc_dhl_return_label_get_sender_address_street`
				 *
				 * @param string $value The address property value.
				 * @param Label  $label The label object.
				 *
				 */
				$value = apply_filters( "{$this->get_hook_prefix()}{$address}_{$prop}", $value, $this );
			}
		}

		return $value;
	}

	protected function round_customs_item_weight( $value, $precision = 0 ) {
		return \Automattic\WooCommerce\Utilities\NumberUtil::round( $value, $precision, 2 );
	}

	protected function get_per_item_weights( $total_weight, $item_weights, $shipment_items ) {
		$item_total_weight = array_sum( $item_weights );

		/**
		 * Discrepancies detected between item weights and total shipment weight.
		 * Try to distribute the mismatch between items.
		 */
		if ( $item_total_weight != $total_weight ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual
			$diff     = $total_weight - $item_total_weight;
			$diff_abs = abs( $diff );

			if ( $diff_abs > 0 ) {
				$diff_left          = $diff_abs;
				$item_keys          = array_keys( $item_weights );
				$current_item_index = 0;

				/**
				 * Loop over the diff (one step/g a time) and distribute
				 * the diff evenly for the items included. Respect min weight (1g).
				 */
				for ( $i = 0; $i < $diff_left; $i++ ) {
					if ( ! isset( $item_keys[ $current_item_index ] ) ) {
						$current_item_index = 0;
					}

					$current_item_key = $item_keys[ $current_item_index ];
					$shipment_item    = $shipment_items[ $current_item_key ];
					$item_min_weight  = 1 * $shipment_item->get_quantity();
					$diff_to_apply    = $diff / $diff_abs; // apply diff in -1/+1 steps
					$new_item_weight  = $item_weights[ $current_item_key ] + $diff_to_apply;

					if ( $new_item_weight >= $item_min_weight ) {
						$item_weights[ $current_item_key ] = $new_item_weight;
					}

					$current_item_index = ( $current_item_index >= count( $item_keys ) ) ? 0 : ( $current_item_index + 1 );
				}
			}
		}

		return $item_weights;
	}

	public function get_customs_data( $max_desc_length = 255 ) {
		if ( ! $shipment = $this->get_shipment() ) {
			return false;
		}

		$customs_items      = array();
		$item_description   = '';
		$total_weight       = (int) ceil( (float) wc_get_weight( $this->get_net_weight(), 'g', 'kg' ) );
		$total_gross_weight = (int) ceil( (float) wc_get_weight( $this->get_weight(), 'g', 'kg' ) );
		$item_weights       = array();
		$shipment_items     = $shipment->get_items();
		$order              = $shipment->get_order();

		foreach ( $shipment_items as $key => $item ) {
			$per_item_weight     = (int) ceil( (float) wc_get_weight( $item->get_weight(), 'g', $shipment->get_weight_unit() ) );
			$per_item_weight     = $per_item_weight * $item->get_quantity();
			$per_item_min_weight = 1 * $item->get_quantity();

			/**
			 * Set min weight to 1g to prevent missing weight error messages.
			 */
			if ( $per_item_weight < $per_item_min_weight ) {
				$per_item_weight = $per_item_min_weight;
			}

			$item_weights[ $key ] = $per_item_weight;
		}

		$item_weights       = $this->get_per_item_weights( $total_weight, $item_weights, $shipment_items );
		$item_gross_weights = $this->get_per_item_weights( $total_gross_weight, $item_weights, $shipment_items );

		$total_weight       = 0;
		$total_gross_weight = 0;
		$total_value        = 0;
		$use_subtotal       = false;

		if ( $order && apply_filters( 'woocommerce_shiptastic_order_has_voucher', false, $order ) ) {
			$use_subtotal = true;
		}

		$use_subtotal = apply_filters( 'woocommerce_shiptastic_customs_use_subtotal', $use_subtotal, $this );

		foreach ( $shipment->get_items() as $key => $item ) {
			$product = $item->get_product();

			if ( $product ) {
				$shipment_product = wc_shiptastic_get_product( $product );
			}

			$single_item_description = $item->get_customs_description();

			$item_description .= ! empty( $item_description ) ? ', ' : '';
			$item_description .= $single_item_description;

			// Use total before discounts for customs
			$product_total = (float) ( $use_subtotal ? $item->get_subtotal() : $item->get_total() ) / $item->get_quantity();

			if ( $product_total < 0.01 ) {
				// Use the order item data as fallback
				if ( ( $order_item = $item->get_order_item() ) && $order ) {
					$order_item_total = $use_subtotal ? $order->get_line_subtotal( $order_item, true, false ) : $order->get_line_total( $order_item, true, false );
					$product_total    = (float) $order_item_total / $item->get_quantity();
				}
			}

			$category = $shipment_product ? $shipment_product->get_main_category() : $item->get_name();

			if ( empty( $category ) ) {
				$category = $item->get_name();
			}

			$product_value = $product_total < 0.01 ? (float) wc_format_decimal( apply_filters( "{$this->get_general_hook_prefix()}customs_item_min_price", 0.01, $item, $this, $shipment ), 2 ) : (float) wc_format_decimal( $product_total, 2 );

			$customs_items[ $key ] = apply_filters(
				"{$this->get_general_hook_prefix()}customs_item",
				array(
					'description'         => wc_shiptastic_substring( wc_shiptastic_get_alphanumeric_string( apply_filters( "{$this->get_general_hook_prefix()}item_description", $single_item_description, $item, $this, $shipment ) ), 0, $max_desc_length ),
					'category'            => wc_shiptastic_get_alphanumeric_string( apply_filters( "{$this->get_general_hook_prefix()}item_category", $category, $item, $this, $shipment ) ),
					'origin_code'         => ( $shipment_product && $shipment_product->get_manufacture_country() ) ? $shipment_product->get_manufacture_country() : Package::get_base_country(),
					'tariff_number'       => $shipment_product ? $shipment_product->get_hs_code() : '',
					'quantity'            => intval( $item->get_quantity() ),
					'weight_in_kg'        => $this->round_customs_item_weight( (float) wc_get_weight( $item_weights[ $key ], 'kg', 'g' ), 3 ),
					'weight_in_g'         => $item_weights[ $key ],
					'single_weight_in_kg' => $this->round_customs_item_weight( (float) wc_get_weight( $item_weights[ $key ] / $item->get_quantity(), 'kg', 'g' ), 3 ),
					'single_weight_in_g'  => ceil( $item_weights[ $key ] / $item->get_quantity() ),
					'gross_weight_in_kg'  => $this->round_customs_item_weight( (float) wc_get_weight( $item_gross_weights[ $key ], 'kg', 'g' ), 3 ),
					'gross_weight_in_g'   => $item_gross_weights[ $key ],
					'single_value'        => $product_value,
					'value'               => wc_format_decimal( $product_value * $item->get_quantity(), 2 ),
					'sku'                 => $item->get_sku(),
				),
				$item,
				$shipment,
				$this
			);

			$total_weight       += $customs_items[ $key ]['weight_in_g'];
			$total_gross_weight += $customs_items[ $key ]['gross_weight_in_g'];
			$total_value        += (float) $customs_items[ $key ]['value'];
		}

		$item_description = wc_shiptastic_substring( wc_shiptastic_get_alphanumeric_string( $item_description ), 0, $max_desc_length );

		$customs_data = apply_filters(
			"{$this->get_general_hook_prefix()}customs_data",
			array(
				'shipment_id'                   => $shipment->get_id(),
				'additional_fee'                => wc_format_decimal( $shipment->get_additional_total(), 2 ),
				'place_of_commital'             => $shipment->get_sender_city(),
				'export_reference_number'       => $this->get_export_reference_number(),
				// e.g. EORI number
				'sender_customs_ref_number'     => $shipment->get_sender_customs_reference_number(),
				'receiver_customs_ref_number'   => $shipment->get_customs_reference_number(),
				// Customs UK VAT ID (HMRC) for totals <= 135 GBP
				'sender_customs_uk_vat_id'      => $shipment->get_sender_customs_uk_vat_id(),
				'items'                         => $customs_items,
				'item_total_weight_in_kg'       => $this->round_customs_item_weight( (float) wc_get_weight( $total_weight, 'kg', 'g' ), 3 ),
				'item_total_weight_in_g'        => $total_weight,
				'item_total_gross_weight_in_kg' => $this->round_customs_item_weight( (float) wc_get_weight( $total_gross_weight, 'kg', 'g' ), 3 ),
				'item_total_gross_weight_in_g'  => $total_gross_weight,
				'item_total_value'              => $total_value,
				'currency'                      => $order ? $order->get_currency() : get_woocommerce_currency(),
				'invoice_number'                => '',
				'incoterms'                     => $shipment->get_incoterms(),
				'export_type'                   => '',
				'export_reason_description'     => $item_description,
				'export_type_description'       => $item_description,
				'export_reason'                 => '',
			),
			$this,
			$shipment
		);

		return apply_filters( 'woocommerce_shiptastic_label_customs_data', $customs_data, $this, $shipment, $max_desc_length );
	}

	public function save() {
		$id = parent::save();

		if ( $cache = \Vendidero\Shiptastic\Caches\Helper::get_cache_object( 'shipment-labels' ) ) {
			$cache->remove( $this->get_id() );
		}

		return $id;
	}
}
