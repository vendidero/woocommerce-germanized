<?php

namespace Vendidero\Germanized\Shipments\Labels;

use Vendidero\Germanized\Shipments\Interfaces\ShipmentLabel;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\ShipmentError;
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
	 * @since 3.0.0
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
	 * @since 3.0.0
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
		'date_created'      => null,
		'shipment_id'       => 0,
		'product_id'        => '',
		'parent_id'         => 0,
		'number'            => '',
		'shipping_provider' => '',
		'weight'            => '',
		'net_weight'        => '',
		'length'            => '',
		'width'             => '',
		'height'            => '',
		'path'              => '',
		'created_via'       => '',
		'services'          => array(),
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

	public function get_type() {
		return 'simple';
	}

	/**
	 * Merge changes with data and clear.
	 * Overrides WC_Data::apply_changes.
	 * array_replace_recursive does not work well for license because it merges domains registered instead
	 * of replacing them.
	 *
	 * @since 3.2.0
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
	 * @since  3.0.0
	 * @return string
	 */
	protected function get_general_hook_prefix() {
		$prefix = 'simple' === $this->get_type() ? '' : $this->get_type() . '_';

		return "woocommerce_gzd_shipment_{$prefix}label_";
	}

	/**
	 * Prefix for action and filter hooks on data.
	 *
	 * @since  3.0.0
	 * @return string
	 */
	protected function get_hook_prefix() {
		return $this->get_general_hook_prefix() . 'get_';
	}

	/**
	 * Return the date this license was created.
	 *
	 * @since  3.0.0
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
			return wc_gzd_get_shipping_provider( $provider );
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
		return $this->get_prop( 'path', $context );
	}

	public function get_services( $context = 'view' ) {
		return $this->get_prop( 'services', $context );
	}

	public function has_service( $service ) {
		return ( in_array( $service, $this->get_services(), true ) );
	}

	public function get_shipment() {
		if ( is_null( $this->shipment ) ) {
			$this->shipment = ( $this->get_shipment_id() > 0 ? wc_gzd_get_shipment( $this->get_shipment_id() ) : false );
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
	 * @since  1.0.0
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
		$this->set_prop( 'path', $path );
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
		return wc_gzd_get_shipment_labels( array( 'parent_id' => $this->get_id() ) );
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
		return array();
	}

	public function get_file( $file_type = '' ) {
		if ( ! $path = $this->get_path( 'view', $file_type ) ) {
			return false;
		}

		return $this->get_file_by_path( $path );
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
				'action'      => 'wc-gzd-download-shipment-label',
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
		 * Example hook name: `woocommerce_gzd_return_shipment_get_dhl_label_download_url`
		 *
		 * @param string $url The download URL.
		 * @param Label  $label The current shipment instance.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
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

			$GLOBALS['gzd_shipments_unique_filename'] = $filename;
			add_filter( 'wp_unique_filename', '_wc_gzd_shipments_keep_force_filename', 10, 1 );

			$tmp = wp_upload_bits( $this->get_filename( $file_type ), null, $stream );

			unset( $GLOBALS['gzd_shipments_unique_filename'] );
			remove_filter( 'wp_unique_filename', '_wc_gzd_shipments_keep_force_filename', 10 );

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
				throw new \Exception( _x( 'Error while downloading the PDF file.', 'shipments', 'woocommerce-germanized' ) );
			}

			// Download file to temp dir.
			$temp_file = download_url( $url, $timeout_seconds );

			if ( is_wp_error( $temp_file ) ) {
				throw new \Exception( _x( 'Error while downloading the PDF file.', 'shipments', 'woocommerce-germanized' ) );
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
				throw new \Exception( _x( 'Error while downloading the PDF file.', 'shipments', 'woocommerce-germanized' ) );
			}
		} catch ( \Exception $e ) {
			return false;
		}
	}

	public function is_trackable() {
		return true;
	}

	public function supports_third_party_email_notification() {
		$supports_email_notification = false;

		if ( ( $shipment = $this->get_shipment() ) && ( $order = $shipment->get_order() ) ) {
			$supports_email_notification = wc_gzd_order_supports_parcel_delivery_reminder( $order );
		}

		return apply_filters( "{$this->get_general_hook_prefix()}supports_third_party_email_notification", $supports_email_notification, $this );
	}

	/**
	 * Gets a prop for a getter method.
	 *
	 * @since  3.0.0
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
				 * hook name which uses `woocommerce_gzd_dhl_label_get_` as a prefix. Additionally
				 * `$address` contains the current address type e.g. sender_address and `$prop` contains the actual
				 * property e.g. street.
				 *
				 * Example hook name: `woocommerce_gzd_dhl_return_label_get_sender_address_street`
				 *
				 * @param string                          $value The address property value.
				 * @param \Vendidero\Germanized\DHL\Label\Label $label The label object.
				 *
				 * @since 3.0.0
				 * @package Vendidero/Germanized/DHL
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
		$item_count        = count( $item_weights );

		/**
		 * Discrepancies detected between item weights an total shipment weight.
		 * Try to distribute the mismatch between items.
		 */
		if ( $item_total_weight != $total_weight ) { // phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			$diff     = $total_weight - $item_total_weight;
			$diff_abs = abs( $diff );

			if ( $diff_abs > 0 ) {
				$per_item_diff = $diff / $item_count;
				// Round down to int
				$per_item_diff_rounded = $this->round_customs_item_weight( $per_item_diff );
				$diff_applied          = 0;

				if ( abs( $per_item_diff_rounded ) > 0 ) {
					foreach ( $item_weights as $key => $weight ) {
						$shipment_item   = $shipment_items[ $key ];
						$item_min_weight = 1 * $shipment_item->get_quantity();

						$item_weight_before = $item_weights[ $key ];
						$new_item_weight    = $item_weights[ $key ] += $per_item_diff_rounded;
						$item_diff_applied  = $per_item_diff_rounded;

						/**
						 * In case the diff is negative make sure we are not
						 * subtracting more than available as min weight per item.
						 */
						if ( $new_item_weight <= $item_min_weight ) {
							$new_item_weight   = $item_min_weight;
							$item_diff_applied = $item_min_weight - $item_weight_before;
						}

						$item_weights[ $key ] = $new_item_weight;
						$diff_applied        += $item_diff_applied;
					}
				}

				// Check rounding diff and apply the diff to one item
				$diff_left = $diff - $diff_applied;

				if ( abs( $diff_left ) > 0 ) {
					foreach ( $item_weights as $key => $weight ) {
						$shipment_item   = $shipment_items[ $key ];
						$item_min_weight = 1 * $shipment_item->get_quantity();

						if ( $diff_left > 0 ) {
							/**
							 * Add the diff left to the first item and stop.
							 */
							$item_weights[ $key ] += $diff_left;
							break;
						} else {
							/**
							 * Remove the diff left from the first item with a weight greater than 0.01 to prevent 0 weights.
							 */
							if ( $weight > $item_min_weight ) {
								$item_weights[ $key ] += $diff_left;
								break;
							}
						}
					}
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
		$total_weight       = $this->round_customs_item_weight( wc_add_number_precision( $this->get_net_weight() ) );
		$total_gross_weight = $this->round_customs_item_weight( wc_add_number_precision( $this->get_weight() ) );
		$item_weights       = array();
		$shipment_items     = $shipment->get_items();
		$order              = $shipment->get_order();

		foreach ( $shipment_items as $key => $item ) {
			$per_item_weight     = wc_format_decimal( floatval( wc_get_weight( $item->get_weight(), 'kg', $shipment->get_weight_unit() ) ), 2 );
			$per_item_weight     = wc_add_number_precision( $per_item_weight );
			$per_item_weight     = $per_item_weight * $item->get_quantity();
			$per_item_min_weight = 1 * $item->get_quantity();

			/**
			 * Set min weight to 0.01 to prevent missing weight error messages
			 * for really small product weights.
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

		if ( $order && apply_filters( 'woocommerce_gzd_shipments_order_has_voucher', false, $order ) ) {
			$use_subtotal = true;
		}

		$use_subtotal = apply_filters( 'woocommerce_gzd_shipments_customs_use_subtotal', $use_subtotal, $this );

		foreach ( $shipment->get_items() as $key => $item ) {
			$item_description .= ! empty( $item_description ) ? ', ' : '';
			$item_description .= $item->get_name();

			// Use total before discounts for customs
			$product_total = floatval( (float) ( $use_subtotal ? $item->get_subtotal() : $item->get_total() ) / $item->get_quantity() );
			$dhl_product   = false;
			$product       = $item->get_product();

			if ( $product ) {
				$shipment_product = wc_gzd_shipments_get_product( $product );
			}

			if ( $product_total < 0.01 ) {
				// Use the order item data as fallback
				if ( ( $order_item = $item->get_order_item() ) && $order ) {
					$order_item_total = $use_subtotal ? $order->get_line_subtotal( $order_item, true, false ) : $order->get_line_total( $order_item, true, false );
					$product_total    = floatval( (float) $order_item_total / $item->get_quantity() );
				}
			}

			$category = $shipment_product ? $shipment_product->get_main_category() : $item->get_name();

			if ( empty( $category ) ) {
				$category = $item->get_name();
			}

			$product_value = $product_total < 0.01 ? wc_format_decimal( apply_filters( "{$this->get_general_hook_prefix()}customs_item_min_price", 0.01, $item, $this, $shipment ), 2 ) : wc_format_decimal( $product_total, 2 );

			$customs_items[ $key ] = apply_filters(
				"{$this->get_general_hook_prefix()}customs_item",
				array(
					'description'               => apply_filters( "{$this->get_general_hook_prefix()}item_description", wc_clean( mb_substr( $item->get_name(), 0, $max_desc_length ) ), $item, $this, $shipment ),
					'category'                  => apply_filters( "{$this->get_general_hook_prefix()}item_category", $category, $item, $this, $shipment ),
					'origin_code'               => ( $shipment_product && $shipment_product->get_manufacture_country() ) ? $shipment_product->get_manufacture_country() : Package::get_base_country(),
					'tariff_number'             => $shipment_product ? $shipment_product->get_hs_code() : '',
					'quantity'                  => intval( $item->get_quantity() ),
					'weight_in_kg'              => wc_remove_number_precision( $item_weights[ $key ] ),
					'single_weight_in_kg'       => $this->round_customs_item_weight( wc_remove_number_precision( $item_weights[ $key ] / $item->get_quantity() ), 2 ),
					'weight_in_kg_raw'          => $item_weights[ $key ],
					'gross_weight_in_kg'        => wc_remove_number_precision( $item_gross_weights[ $key ] ),
					'single_gross_weight_in_kg' => $this->round_customs_item_weight( wc_remove_number_precision( $item_gross_weights[ $key ] / $item->get_quantity() ), 2 ),
					'gross_weight_in_kg_raw'    => $item_gross_weights[ $key ],
					'single_value'              => $product_value,
					'value'                     => wc_format_decimal( $product_value * $item->get_quantity(), 2 ),
				),
				$item,
				$shipment,
				$this
			);

			$total_weight       += (float) $customs_items[ $key ]['weight_in_kg'];
			$total_gross_weight += (float) $customs_items[ $key ]['gross_weight_in_kg'];
			$total_value        += (float) $customs_items[ $key ]['value'];
		}

		$item_description = mb_substr( $item_description, 0, $max_desc_length );

		$customs_data = apply_filters(
			"{$this->get_general_hook_prefix()}customs_data",
			array(
				'shipment_id'                   => $shipment->get_id(),
				'additional_fee'                => wc_format_decimal( $shipment->get_additional_total(), 2 ),
				'export_type_description'       => $item_description,
				'place_of_commital'             => $shipment->get_sender_city(),
				// e.g. EORI number
				'sender_customs_ref_number'     => $shipment->get_sender_customs_reference_number(),
				'receiver_customs_ref_number'   => $shipment->get_customs_reference_number(),
				// Customs UK VAT ID (HMRC) for totals <= 135 GBP
				'sender_customs_uk_vat_id'      => $shipment->get_sender_customs_uk_vat_id(),
				'items'                         => $customs_items,
				'item_total_weight_in_kg'       => $total_weight,
				'item_total_gross_weight_in_kg' => $total_gross_weight,
				'item_total_value'              => $total_value,
				'currency'                      => $order ? $order->get_currency() : get_woocommerce_currency(),
				'invoice_number'                => '',
			),
			$this,
			$shipment
		);

		return $customs_data;
	}
}
