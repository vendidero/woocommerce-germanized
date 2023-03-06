<?php
/**
 * WooCommerce Germanized DHL Shipment Functions
 *
 * Functions for shipment specific things.
 *
 * @package WooCommerce_Germanized/DHL/Functions
 * @version 3.4.0
 */

use Vendidero\Germanized\Shipments\Order;
use Vendidero\Germanized\Shipments\ReturnReason;
use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\AddressSplitter;
use Vendidero\Germanized\Shipments\ShipmentFactory;
use Vendidero\Germanized\Shipments\ShipmentItem;
use Vendidero\Germanized\Shipments\ShipmentReturnItem;
use Vendidero\Germanized\Shipments\SimpleShipment;
use Vendidero\Germanized\Shipments\ReturnShipment;
use Vendidero\Germanized\Shipments\Package;
use Vendidero\Germanized\Shipments\ShippingProvider;

defined( 'ABSPATH' ) || exit;

function wc_gzd_get_formatted_state( $country = '', $state = '' ) {
	if ( empty( $country ) ) {
		return '';
	}

	$states          = WC()->countries ? WC()->countries->get_states( $country ) : array();
	$formatted_state = ( $states && isset( $states[ $state ] ) ) ? $states[ $state ] : $state;

	return $formatted_state;
}

function wc_gzd_get_shipment_order( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	if ( is_a( $order, 'WC_Order' ) ) {
		try {
			return new Vendidero\Germanized\Shipments\Order( $order );
		} catch ( Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, array( $order ) );
			return false;
		}
	}

	return false;
}

function wc_gzd_get_shipment_label_title( $type, $plural = false ) {
	$type_data = wc_gzd_get_shipment_type_data( $type );

	return ( ! $plural ? $type_data['labels']['singular'] : $type_data['labels']['plural'] );
}

function wc_gzd_get_shipment_types() {
	return array_keys( wc_gzd_get_shipment_type_data( false ) );
}

/**
 * Get shipment type data by type.
 *
 * @param  string $type type name.
 * @return bool|array Details about the shipment type.
 *
 * @package Vendidero/Germanized/Shipments
 */
function wc_gzd_get_shipment_type_data( $type = false ) {
	$types = apply_filters(
		'woocommerce_gzd_shipment_type_data',
		array(
			'simple' => array(
				'class_name' => '\Vendidero\Germanized\Shipments\SimpleShipment',
				'labels'     => array(
					'singular' => _x( 'Shipment', 'shipments', 'woocommerce-germanized' ),
					'plural'   => _x( 'Shipments', 'shipments', 'woocommerce-germanized' ),
				),
			),
			'return' => array(
				'class_name' => '\Vendidero\Germanized\Shipments\ReturnShipment',
				'labels'     => array(
					'singular' => _x( 'Return', 'shipments', 'woocommerce-germanized' ),
					'plural'   => _x( 'Returns', 'shipments', 'woocommerce-germanized' ),
				),
			),
		)
	);

	if ( $type && array_key_exists( $type, $types ) ) {
		return $types[ $type ];
	} elseif ( false === $type ) {
		return $types;
	} else {
		return $types['simple'];
	}
}

function wc_gzd_get_shipments_by_order( $order ) {
	$shipments = array();

	if ( $order_shipment = wc_gzd_get_shipment_order( $order ) ) {
		$shipments = $order_shipment->get_shipments();
	}

	return $shipments;
}

function wc_gzd_get_shipment_order_shipping_statuses() {
	$shipment_statuses = array(
		'gzd-not-shipped'         => _x( 'Not shipped', 'shipments', 'woocommerce-germanized' ),
		'gzd-partially-shipped'   => _x( 'Partially shipped', 'shipments', 'woocommerce-germanized' ),
		'gzd-shipped'             => _x( 'Shipped', 'shipments', 'woocommerce-germanized' ),
		'gzd-partially-delivered' => _x( 'Partially delivered', 'shipments', 'woocommerce-germanized' ),
		'gzd-delivered'           => _x( 'Delivered', 'shipments', 'woocommerce-germanized' ),
		'gzd-no-shipping-needed'  => _x( 'No shipping needed', 'shipments', 'woocommerce-germanized' ),
	);

	/**
	 * Filter to adjust or add order shipping statuses.
	 * An order might retrieve a shipping status e.g. not shipped.
	 *
	 * @param array $shipment_statuses Available order shipping statuses.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_order_shipping_statuses', $shipment_statuses );
}

function wc_gzd_get_shipment_order_return_statuses() {
	$shipment_statuses = array(
		'gzd-open'               => _x( 'Open', 'shipments', 'woocommerce-germanized' ),
		'gzd-partially-returned' => _x( 'Partially returned', 'shipments', 'woocommerce-germanized' ),
		'gzd-returned'           => _x( 'Returned', 'shipments', 'woocommerce-germanized' ),
	);

	/**
	 * Filter to adjust or add order return statuses.
	 * An order might retrieve a shipping status e.g. not shipped.
	 *
	 * @param array $shipment_statuses Available order return statuses.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_order_return_statuses', $shipment_statuses );
}

/**
 * @param $instance_id
 *
 * @return ShippingProvider\Method
 */
function wc_gzd_get_shipping_provider_method( $instance_id ) {
	$original_id = $instance_id;
	$method      = false;
	$method_id   = '';

	if ( is_a( $original_id, 'WC_Shipping_Rate' ) ) {
		$instance_id = $original_id->get_instance_id();
		$method_id   = $original_id->get_method_id();
	} elseif ( is_a( $original_id, 'WC_Shipping_Method' ) ) {
		$instance_id = $original_id->get_instance_id();
		$method_id   = $original_id->id;
	} elseif ( ! is_numeric( $instance_id ) && is_string( $instance_id ) ) {
		if ( strpos( $instance_id, ':' ) !== false ) {
			$expl        = explode( ':', $instance_id );
			$instance_id = ( ( ! empty( $expl ) && count( $expl ) > 1 ) ? (int) $expl[1] : 0 );
			$method_id   = ( ! empty( $expl ) ) ? $expl[0] : $instance_id;
		} else {
			/**
			 * Plugins like Flexible Shipping use underscores to separate instance ids.
			 * Example: flexible_shipping_4_1. In this case, 4 ist the instance id.
			 * method_id: flexible_shipping
			 * instance_id: 4
			 *
			 * On the other hand legacy shipping methods may be string only, e.g. an instance id might not exist.
			 * Example: local_pickup_plus
			 * method: local_pickup_plus
			 * instance_id: 0
			 */
			$expl      = explode( '_', $instance_id );
			$numbers   = array_values( array_filter( $expl, 'is_numeric' ) );
			$method_id = rtrim( preg_replace( '/[0-9]+/', '', $instance_id ), '_' );

			if ( ! empty( $numbers ) ) {
				$instance_id = absint( $numbers[0] );
			} else {
				$instance_id = 0;
			}
		}
	}

	if ( ! empty( $instance_id ) ) {
		// Make sure shipping zones are loaded
		include_once WC_ABSPATH . 'includes/class-wc-shipping-zones.php';

		/**
		 * Cache methods within frontend
		 */
		if ( WC()->session && did_action( 'woocommerce_shipping_init' ) ) {
			$cache_key  = 'woocommerce_gzd_method_' . $instance_id;
			$tmp_method = WC()->session->get( $cache_key );

			if ( ! $tmp_method || ! is_object( $tmp_method ) || is_a( $tmp_method, '__PHP_Incomplete_Class' ) ) {
				$method = WC_Shipping_Zones::get_shipping_method( $instance_id );

				if ( $method ) {
					WC()->session->set( $cache_key, $method );
				}
			} else {
				$method = $tmp_method;
			}
		} else {
			$method = WC_Shipping_Zones::get_shipping_method( $instance_id );
		}
	}

	/**
	 * Fallback for legacy shipping methods that do not support instance ids.
	 */
	if ( ! $method && empty( $instance_id ) && ! empty( $method_id ) ) {
		$shipping_methods = WC()->shipping()->get_shipping_methods();

		if ( array_key_exists( $method_id, $shipping_methods ) ) {
			$method = $shipping_methods[ $method_id ];
		}
	}

	if ( $method ) {
		/**
		 * Filter to adjust the classname used to construct the shipping provider method
		 * which contains additional provider related settings useful for shipments.
		 *
		 * @param string             $classname The classname.
		 * @param WC_Shipping_Method $method The shipping method instance.
		 *
		 * @since 3.0.6
		 * @package Vendidero/Germanized/Shipments
		 */
		$classname = apply_filters( 'woocommerce_gzd_shipping_provider_method_classname', 'Vendidero\Germanized\Shipments\ShippingProvider\Method', $method );

		return new $classname( $method );
	}

	// Load placeholder
	$placeholder = new ShippingProvider\MethodPlaceholder( $original_id );

	/**
	 * Filter to adjust the fallback shipping method to be loaded if no real
	 * shipping method was able to be constructed (e.g. a custom plugin is being used which
	 * replaces the default Woo shipping zones integration).
	 *
	 * @param ShippingProvider\MethodPlaceholder $placeholder The placeholder impl.
	 * @param string                             $original_id The shipping method id.
	 *
	 * @since 3.0.6
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipping_provider_method_fallback', $placeholder, $original_id );
}

/**
 * Returns the current shipping method rate id.
 *
 * @return false|string
 */
function wc_gzd_get_current_shipping_method_id() {
	$chosen_shipping_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods' ) : array();

	if ( ! empty( $chosen_shipping_methods ) ) {
		return reset( $chosen_shipping_methods );
	}

	return false;
}

function wc_gzd_get_current_shipping_provider_method() {
	if ( $current = wc_gzd_get_current_shipping_method_id() ) {
		return wc_gzd_get_shipping_provider_method( $current );
	}

	return false;
}

function wc_gzd_get_shipment_order_shipping_status_name( $status ) {
	if ( 'gzd-' !== substr( $status, 0, 4 ) ) {
		$status = 'gzd-' . $status;
	}

	$status_name = '';
	$statuses    = wc_gzd_get_shipment_order_shipping_statuses();

	if ( array_key_exists( $status, $statuses ) ) {
		$status_name = $statuses[ $status ];
	}

	/**
	 * Filter to adjust the status name for a certain order shipping status.
	 *
	 * @see wc_gzd_get_shipment_order_shipping_statuses()
	 *
	 * @param string $status_name The status name.
	 * @param string $status The shipping status.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_order_shipping_status_name', $status_name, $status );
}

function wc_gzd_get_shipment_order_return_status_name( $status ) {
	if ( 'gzd-' !== substr( $status, 0, 4 ) ) {
		$status = 'gzd-' . $status;
	}

	$status_name = '';
	$statuses    = wc_gzd_get_shipment_order_return_statuses();

	if ( array_key_exists( $status, $statuses ) ) {
		$status_name = $statuses[ $status ];
	}

	/**
	 * Filter to adjust the status name for a certain order return status.
	 *
	 * @see wc_gzd_get_shipment_order_return_statuses()
	 *
	 * @param string $status_name The status name.
	 * @param string $status The return status.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_order_return_status_name', $status_name, $status );
}

/**
 * Standard way of retrieving shipments based on certain parameters.
 *
 * @param  array $args Array of args (above).
 *
 * @return Shipment[] The shipments found.
 *@since  3.0.0
 */
function wc_gzd_get_shipments( $args ) {
	$query = new Vendidero\Germanized\Shipments\ShipmentQuery( $args );

	return $query->get_shipments();
}

function wc_gzd_get_shipment_customer_visible_statuses( $shipment_type = 'simple' ) {
	$statuses = array_keys( wc_gzd_get_shipment_statuses() );
	$statuses = array_diff( $statuses, array( 'gzd-draft' ) );

	/**
	 * Filter to decide which shipment statuses should be visible to customers
	 * e.g. whether a shipment of a certain status should be shown or not.
	 *
	 * @param array  $shipment_statuses The available shipment statuses.
	 * @param string $shipment_type The shipment type.
	 *
	 * @since 3.1.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipment_customer_visible_statuses', $statuses, $shipment_type );
}

/**
 * Main function for returning shipments.
 *
 * @param  mixed $the_shipment Object or shipment id.
 *
 * @return bool|SimpleShipment|ReturnShipment|Shipment
 */
function wc_gzd_get_shipment( $the_shipment ) {
	return ShipmentFactory::get_shipment( $the_shipment );
}

/**
 * Get all shipment statuses.
 *
 * @return array
 */
function wc_gzd_get_shipment_statuses() {
	$shipment_statuses = array(
		'gzd-draft'      => _x( 'Draft', 'shipments', 'woocommerce-germanized' ),
		'gzd-processing' => _x( 'Processing', 'shipments', 'woocommerce-germanized' ),
		'gzd-shipped'    => _x( 'Shipped', 'shipments', 'woocommerce-germanized' ),
		'gzd-delivered'  => _x( 'Delivered', 'shipments', 'woocommerce-germanized' ),
		'gzd-requested'  => _x( 'Requested', 'shipments', 'woocommerce-germanized' ),
	);

	/**
	 * Add or adjust available Shipment statuses.
	 *
	 * @param array $shipment_statuses The available shipment statuses.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipment_statuses', $shipment_statuses );
}

/**
 * @param Shipment $shipment
 *
 * @return mixed|void
 */
function wc_gzd_get_shipment_selectable_statuses( $shipment ) {
	$shipment_statuses = wc_gzd_get_shipment_statuses();

	if ( ! $shipment->has_status( 'requested' ) && isset( $shipment_statuses['gzd-requested'] ) ) {
		unset( $shipment_statuses['gzd-requested'] );
	}

	/**
	 * Add or remove selectable shipment statuses for a certain shipment and/or shipment type.
	 *
	 * @param array    $shipment_statuses The available shipment statuses.
	 * @param string   $type The shipment type e.g. return.
	 * @param Shipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipment_selectable_statuses', $shipment_statuses, $shipment->get_type(), $shipment );
}

/**
 * @param Order $order_shipment
 * @param array $args
 *
 * @return ReturnShipment|WP_Error
 */
function wc_gzd_create_return_shipment( $order_shipment, $args = array() ) {
	try {

		if ( ! $order_shipment || ! is_a( $order_shipment, 'Vendidero\Germanized\Shipments\Order' ) ) {
			throw new Exception( _x( 'Invalid order.', 'shipments', 'woocommerce-germanized' ) );
		}

		if ( ! $order_shipment->needs_return() ) {
			throw new Exception( _x( 'This order is already fully returned.', 'shipments', 'woocommerce-germanized' ) );
		}

		$args = wp_parse_args(
			$args,
			array(
				'items' => array(),
				'props' => array(),
			)
		);

		$shipment = ShipmentFactory::get_shipment( false, 'return' );

		if ( ! $shipment ) {
			throw new Exception( _x( 'Error while creating the shipment instance', 'shipments', 'woocommerce-germanized' ) );
		}

		// Make sure shipment knows its parent
		$shipment->set_order_shipment( $order_shipment );
		$shipment->sync( $args['props'] );
		$shipment->sync_items( $args );
		$shipment->save();

	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}

	return $shipment;
}

/**
 * @param Order $order_shipment
 * @param array $args
 *
 * @return Shipment|WP_Error
 */
function wc_gzd_create_shipment( $order_shipment, $args = array() ) {
	try {
		if ( ! $order_shipment || ! is_a( $order_shipment, 'Vendidero\Germanized\Shipments\Order' ) ) {
			throw new Exception( _x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized' ) );
		}

		if ( ! $order = $order_shipment->get_order() ) {
			throw new Exception( _x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized' ) );
		}

		$args = wp_parse_args(
			$args,
			array(
				'items' => array(),
				'props' => array(),
			)
		);

		$shipment = ShipmentFactory::get_shipment( false, 'simple' );

		if ( ! $shipment ) {
			throw new Exception( _x( 'Error while creating the shipment instance', 'shipments', 'woocommerce-germanized' ) );
		}

		$shipment->set_order_shipment( $order_shipment );
		$shipment->sync( $args['props'] );
		$shipment->sync_items( $args );
		$shipment->save();

	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}

	return $shipment;
}

function wc_gzd_create_shipment_item( $shipment, $order_item, $args = array() ) {
	try {

		if ( ! $order_item || ! is_a( $order_item, 'WC_Order_Item' ) ) {
			throw new Exception( _x( 'Invalid order item', 'shipments', 'woocommerce-germanized' ) );
		}

		$item = new Vendidero\Germanized\Shipments\ShipmentItem();

		$item->set_order_item_id( $order_item->get_id() );
		$item->set_shipment( $shipment );
		$item->sync( $args );
		$item->save();

	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}

	return $item;
}

function wc_gzd_allow_customer_return_empty_return_reason( $order ) {
	return apply_filters( 'woocommerce_gzd_allow_customer_return_empty_return_reason', true, $order );
}

/**
 * @param bool $allow_none
 * @param bool|WC_Order_Item $order_item
 *
 * @return ReturnReason[]
 */
function wc_gzd_get_return_shipment_reasons( $order_item = false ) {
	$reasons = Package::get_setting( 'return_reasons' );

	if ( ! is_array( $reasons ) ) {
		$reasons = array();
	} else {
		$reasons = array_filter( $reasons );
	}

	/**
	 * Filter that allows adjusting raw return reasons for a specific shipment (e.g. array containing reason data with code, reason and order).
	 *
	 * @param array               $reasons Available return reasons.
	 * @param WC_Order_Item|false $order_item The order item object if available to further filter reasons.
	 *
	 * @since 3.1.0
	 * @package Vendidero/Germanized/Shipments
	 */
	$reasons   = apply_filters( 'woocommerce_gzd_return_shipment_reasons_raw', $reasons, $order_item );
	$instances = array();

	foreach ( $reasons as $reason ) {
		$instances[] = new ReturnReason( $reason );
	}

	usort( $instances, '_wc_gzd_sort_return_shipment_reasons' );

	/**
	 * Filter that allows to adjust available return reasons for a specific shipment.
	 *
	 * @param ReturnReason[]         $reasons Available return reasons.
	 * @param WC_Order_Item|false    $order_item The order item object if available to further filter reasons.
	 *
	 * @since 3.1.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_return_shipment_reasons', $instances, $order_item );
}

function wc_gzd_return_shipment_reason_exists( $maybe_reason, $shipment = false ) {
	$reasons = wc_gzd_get_return_shipment_reasons( $shipment );
	$exists  = false;

	foreach ( $reasons as $reason ) {

		if ( $reason->get_code() === $maybe_reason ) {
			$exists = true;
			break;
		}
	}

	return $exists;
}

/**
 * @param ReturnReason $a
 * @param ReturnReason $b
 */
function _wc_gzd_sort_return_shipment_reasons( $a, $b ) {
	if ( $a->get_order() === $b->get_order() ) {
		return 0;
	} elseif ( $a->get_order() > $b->get_order() ) {
		return 1;
	} else {
		return -1;
	}
}

/**
 * @param WP_Error $error
 *
 * @return bool
 */
function wc_gzd_shipment_wp_error_has_errors( $error ) {
	if ( is_callable( array( $error, 'has_errors' ) ) ) {
		return $error->has_errors();
	} else {
		$errors = $error->errors;

		return ( ! empty( $errors ) ? true : false );
	}
}

/**
 * @param Shipment $shipment
 * @param ShipmentItem $shipment_item
 * @param array $args
 *
 * @return ShipmentReturnItem|WP_Error
 */
function wc_gzd_create_return_shipment_item( $shipment, $shipment_item, $args = array() ) {

	try {

		if ( ! $shipment_item || ! is_a( $shipment_item, '\Vendidero\Germanized\Shipments\ShipmentItem' ) ) {
			throw new Exception( _x( 'Invalid shipment item', 'shipments', 'woocommerce-germanized' ) );
		}

		$item = new Vendidero\Germanized\Shipments\ShipmentReturnItem();
		$item->set_order_item_id( $shipment_item->get_order_item_id() );
		$item->set_shipment( $shipment );
		$item->sync( $args );
		$item->save();

	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}

	return $item;
}

function wc_gzd_get_shipment_editable_statuses() {
	/**
	 * Filter that allows to adjust Shipment statuses which decide upon whether
	 * a Shipment is editable or not.
	 *
	 * @param array $statuses Statuses which should be considered as editable.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipment_editable_statuses', array( 'draft', 'requested', 'processing' ) );
}

function wc_gzd_split_shipment_street( $street_str ) {
	$return = array(
		'street'     => $street_str,
		'number'     => '',
		'addition'   => '',
		'addition_2' => '',
	);

	try {
		$split = AddressSplitter::split_address( $street_str );

		$return['street'] = $split['streetName'];
		$return['number'] = $split['houseNumber'];
		/**
		 * e.g. 5. OG
		 */
		$return['addition'] = isset( $split['additionToAddress2'] ) ? $split['additionToAddress2'] : '';
		/**
		 * E.g. details to the location prefixed to the street name
		 */
		$return['addition_2'] = isset( $split['additionToAddress1'] ) ? $split['additionToAddress1'] : '';
	} catch ( Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
	}

	return $return;
}

function wc_gzd_get_shipping_providers() {
	return ShippingProvider\Helper::instance()->get_shipping_providers();
}

function wc_gzd_get_shipping_provider( $name ) {
	return ShippingProvider\Helper::instance()->get_shipping_provider( $name );
}

function wc_gzd_get_default_shipping_provider() {
	$default = Package::get_setting( 'default_shipping_provider' );

	/**
	 * Filter to adjust the default shipping provider used as a fallback for shipments
	 * for which no provider could be determined automatically (e.g. by the chosen shipping methid).
	 *
	 * @param string  $title The shipping provider slug.
	 *
	 * @since 3.0.6
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_default_shipping_provider', $default );
}

function wc_gzd_get_shipping_provider_select() {
	$providers = wc_gzd_get_shipping_providers();
	$select    = array(
		'' => _x( 'None', 'shipments', 'woocommerce-germanized' ),
	);

	foreach ( $providers as $provider ) {
		if ( ! $provider->is_activated() ) {
			continue;
		}
		$select[ $provider->get_name() ] = $provider->get_title();
	}

	return $select;
}

function wc_gzd_get_shipping_provider_title( $slug ) {
	$providers = wc_gzd_get_shipping_providers();

	if ( array_key_exists( $slug, $providers ) ) {
		$title = $providers[ $slug ]->get_title();
	} else {
		$title = $slug;
	}

	/**
	 * Filter to adjust the title of a certain shipping provider e.g. DHL.
	 *
	 * @param string  $title The shipping provider title.
	 * @param string  $slug The shipping provider slug.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipping_provider_title', $title, $slug );
}

/**
 * @param Shipment $shipment
 */
function wc_gzd_get_shipment_shipping_provider_title( $shipment ) {
	$title = $shipment->get_shipping_provider_title();

	if ( empty( $title ) ) {
		$title = apply_filters( 'woocommerce_gzd_shipping_provider_unknown_title', _x( 'Unknown', 'shipments-shipping-provider', 'woocommerce-germanized' ) );
	}

	return $title;
}

function wc_gzd_get_shipping_provider_slug( $provider ) {
	$providers = wc_gzd_get_shipping_providers();

	if ( in_array( $provider, $providers, true ) ) {
		$slug = array_search( $provider, $providers, true );
	} elseif ( array_key_exists( $provider, $providers ) ) {
		$slug = $provider;
	} else {
		$slug = sanitize_key( $provider );
	}

	return $slug;
}

function _wc_gzd_shipments_keep_force_filename( $new_filename ) {
	return isset( $GLOBALS['gzd_shipments_unique_filename'] ) ? $GLOBALS['gzd_shipments_unique_filename'] : $new_filename;
}

function wc_gzd_shipments_upload_data( $filename, $bits, $relative = true ) {
	try {
		Package::set_upload_dir_filter();
		$GLOBALS['gzd_shipments_unique_filename'] = $filename;
		add_filter( 'wp_unique_filename', '_wc_gzd_shipments_keep_force_filename', 10, 1 );

		$tmp = wp_upload_bits( $filename, null, $bits );

		unset( $GLOBALS['gzd_shipments_unique_filename'] );
		remove_filter( 'wp_unique_filename', '_wc_gzd_shipments_keep_force_filename', 10 );
		Package::unset_upload_dir_filter();

		if ( isset( $tmp['file'] ) ) {
			$path = $tmp['file'];

			if ( $relative ) {
				$path = Package::get_relative_upload_dir( $path );
			}

			return $path;
		} else {
			throw new Exception( _x( 'Error while uploading file.', 'shipments', 'woocommerce-germanized' ) );
		}
	} catch ( Exception $e ) {
		return false;
	}
}

function wc_gzd_get_shipment_setting_default_address_fields( $type = 'shipper' ) {
	$address_fields = array(
		'first_name'               => _x( 'First Name', 'shipments', 'woocommerce-germanized' ),
		'last_name'                => _x( 'Last Name', 'shipments', 'woocommerce-germanized' ),
		'full_name'                => _x( 'Full Name', 'shipments', 'woocommerce-germanized' ),
		'company'                  => _x( 'Company', 'shipments', 'woocommerce-germanized' ),
		'address_1'                => _x( 'Address 1', 'shipments', 'woocommerce-germanized' ),
		'address_2'                => _x( 'Address 2', 'shipments', 'woocommerce-germanized' ),
		'street'                   => _x( 'Street', 'shipments', 'woocommerce-germanized' ),
		'street_number'            => _x( 'House Number', 'shipments', 'woocommerce-germanized' ),
		'postcode'                 => _x( 'Postcode', 'shipments', 'woocommerce-germanized' ),
		'city'                     => _x( 'City', 'shipments', 'woocommerce-germanized' ),
		'country'                  => _x( 'Country', 'shipments', 'woocommerce-germanized' ),
		'state'                    => _x( 'State', 'shipments', 'woocommerce-germanized' ),
		'phone'                    => _x( 'Phone', 'shipments', 'woocommerce-germanized' ),
		'email'                    => _x( 'Email', 'shipments', 'woocommerce-germanized' ),
		'customs_reference_number' => _x( 'Customs Reference Number', 'shipments', 'woocommerce-germanized' ),
		'customs_uk_vat_id'        => _x( 'UK VAT ID (HMRC)', 'shipments', 'woocommerce-germanized' ),
	);

	return apply_filters( 'woocommerce_gzd_shipment_default_address_fields', $address_fields, $type );
}

/**
 * @return array
 */
function wc_gzd_get_shipment_setting_address_fields( $address_type = 'shipper' ) {
	$default_address_fields = array_keys( wc_gzd_get_shipment_setting_default_address_fields( $address_type ) );
	$default_address_data   = array();

	if ( 'return' === $address_type ) {
		$default_address_data = wc_gzd_get_shipment_setting_address_fields( 'shipper' );
	}

	foreach ( $default_address_fields as $prop ) {
		$key   = "woocommerce_gzd_shipments_{$address_type}_address_{$prop}";
		$value = get_option( $key, '' );

		if ( '' === $value && array_key_exists( $prop, $default_address_data ) ) {
			$value = $default_address_data[ $prop ];
		}

		$address_fields[ $prop ] = $value;
	}

	if ( ! empty( $address_fields['country'] ) && strlen( $address_fields['country'] ) > 2 ) {
		$value                     = wc_format_country_state_string( $address_fields['country'] );
		$address_fields['country'] = $value['country'];
		$address_fields['state']   = $value['state'];
	}

	/**
	 * Format/split address 1 into street and house number
	 */
	if ( ! empty( $address_fields['address_1'] ) ) {
		$split = wc_gzd_split_shipment_street( $address_fields['address_1'] );

		$address_fields['street']        = $split['street'];
		$address_fields['street_number'] = $split['number'];
	} else {
		$address_fields['street']        = '';
		$address_fields['street_number'] = '';
	}

	/**
	 * Attach formatted full name
	 */
	$address_fields['full_name'] = trim( sprintf( _x( '%1$s %2$s', 'full name', 'woocommerce-germanized' ), $address_fields['first_name'], $address_fields['last_name'] ) );

	return apply_filters( "woocommerce_gzd_shipment_{$address_type}_address_fields", $address_fields, $address_type );
}

/**
 * @param Order $shipment_order
 *
 * @return array
 */
function wc_gzd_get_shipment_return_address( $shipment_order = false ) {
	return wc_gzd_get_shipment_setting_address_fields( 'return' );
}

/**
 * @param WC_Order $order
 */
function wc_gzd_get_shipment_order_shipping_method_id( $order ) {
	$methods = $order->get_shipping_methods();
	$id      = '';

	if ( ! empty( $methods ) ) {
		$method_vals = array_values( $methods );
		$method      = array_shift( $method_vals );

		if ( $method ) {
			$id = $method->get_method_id() . ':' . $method->get_instance_id();
		}
	}

	/**
	 * Allows adjusting the shipping method id for a certain Order.
	 *
	 * @param string   $id The shipping method id.
	 * @param WC_Order $order The order object.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipment_order_shipping_method_id', $id, $order );
}

function wc_gzd_render_shipment_action_buttons( $actions ) {
	$actions_html = '';

	foreach ( $actions as $action ) {
		if ( isset( $action['group'] ) ) {
			$actions_html .= '<div class="wc-gzd-shipment-action-button-group"><label>' . $action['group'] . '</label> <span class="wc-gzd-shipment-action-button-group__items">' . wc_gzd_render_shipment_action_buttons( $action['actions'] ) . '</span></div>';
		} elseif ( isset( $action['action'], $action['url'], $action['name'] ) ) {
			$target = isset( $action['target'] ) ? $action['target'] : '_self';

			$actions_html .= sprintf( '<a class="button wc-gzd-shipment-action-button wc-gzd-shipment-action-button-%1$s %1$s" href="%2$s" aria-label="%3$s" title="%3$s" target="%4$s">%5$s</a>', esc_attr( $action['action'] ), esc_url( $action['url'] ), esc_attr( isset( $action['title'] ) ? $action['title'] : $action['name'] ), $target, esc_html( $action['name'] ) );
		}
	}

	return $actions_html;
}

function wc_gzd_get_shipment_status_name( $status ) {
	if ( 'gzd-' !== substr( $status, 0, 4 ) ) {
		$status = 'gzd-' . $status;
	}

	$status_name = '';
	$statuses    = wc_gzd_get_shipment_statuses();

	if ( array_key_exists( $status, $statuses ) ) {
		$status_name = $statuses[ $status ];
	}

	/**
	 * Filter to adjust the shipment status name or title.
	 *
	 * @param string  $status_name The status name or title.
	 * @param integer $status The status slug.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipment_status_name', $status_name, $status );
}

function wc_gzd_get_shipment_sent_statuses() {
	/**
	 * Filter to adjust which Shipment statuses should be considered as sent.
	 *
	 * @param array $statuses An array of statuses considered as shipped,
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters(
		'woocommerce_gzd_shipment_sent_statuses',
		array(
			'shipped',
			'delivered',
		)
	);
}

function wc_gzd_get_shipment_counts( $type = '' ) {
	$counts = array();

	foreach ( array_keys( wc_gzd_get_shipment_statuses() ) as $status ) {
		$counts[ $status ] = wc_gzd_get_shipment_count( $status, $type );
	}

	return $counts;
}

function wc_gzd_get_shipment_count( $status, $type = '' ) {
	$count             = 0;
	$status            = ( substr( $status, 0, 4 ) ) === 'gzd-' ? $status : 'gzd-' . $status;
	$shipment_statuses = array_keys( wc_gzd_get_shipment_statuses() );

	if ( ! in_array( $status, $shipment_statuses, true ) ) {
		return 0;
	}

	$cache_key    = WC_Cache_Helper::get_cache_prefix( 'shipments' ) . $status . $type;
	$cached_count = wp_cache_get( $cache_key, 'counts' );

	if ( false !== $cached_count ) {
		return $cached_count;
	}

	$data_store = WC_Data_Store::load( 'shipment' );

	if ( $data_store ) {
		$count += $data_store->get_shipment_count( $status, $type );
	}

	wp_cache_set( $cache_key, $count, 'counts' );

	return $count;
}

/**
 * See if a string is a shipment status.
 *
 * @param  string $maybe_status Status, including any gzd- prefix.
 * @return bool
 */
function wc_gzd_is_shipment_status( $maybe_status ) {
	$shipment_statuses = wc_gzd_get_shipment_statuses();

	return isset( $shipment_statuses[ $maybe_status ] );
}

/**
 * Main function for returning shipment items.
 *
 * @since  2.2
 *
 * @param mixed $the_item Object or shipment item id.
 * @param string $item_type The shipment item type.
 *
 * @return bool|ShipmentItem
 */
function wc_gzd_get_shipment_item( $the_item = false, $item_type = 'simple' ) {
	$item_id = wc_gzd_get_shipment_item_id( $the_item );

	if ( ! $item_id ) {
		return false;
	}

	$item_class = 'Vendidero\Germanized\Shipments\ShipmentItem';

	if ( 'return' === $item_type ) {
		$item_class = 'Vendidero\Germanized\Shipments\ShipmentReturnItem';
	}

	/**
	 * Filter to adjust the classname used to construct a ShipmentItem.
	 *
	 * @param string  $classname The classname to be used.
	 * @param integer $item_id The shipment item id.
	 * @param string  $item_type The shipment item type.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	$classname = apply_filters( 'woocommerce_gzd_shipment_item_class', $item_class, $item_id, $item_type );

	if ( ! class_exists( $classname ) ) {
		return false;
	}

	try {
		return new $classname( $item_id );
	} catch ( Exception $e ) {
		wc_caught_exception( $e, __FUNCTION__, array( $the_item, $item_type ) );
		return false;
	}
}

/**
 * Get the shipment item ID depending on what was passed.
 *
 * @since 3.0.0
 * @param  mixed $item Item data to convert to an ID.
 * @return int|bool false on failure
 */
function wc_gzd_get_shipment_item_id( $item ) {
	if ( is_numeric( $item ) ) {
		return $item;
	} elseif ( $item instanceof Vendidero\Germanized\Shipments\ShipmentItem ) {
		return $item->get_id();
	} elseif ( ! empty( $item->shipment_item_id ) ) {
		return $item->shipment_item_id;
	} else {
		return false;
	}
}

/**
 * Format dimensions for display.
 *
 * @since  3.0.0
 * @param  array $dimensions Array of dimensions.
 * @return string
 */
function wc_gzd_format_shipment_dimensions( $dimensions, $unit = '' ) {
	$dimension_string = implode( ' &times; ', array_filter( array_map( 'wc_format_localized_decimal', $dimensions ) ) );

	if ( ! empty( $dimension_string ) ) {
		$unit              = empty( $unit ) ? get_option( 'woocommerce_dimension_unit' ) : $unit;
		$dimension_string .= ' ' . $unit;
	} else {
		$dimension_string = _x( 'N/A', 'shipments', 'woocommerce-germanized' );
	}

	/**
	 * Filter to adjust the format of Shipment dimensions e.g. LxBxH.
	 *
	 * @param string  $dimension_string The dimension string.
	 * @param array   $dimensions Array containing the dimensions.
	 * @param string  $unit The dimension unit.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_format_shipment_dimensions', $dimension_string, $dimensions, $unit );
}

/**
 * Format a weight for display.
 *
 * @since  3.0.0
 * @param  float $weight Weight.
 * @return string
 */
function wc_gzd_format_shipment_weight( $weight, $unit = '' ) {
	$weight_string = wc_format_localized_decimal( $weight );

	if ( ! empty( $weight_string ) ) {
		$unit           = empty( $unit ) ? get_option( 'woocommerce_weight_unit' ) : $unit;
		$weight_string .= ' ' . $unit;
	} else {
		$weight_string = _x( 'N/A', 'shipments', 'woocommerce-germanized' );
	}

	/**
	 * Filter to adjust the format of Shipment weight.
	 *
	 * @param string  $weight_string The weight string.
	 * @param string  $weight The Shipment weight.
	 * @param string  $unit The dimension unit.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_format_shipment_weight', $weight_string, $weight, $unit );
}

/**
 * Get My Account > Shipments columns.
 *
 * @since 3.0.0
 * @return array
 */
function wc_gzd_get_account_shipments_columns( $type = 'simple' ) {
	/**
	 * Filter to adjust columns being used to display shipments in a table view on the customer
	 * account page.
	 *
	 * @param string[] $columns The columns in key => value pairs.
	 * @param string   $type    The shipment type e.g. simple or return.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	$columns = apply_filters(
		'woocommerce_gzd_account_shipments_columns',
		array(
			'shipment-number'   => _x( 'Shipment', 'shipments', 'woocommerce-germanized' ),
			'shipment-date'     => _x( 'Date', 'shipments', 'woocommerce-germanized' ),
			'shipment-status'   => _x( 'Status', 'shipments', 'woocommerce-germanized' ),
			'shipment-tracking' => _x( 'Tracking', 'shipments', 'woocommerce-germanized' ),
			'shipment-actions'  => _x( 'Actions', 'shipments', 'woocommerce-germanized' ),
		),
		$type
	);

	return $columns;
}

function wc_gzd_get_order_customer_add_return_url( $order ) {

	if ( ! $shipment_order = wc_gzd_get_shipment_order( $order ) ) {
		return false;
	}

	$url = wc_get_endpoint_url( 'add-return-shipment', $shipment_order->get_order()->get_id(), wc_get_page_permalink( 'myaccount' ) );

	if ( $shipment_order->get_order()->get_customer_id() <= 0 ) {
		$key = $shipment_order->get_order_return_request_key();

		if ( ! empty( $key ) ) {
			$url = add_query_arg( array( 'key' => $key ), $url );
		} else {
			$url = '';
		}
	}

	/**
	 * Filter to adjust the URL the customer (or guest) might access to add a return to a certain order.
	 *
	 * @param string   $url The URL pointing to the add return page.
	 * @param Order    $order The order object.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_shipments_add_return_shipment_url', $url, $shipment_order->get_order() );
}

/**
 * @param WC_Order $order
 *
 * @return mixed
 */
function wc_gzd_order_is_customer_returnable( $order, $check_date = true ) {
	$is_returnable = false;

	if ( ! $shipment_order = wc_gzd_get_shipment_order( $order ) ) {
		return false;
	}

	if ( $provider = wc_gzd_get_order_shipping_provider( $order ) ) {
		$is_returnable = $provider->supports_customer_returns( $shipment_order->get_order() );

		if ( $shipment_order->get_order()->get_customer_id() <= 0 && ! $provider->supports_guest_returns() ) {
			$is_returnable = false;
		}
	}

	// Shipment is fully returned
	if ( ! $shipment_order->needs_return() ) {
		$is_returnable = false;
	}

	// Check days left for return
	$maximum_days = Package::get_setting( 'customer_return_open_days' );

	if ( $check_date && ! empty( $maximum_days ) ) {
		$maximum_days = absint( $maximum_days );

		if ( ! empty( $maximum_days ) ) {

			$completed_date = $shipment_order->get_order()->get_date_created();

			if ( $shipment_order->get_date_shipped() ) {
				$completed_date = $shipment_order->get_date_shipped();
			} elseif ( $shipment_order->get_order()->get_date_completed() ) {
				$completed_date = $shipment_order->get_order()->get_date_completed();
			}

			/**
			 * Filter to adjust the completed date of an order used to determine whether an order is
			 * still returnable by the customer or not. The date is constructed by checking for existence in the following order:
			 *
			 * 1. The date the order was shipped completely
			 * 2. The date the order was marked as completed
			 * 3. The date the order was created
			 *
			 * @param WC_DateTime $completed_date The order completed date.
			 * @param WC_Order    $order The order instance.
			 *
			 * @since 3.1.0
			 * @package Vendidero/Germanized/Shipments
			 */
			$completed_date = apply_filters( 'woocommerce_gzd_order_return_completed_date', $completed_date, $shipment_order->get_order() );

			if ( $completed_date ) {
				$today = new WC_DateTime();
				$diff  = $today->diff( $completed_date );

				if ( $diff->days > $maximum_days ) {
					$is_returnable = false;
				}
			}
		}
	}

	/**
	 * Filter to decide whether a customer might add return request to a certain order.
	 *
	 * @param bool     $is_returnable Whether or not shipment supports customer added returns
	 * @param WC_Order $order The order instance for which the return shall be created.
	 * @param bool     $check_date Whether to check for a maximum date or not.
	 *
	 * @since 3.1.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_order_is_returnable_by_customer', $is_returnable, $shipment_order->get_order(), $check_date );
}

/**
 * @param $order
 *
 * @return bool|\Vendidero\Germanized\Shipments\Interfaces\ShippingProvider
 */
function wc_gzd_get_order_shipping_provider( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	if ( ! $order ) {
		return false;
	}

	$provider = false;

	if ( $method = wc_gzd_get_shipping_provider_method( wc_gzd_get_shipment_order_shipping_method_id( $order ) ) ) {
		$provider = $method->get_provider_instance();
	}

	/**
	 * Filters the shipping provider detected for a specific order.
	 *
	 * @param bool|\Vendidero\Germanized\Shipments\Interfaces\ShippingProvider $provider The shipping provider instance.
	 * @param WC_Order              $order The order instance.
	 *
	 * @since 3.1.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_get_order_shipping_provider', $provider, $order );
}

function wc_gzd_get_customer_order_return_request_key() {
	$key = ( isset( $_REQUEST['key'] ) ? wc_clean( wp_unslash( $_REQUEST['key'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return $key;
}

function wc_gzd_customer_can_add_return_shipment( $order_id ) {
	$can_view_shipments = false;

	if ( isset( $_REQUEST['key'] ) && ! empty( $_REQUEST['key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = wc_gzd_get_customer_order_return_request_key();

		if ( ( $order_shipment = wc_gzd_get_shipment_order( $order_id ) ) && ! empty( $key ) ) {

			if ( hash_equals( $order_shipment->get_order_return_request_key(), $key ) ) {
				$can_view_shipments = true;
			}
		}
	} elseif ( is_user_logged_in() ) {
		$can_view_shipments = current_user_can( 'view_order', $order_id );
	}

	/**
	 * Filters whether a logged in user (or guest) might view shipments belonging to an order or not.
	 *
	 * @param bool    $can_view_shipments Whether the user (or guest) might see shipments or not.
	 * @param integer $order_id The order id.
	 *
	 * @since 3.1.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_customer_can_view_shipments', $can_view_shipments, $order_id );
}

/**
 * @param WC_Order|integer $order
 */
function wc_gzd_customer_return_needs_manual_confirmation( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	if ( ! $order ) {
		return true;
	}

	$needs_manual_confirmation = true;

	if ( $provider = wc_gzd_get_order_shipping_provider( $order ) ) {
		$needs_manual_confirmation = $provider->needs_manual_confirmation_for_returns();
	}

	/**
	 * Filter to decide whether a customer added return of a certain order
	 * needs manual confirmation by the shop manager or not.
	 *
	 * @param bool     $needs_manual_confirmation Whether needs manual confirmation or not.
	 * @param WC_Order $order The order instance for which the return shall be created.
	 *
	 * @since 3.1.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_customer_return_needs_manual_confirmation', $needs_manual_confirmation, $order );
}

/**
 * Get account shipments actions.
 *
 * @since  3.2.0
 * @param  int|Shipment $shipment Shipment instance or ID.
 * @return array
 */
function wc_gzd_get_account_shipments_actions( $shipment ) {

	if ( ! is_object( $shipment ) ) {
		$shipment_id = absint( $shipment );
		$shipment    = wc_gzd_get_shipment( $shipment_id );
	}

	if ( ! $shipment ) {
		return array();
	}

	$actions = array(
		'view' => array(
			'url'  => $shipment->get_view_shipment_url(),
			'name' => _x( 'View', 'shipments', 'woocommerce-germanized' ),
		),
	);

	if ( 'return' === $shipment->get_type() && $shipment->has_label() && ! $shipment->has_status( 'delivered' ) ) {
		$actions['download-label'] = array(
			'url'  => $shipment->get_label()->get_download_url(),
			'name' => _x( 'Download label', 'shipments', 'woocommerce-germanized' ),
		);
	}

	/**
	 * Filter to adjust available actions in the shipments table view on the customer account page
	 * for a specific shipment.
	 *
	 * @param string[] $actions Available actions containing an id as key and a URL and name.
	 * @param Shipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/Shipments
	 */
	return apply_filters( 'woocommerce_gzd_account_shipments_actions', $actions, $shipment );
}

function wc_gzd_shipments_get_product( $the_product ) {
	try {
		if ( is_a( $the_product, '\Vendidero\Germanized\Shipments\Product' ) ) {
			return $the_product;
		}

		return new \Vendidero\Germanized\Shipments\Product( $the_product );
	} catch ( \Exception $e ) {
		return false;
	}
}

function wc_gzd_get_volume_dimension( $dimension, $to_unit, $from_unit = '' ) {
	$to_unit = strtolower( $to_unit );

	if ( empty( $from_unit ) ) {
		$from_unit = strtolower( get_option( 'woocommerce_dimension_unit' ) );
	}

	// Unify all units to cm first.
	if ( $from_unit !== $to_unit ) {
		switch ( $from_unit ) {
			case 'm':
				$dimension *= 1000000;
				break;
			case 'mm':
				$dimension *= 0.001;
				break;
		}

		// Output desired unit.
		switch ( $to_unit ) {
			case 'm':
				$dimension *= 0.000001;
				break;
			case 'mm':
				$dimension *= 1000;
				break;
		}
	}

	return ( $dimension < 0 ) ? 0 : $dimension;
}

if ( ! function_exists( 'wc_gzd_wp_theme_get_element_class_name' ) ) {
	/**
	 * Given an element name, returns a class name.
	 *
	 * If the WP-related function is not defined, return empty string.
	 *
	 * @param string $element The name of the element.
	 *
	 * @return string
	 */
	function wc_gzd_wp_theme_get_element_class_name( $element ) {
		if ( function_exists( 'wc_wp_theme_get_element_class_name' ) ) {
			return wc_wp_theme_get_element_class_name( $element );
		} elseif ( function_exists( 'wp_theme_get_element_class_name' ) ) {
			return wp_theme_get_element_class_name( $element );
		}

		return '';
	}
}

/**
 * Forces a WP_Error object to be converted to a ShipmentError.
 *
 * @param $error
 *
 * @return mixed|\Vendidero\Germanized\Shipments\ShipmentError
 */
function wc_gzd_get_shipment_error( $error ) {
	if ( ! is_wp_error( $error ) ) {
		return $error;
	} elseif ( is_a( $error, 'Vendidero\Germanized\Shipments\ShipmentError' ) ) {
		return $error;
	} else {
		return \Vendidero\Germanized\Shipments\ShipmentError::from_wp_error( $error );
	}
}
