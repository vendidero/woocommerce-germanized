<?php
/**
 * Shipment Functions
 *
 * Functions for shipment specific things.
 *
 * @version 3.4.0
 */
use Vendidero\Shiptastic\Order;
use Vendidero\Shiptastic\ReturnReason;
use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\AddressSplitter;
use Vendidero\Shiptastic\ShipmentFactory;
use Vendidero\Shiptastic\ShipmentItem;
use Vendidero\Shiptastic\ShipmentReturnItem;
use Vendidero\Shiptastic\SimpleShipment;
use Vendidero\Shiptastic\ReturnShipment;
use Vendidero\Shiptastic\Package;
use Vendidero\Shiptastic\ShippingProvider;

defined( 'ABSPATH' ) || exit;

function wc_stc_get_formatted_state( $country = '', $state = '' ) {
	if ( empty( $country ) ) {
		return '';
	}

	$states          = WC()->countries ? WC()->countries->get_states( $country ) : array();
	$formatted_state = ( $states && isset( $states[ $state ] ) ) ? $states[ $state ] : $state;

	return $formatted_state;
}

function wc_stc_parse_pickup_location_code( $location_code ) {
	$parts = wc_stc_get_pickup_location_code_parts( $location_code );

	return $parts['code'];
}

function wc_stc_get_pickup_location_code_parts( $location_code ) {
	$parts      = array(
		'code'              => $location_code,
		'postcode'          => '',
		'country'           => '',
		'shipping_provider' => '',
	);
	$code_parts = explode( '_', $location_code );

	if ( count( $code_parts ) >= 3 ) {
		$parts['code']     = $code_parts[0];
		$parts['country']  = strtoupper( $code_parts[1] );
		$parts['postcode'] = $code_parts[2];

		if ( count( $code_parts ) >= 4 ) {
			$parts['shipping_provider'] = str_replace( '#', '_', $code_parts[3] );
		}
	}

	return $parts;
}

function wc_stc_country_to_alpha3( $country ) {
	return Package::get_country_iso_alpha3( $country );
}

function wc_stc_get_customer_preferred_shipping_provider( $user_id ) {
	$default_provider = wc_stc_get_default_shipping_provider();
	$provider         = false;

	if ( ! $default_provider ) {
		$available        = wc_stc_get_available_shipping_providers();
		$default_provider = 1 === count( $available ) ? array_values( $available )[0] : false;
	}

	if ( $customer = new WC_Customer( $user_id ) ) {
		if ( $last_order = $customer->get_last_order() ) {
			$provider = wc_stc_get_order_shipping_provider( $last_order );
		}
	}

	if ( ! $provider && $default_provider ) {
		$provider = wc_stc_get_shipping_provider( $default_provider );
	}

	return apply_filters( 'woocommerce_shiptastic_customer_shipping_provider', $provider, $user_id );
}

function wc_stc_country_to_alpha2( $country ) {
	return Package::get_country_iso_alpha2( $country );
}

function wc_stc_get_shipment_order( $order ) {
	return \Vendidero\Shiptastic\Orders\Factory::get_order( $order );
}

function wc_stc_get_shipment_label_title( $type, $plural = false ) {
	$type_data = wc_stc_get_shipment_type_data( $type );

	return ( ! $plural ? $type_data['labels']['singular'] : $type_data['labels']['plural'] );
}

function wc_stc_get_shipping_label_zones() {
	return apply_filters(
		'woocommerce_shiptastic_shipping_label_zones',
		array(
			'dom' => _x( 'Domestic', 'shipments', 'woocommerce-germanized' ),
			'eu'  => _x( 'EU', 'shipments', 'woocommerce-germanized' ),
			'int' => _x( 'International', 'shipments', 'woocommerce-germanized' ),
		)
	);
}

function wc_stc_get_shipping_label_zone_title( $zone ) {
	$zones = wc_stc_get_shipping_label_zones();
	$title = array_key_exists( $zone, $zones ) ? $zones[ $zone ] : '';

	return apply_filters( 'woocommerce_shiptastic_shipping_label_zone_title', $title, $zone );
}

function wc_stc_get_shipping_shipments_label_zone_title( $zone ) {
	$title = _x( '%1$s shipments', 'shipments-zone-title', 'woocommerce-germanized' );

	$zones = array(
		'dom' => _x( 'Domestic Shipments', 'shipments', 'woocommerce-germanized' ),
		'eu'  => _x( 'EU Shipments', 'shipments', 'woocommerce-germanized' ),
		'int' => _x( 'International Shipments', 'shipments', 'woocommerce-germanized' ),
	);

	$title = array_key_exists( $zone, $zones ) ? $zones[ $zone ] : $title;

	return apply_filters( 'woocommerce_shiptastic_shipping_shipments_label_zone_title', $title, $zone );
}

function wc_stc_get_shipment_types() {
	return array_keys( wc_stc_get_shipment_type_data( false ) );
}

/**
 * Get shipment type data by type.
 *
 * @param  string $type type name.
 * @return bool|array Details about the shipment type.
 *
 * @package Vendidero/Shiptastic
 */
function wc_stc_get_shipment_type_data( $type = false ) {
	$types = apply_filters(
		'woocommerce_shiptastic_shipment_type_data',
		array(
			'simple' => array(
				'class_name' => '\Vendidero\Shiptastic\SimpleShipment',
				'labels'     => array(
					'singular' => _x( 'Shipment', 'shipments', 'woocommerce-germanized' ),
					'plural'   => _x( 'Shipments', 'shipments', 'woocommerce-germanized' ),
				),
			),
			'return' => array(
				'class_name' => '\Vendidero\Shiptastic\ReturnShipment',
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

function wc_stc_get_shipments_by_order( $order ) {
	$shipments = array();

	if ( $order_shipment = wc_stc_get_shipment_order( $order ) ) {
		$shipments = $order_shipment->get_shipments();
	}

	return $shipments;
}

function wc_stc_get_order_last_tracking_id( $order ) {
	$tracking_id = '';

	if ( $order_shipment = wc_stc_get_shipment_order( $order ) ) {
		$tracking_id = $order_shipment->get_last_tracking_id();
	}

	return $tracking_id;
}

/**
 * @param $tracking_id
 *
 * @return false|Shipment
 */
function wc_stc_get_shipment_by_tracking_id( $tracking_id ) {
	$shipments = wc_stc_get_shipments(
		array(
			'tracking_id' => $tracking_id,
			'limit'       => 1,
		)
	);

	if ( ! empty( $shipments ) ) {
		return $shipments[0];
	}

	return false;
}

function wc_stc_get_shipment_order_shipping_statuses() {
	$shipment_statuses = array(
		'not-shipped'                  => _x( 'Not shipped', 'shipments', 'woocommerce-germanized' ),
		'ready-for-shipping'           => _x( 'Ready for shipping', 'shipments', 'woocommerce-germanized' ),
		'partially-ready-for-shipping' => _x( 'Partially ready for shipping', 'shipments', 'woocommerce-germanized' ),
		'partially-shipped'            => _x( 'Partially shipped', 'shipments', 'woocommerce-germanized' ),
		'shipped'                      => _x( 'Shipped', 'shipments', 'woocommerce-germanized' ),
		'partially-delivered'          => _x( 'Partially delivered', 'shipments', 'woocommerce-germanized' ),
		'delivered'                    => _x( 'Delivered', 'shipments', 'woocommerce-germanized' ),
		'no-shipping-needed'           => _x( 'No shipping needed', 'shipments', 'woocommerce-germanized' ),
	);

	/**
	 * Filter to adjust or add order shipping statuses.
	 * An order might retrieve a shipping status e.g. not shipped.
	 *
	 * @param array $shipment_statuses Available order shipping statuses.
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_order_shipping_statuses', $shipment_statuses );
}

function wc_stc_get_shipment_order_return_statuses() {
	$shipment_statuses = array(
		'open'               => _x( 'Open', 'shipments', 'woocommerce-germanized' ),
		'partially-returned' => _x( 'Partially returned', 'shipments', 'woocommerce-germanized' ),
		'returned'           => _x( 'Returned', 'shipments', 'woocommerce-germanized' ),
	);

	/**
	 * Filter to adjust or add order return statuses.
	 * An order might retrieve a shipping status e.g. not shipped.
	 *
	 * @param array $shipment_statuses Available order return statuses.
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_order_return_statuses', $shipment_statuses );
}

/**
 * @param $instance_id
 *
 * @return \Vendidero\Shiptastic\ShippingMethod\ProviderMethod|false
 */
function wc_stc_get_shipping_provider_method( $instance_id ) {
	return \Vendidero\Shiptastic\ShippingMethod\MethodHelper::get_provider_method( $instance_id );
}

/**
 * Returns the current shipping method rate id.
 *
 * @return false|string
 */
function wc_stc_get_current_shipping_method_id() {
	$chosen_shipping_methods = wc_stc_get_current_shipping_method_ids();

	if ( ! empty( $chosen_shipping_methods ) ) {
		return reset( $chosen_shipping_methods );
	}

	return false;
}

/**
 * Checks whether the current cart/checkout contains multiple packages.
 *
 * @return bool
 */
function wc_stc_cart_has_multiple_packages() {
	return count( WC()->shipping()->get_packages() ) > 1;
}

/**
 * @return array
 */
function wc_stc_get_current_shipping_method_ids() {
	return WC()->session ? (array) WC()->session->get( 'chosen_shipping_methods' ) : array();
}

function wc_stc_get_current_shipping_provider_method() {
	if ( $current = wc_stc_get_current_shipping_method_id() ) {
		if ( $method = wc_stc_get_shipping_provider_method( $current ) ) {
			if ( wc_stc_cart_has_multiple_packages() ) {
				$method = clone $method;
				$method->set_provider_is_disabled( true );
			}

			return $method;
		}
	}

	return false;
}

/**
 * @return \Vendidero\Shiptastic\ShippingMethod\ProviderMethod[]
 */
function wc_stc_get_current_shipping_provider_methods() {
	$methods = array();

	foreach ( wc_stc_get_current_shipping_method_ids() as $shipping_method_id ) {
		if ( $method = wc_stc_get_shipping_provider_method( $shipping_method_id ) ) {
			$methods[ $shipping_method_id ] = $method;
		}
	}

	return $methods;
}

function wc_stc_get_prefixed_shipment_status_name( $status ) {
	return $status;
}

function wc_stc_get_shipment_order_shipping_status_name( $status ) {
	$status_name = '';
	$statuses    = wc_stc_get_shipment_order_shipping_statuses();

	if ( array_key_exists( $status, $statuses ) ) {
		$status_name = $statuses[ $status ];
	}

	/**
	 * Filter to adjust the status name for a certain order shipping status.
	 *
	 * @see wc_stc_get_shipment_order_shipping_statuses()
	 *
	 * @param string $status_name The status name.
	 * @param string $status The shipping status.
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_order_shipping_status_name', $status_name, $status );
}

function wc_stc_get_shipment_order_return_status_name( $status ) {
	$status_name = '';
	$statuses    = wc_stc_get_shipment_order_return_statuses();

	if ( array_key_exists( $status, $statuses ) ) {
		$status_name = $statuses[ $status ];
	}

	/**
	 * Filter to adjust the status name for a certain order return status.
	 *
	 * @see wc_stc_get_shipment_order_return_statuses()
	 *
	 * @param string $status_name The status name.
	 * @param string $status The return status.
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_order_return_status_name', $status_name, $status );
}

/**
 * Standard way of retrieving shipments based on certain parameters.
 *
 * @param  array $args Array of args (above).
 *
 * @return Shipment[] The shipments found.
 */
function wc_stc_get_shipments( $args ) {
	$query = new Vendidero\Shiptastic\ShipmentQuery( $args );

	return $query->get_shipments();
}

function wc_stc_get_shipment_customer_visible_statuses( $shipment_type = 'simple' ) {
	$statuses = array_keys( wc_stc_get_shipment_statuses() );
	$statuses = array_diff( $statuses, array( 'draft' ) );

	/**
	 * Filter to decide which shipment statuses should be visible to customers
	 * e.g. whether a shipment of a certain status should be shown or not.
	 *
	 * @param array  $shipment_statuses The available shipment statuses.
	 * @param string $shipment_type The shipment type.
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_shipment_customer_visible_statuses', $statuses, $shipment_type );
}

/**
 * Main function for returning shipments.
 *
 * @param  mixed $the_shipment Object or shipment id.
 *
 * @return bool|SimpleShipment|ReturnShipment|Shipment
 */
function wc_stc_get_shipment( $the_shipment ) {
	return ShipmentFactory::get_shipment( $the_shipment );
}

/**
 * Get all shipment statuses.
 *
 * @return array
 */
function wc_stc_get_shipment_statuses() {
	$shipment_statuses = array(
		'draft'              => _x( 'Draft', 'shipments', 'woocommerce-germanized' ),
		'processing'         => _x( 'Processing', 'shipments', 'woocommerce-germanized' ),
		'ready-for-shipping' => _x( 'Ready for shipping', 'shipments', 'woocommerce-germanized' ),
		'shipped'            => _x( 'Shipped', 'shipments', 'woocommerce-germanized' ),
		'delivered'          => _x( 'Delivered', 'shipments', 'woocommerce-germanized' ),
		'requested'          => _x( 'Requested', 'shipments', 'woocommerce-germanized' ),
	);

	/**
	 * Add or adjust available Shipment statuses.
	 *
	 * @param array $shipment_statuses The available shipment statuses.
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_shipment_statuses', $shipment_statuses );
}

/**
 * @param Shipment $shipment
 *
 * @return mixed|void
 */
function wc_stc_get_shipment_selectable_statuses( $shipment ) {
	$shipment_statuses = wc_stc_get_shipment_statuses();

	if ( ! $shipment->has_status( 'requested' ) && isset( $shipment_statuses['requested'] ) ) {
		unset( $shipment_statuses['requested'] );
	}

	/**
	 * Add or remove selectable shipment statuses for a certain shipment and/or shipment type.
	 *
	 * @param array    $shipment_statuses The available shipment statuses.
	 * @param string   $type The shipment type e.g. return.
	 * @param Shipment $shipment The shipment instance.
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_shipment_selectable_statuses', $shipment_statuses, $shipment->get_type(), $shipment );
}

/**
 * @param Order $order_shipment
 * @param array $args
 *
 * @return ReturnShipment|WP_Error
 */
function wc_stc_create_return_shipment( $order_shipment, $args = array() ) {
	try {
		if ( ! $order_shipment || ! is_a( $order_shipment, 'Vendidero\Shiptastic\Order' ) ) {
			throw new Exception( esc_html_x( 'Invalid order.', 'shipments', 'woocommerce-germanized' ) );
		}

		if ( ! $order_shipment->needs_return() ) {
			throw new Exception( esc_html_x( 'This order is already fully returned.', 'shipments', 'woocommerce-germanized' ) );
		}

		$args = wp_parse_args(
			$args,
			array(
				'items' => array(),
				'props' => array(),
				'save'  => true,
			)
		);

		$shipment = ShipmentFactory::get_shipment( false, 'return' );

		if ( ! $shipment ) {
			throw new Exception( esc_html_x( 'Error while creating the shipment instance', 'shipments', 'woocommerce-germanized' ) );
		}

		// Make sure shipment knows its parent
		$shipment->set_order_shipment( $order_shipment );
		$shipment->sync( $args['props'] );
		$shipment->sync_items( $args );
		$shipment->calculate_return_costs();

		if ( $args['save'] ) {
			$shipment->save();
		}
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
function wc_stc_create_shipment( $order_shipment, $args = array() ) {
	try {
		if ( ! $order_shipment || ! is_a( $order_shipment, 'Vendidero\Shiptastic\Order' ) ) {
			throw new Exception( esc_html_x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized' ) );
		}

		if ( ! $order = $order_shipment->get_order() ) {
			throw new Exception( esc_html_x( 'Invalid shipment order', 'shipments', 'woocommerce-germanized' ) );
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
			throw new Exception( esc_html_x( 'Error while creating the shipment instance', 'shipments', 'woocommerce-germanized' ) );
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

function wc_stc_create_shipment_item( $shipment, $order_item, $args = array() ) {
	try {
		if ( ! $order_item || ! is_a( $order_item, 'WC_Order_Item' ) ) {
			throw new Exception( esc_html_x( 'Invalid order item', 'shipments', 'woocommerce-germanized' ) );
		}

		$item = new Vendidero\Shiptastic\ShipmentItem();

		$item->set_order_item_id( $order_item->get_id() );
		$item->set_shipment( $shipment );
		$item->sync( $args );

		if ( $shipment->get_id() > 0 ) {
			$item->save();
		}
	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}

	return $item;
}

function wc_stc_allow_customer_return_empty_return_reason( $order ) {
	return apply_filters( 'woocommerce_shiptastic_allow_customer_return_empty_return_reason', true, $order );
}

/**
 * @param bool $allow_none
 * @param bool|WC_Order_Item $order_item
 *
 * @return ReturnReason[]
 */
function wc_stc_get_return_shipment_reasons( $order_item = false ) {
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
	 * @package Vendidero/Shiptastic
	 */
	$reasons   = apply_filters( 'woocommerce_shiptastic_return_shipment_reasons_raw', $reasons, $order_item );
	$instances = array();

	foreach ( $reasons as $reason ) {
		$instances[] = new ReturnReason( $reason );
	}

	usort( $instances, '_wc_stc_sort_return_shipment_reasons' );

	/**
	 * Filter that allows to adjust available return reasons for a specific shipment.
	 *
	 * @param ReturnReason[]         $reasons Available return reasons.
	 * @param WC_Order_Item|false    $order_item The order item object if available to further filter reasons.
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_return_shipment_reasons', $instances, $order_item );
}

function wc_stc_return_shipment_reason_exists( $maybe_reason, $shipment = false ) {
	$reasons = wc_stc_get_return_shipment_reasons( $shipment );
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
function _wc_stc_sort_return_shipment_reasons( $a, $b ) {
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
function wc_stc_shipment_wp_error_has_errors( $error ) {
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
function wc_stc_create_return_shipment_item( $shipment, $shipment_item, $args = array() ) {
	try {
		if ( ! $shipment_item || ! is_a( $shipment_item, '\Vendidero\Shiptastic\ShipmentItem' ) ) {
			throw new Exception( esc_html_x( 'Invalid shipment item', 'shipments', 'woocommerce-germanized' ) );
		}

		$item = new Vendidero\Shiptastic\ShipmentReturnItem();
		$item->set_order_item_id( $shipment_item->get_order_item_id() );
		$item->set_shipment( $shipment );
		$item->sync( $args );
		$item->save();
	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}

	return $item;
}

function wc_stc_get_shipment_editable_statuses() {
	/**
	 * Filter that allows to adjust Shipment statuses which decide upon whether
	 * a Shipment is editable or not.
	 *
	 * @param array $statuses Statuses which should be considered as editable.
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_shipment_editable_statuses', array( 'draft', 'requested', 'processing' ) );
}

/**
 * @param Shipment $shipment
 */
function wc_stc_get_shipment_address_addition( $shipment ) {
	$addition        = $shipment->get_address_2();
	$street_addition = $shipment->get_address_street_addition();

	if ( ! empty( $street_addition ) ) {
		$addition = $street_addition . ( ! empty( $addition ) ? ' ' . $addition : '' );
	}

	return trim( $addition );
}

function wc_stc_get_address_from_street_and_number( $street, $number, $country ) {
	$pad_to         = 'right';
	$countries_left = array(
		'CA',
		'US',
		'GB',
		'FR',
		'AU',
	);

	if ( in_array( $country, $countries_left, true ) ) {
		$pad_to = 'left';
	}

	if ( 'left' === $pad_to ) {
		$address = ( ! empty( $number ) ? $number . ' ' : '' ) . $street;
	} else {
		$address = $street . ( ! empty( $number ) ? ' ' . $number : '' );
	}

	return $address;
}

/**
 * Formats address_1 and address_2 based on known formats for certain countries.
 * Falls back to searching house number in address_2 if house number is not provided in address_1 line.
 *
 * @param array|string $address
 * @param string $country
 *
 * @return array
 */
function wc_stc_get_formatted_address_data( $address, $country ) {
	if ( is_string( $address ) ) {
		$address = array(
			'address_1' => $address,
			'address_2' => '',
		);
	}

	$address = wp_parse_args(
		(array) $address,
		array(
			'address_1'                 => '',
			'address_2'                 => '',
			'house_number_in_address_2' => false,
		)
	);

	$format = Package::get_country_street_format( $country );

	if ( $format && ! empty( $address['address_1'] ) ) {
		$split = wc_stc_split_shipment_street( $address['address_1'] );

		/**
		 * Fallback to searching for house number in both address_1 and address_2.
		 */
		if ( empty( $split['number'] ) ) {
			$full_split = wc_stc_split_shipment_street( wc_stc_format_address_line( $format, $address['address_2'], $address['address_1'] ) );

			if ( ! empty( $full_split['number'] ) ) {
				$address['house_number_in_address_2'] = true;
				$split                                = $full_split;
			}
		}

		if ( ! empty( $split['number'] ) ) {
			$address_addition     = $split['addition'] . ( ! empty( $split['addition_2'] ) ? ' ' . $split['addition_2'] : '' );
			$address['address_1'] = wc_stc_format_address_line( $format, $split['number'], $split['street'], $address_addition );

			if ( ! empty( $address_addition ) ) {
				$address['address_2'] = $address_addition . ', ' . $address['address_2'];

				/**
				 * Override address_2 field with address addition in case house number is in address_2
				 */
				if ( $address['house_number_in_address_2'] ) {
					$address['address_2'] = $address_addition;

					/**
					 * Address addition (which contains house number) has already been added to address_1
					 */
					if ( strstr( $format, '%sa' ) ) {
						$address['address_2'] = '';
					}
				}
			}

			/**
			 * Remove trailing comma + (duplicate) whitespace
			 */
			$address['address_2'] = preg_replace( '/\s+/', ' ', rtrim( $address['address_2'], ', ' ) );
		}
	}

	return $address;
}

function wc_stc_format_address_line( $format, $number, $street, $street_addition = '' ) {
	$formatted_address_line = str_replace( array( '%n', '%sa', '%s' ), array( $number, $street_addition, $street ), $format );

	/**
	 * Remove trailing comma + (duplicate) whitespace
	 */
	return preg_replace( '/\s+/', ' ', rtrim( $formatted_address_line, ', ' ) );
}

function wc_stc_split_shipment_street( $street_str ) {
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

/**
 * @return ShippingProvider\Auto[]|ShippingProvider\Simple[]
 */
function wc_stc_get_shipping_providers() {
	return ShippingProvider\Helper::instance()->get_shipping_providers();
}

function wc_stc_get_available_shipping_providers() {
	return ShippingProvider\Helper::instance()->get_available_shipping_providers();
}

function wc_stc_get_shipping_provider( $name ) {
	return ShippingProvider\Helper::instance()->get_shipping_provider( $name );
}

function wc_stc_get_default_shipping_provider() {
	$default = Package::get_setting( 'default_shipping_provider' );

	/**
	 * Filter to adjust the default shipping provider used as a fallback for shipments
	 * for which no provider could be determined automatically (e.g. by chosen shipping method).
	 *
	 * @param string  $title The shipping provider slug.
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_default_shipping_provider', $default );
}

/**
 * @return \Vendidero\Shiptastic\Interfaces\ShippingProvider|ShippingProvider\Auto|ShippingProvider\Simple|null
 */
function wc_stc_get_default_shipping_provider_instance() {
	$default = wc_stc_get_default_shipping_provider();

	if ( ! empty( $default ) && ( $provider = wc_stc_get_shipping_provider( $default ) ) ) {
		if ( $provider->is_activated() ) {
			return $provider;
		}
	}

	return null;
}

/**
 * @param $props
 * @param $is_manual
 *
 * @return ShippingProvider\Simple
 */
function wc_stc_create_shipping_provider( $props = array(), $is_manual = true ) {
	$props = wp_parse_args(
		$props,
		array(
			'title'                    => '',
			'description'              => '',
			'tracking_url_placeholder' => '',
		)
	);

	$provider = new ShippingProvider\Simple();
	$provider->set_props( $props );

	if ( empty( $provider->get_tracking_desc_placeholder( 'edit' ) ) ) {
		$provider->set_tracking_desc_placeholder( $provider->get_default_tracking_desc_placeholder() );
	}

	if ( empty( $provider->get_tracking_url_placeholder( 'edit' ) ) ) {
		$provider->set_tracking_url_placeholder( $provider->get_default_tracking_url_placeholder() );
	}

	if ( $is_manual ) {
		add_filter( 'woocommerce_shiptastic_shipping_provider_is_manual_creation_request', '__return_true', 9999 );
	}

	$provider->activate();
	$provider->save();

	if ( $is_manual ) {
		remove_filter( 'woocommerce_shiptastic_shipping_provider_is_manual_creation_request', '__return_true', 9999 );
	}

	return $provider;
}

function wc_stc_get_shipping_provider_select( $include_none = true ) {
	$providers = wc_stc_get_shipping_providers();
	$select    = $include_none ? array(
		'' => _x( 'None', 'shipments', 'woocommerce-germanized' ),
	) : array();

	foreach ( $providers as $provider ) {
		if ( ! $provider->is_activated() ) {
			continue;
		}
		$select[ $provider->get_name() ] = $provider->get_title();
	}

	return $select;
}

function wc_stc_get_shipping_provider_title( $slug ) {
	$providers = wc_stc_get_shipping_providers();

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
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_shipping_provider_title', $title, $slug );
}

/**
 * @param Shipment $shipment
 */
function wc_stc_get_shipment_shipping_provider_title( $shipment ) {
	$title = $shipment->get_shipping_provider_title();

	if ( empty( $title ) ) {
		$title = apply_filters( 'woocommerce_shiptastic_shipping_provider_unknown_title', _x( 'Unknown', 'shipments-shipping-provider', 'woocommerce-germanized' ) );
	}

	return $title;
}

function wc_stc_get_shipping_provider_service_locations() {
	return array( 'settings', 'shipping_provider_settings', 'shipping_method_settings', 'packaging_settings', 'labels', 'label_services' );
}

function wc_stc_get_shipping_provider_slug( $provider ) {
	$providers = wc_stc_get_shipping_providers();

	if ( in_array( $provider, $providers, true ) ) {
		$slug = array_search( $provider, $providers, true );
	} elseif ( array_key_exists( $provider, $providers ) ) {
		$slug = $provider;
	} else {
		$slug = sanitize_key( $provider );
	}

	return $slug;
}

function _wc_shiptastic_keep_force_filename( $new_filename ) {
	return isset( $GLOBALS['stc_unique_filename'] ) ? $GLOBALS['stc_unique_filename'] : $new_filename;
}

function wc_shiptastic_upload_data( $filename, $bits, $relative = true ) {
	try {
		Package::set_upload_dir_filter();
		$GLOBALS['stc_unique_filename'] = $filename;
		add_filter( 'wp_unique_filename', '_wc_shiptastic_keep_force_filename', 10, 1 );

		$tmp = wp_upload_bits( $filename, null, $bits );

		unset( $GLOBALS['stc_unique_filename'] );
		remove_filter( 'wp_unique_filename', '_wc_shiptastic_keep_force_filename', 10 );
		Package::unset_upload_dir_filter();

		if ( isset( $tmp['file'] ) ) {
			$path = $tmp['file'];

			if ( $relative ) {
				$path = Package::get_relative_upload_dir( $path );
			}

			return $path;
		} else {
			throw new Exception( esc_html_x( 'Error while uploading file.', 'shipments', 'woocommerce-germanized' ) );
		}
	} catch ( Exception $e ) {
		return false;
	}
}

function wc_stc_get_shipment_setting_default_address_fields( $type = 'shipper' ) {
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

	return apply_filters( 'woocommerce_shiptastic_shipment_default_address_fields', $address_fields, $type );
}

/**
 * @return array
 */
function wc_stc_get_shipment_setting_address_fields( $address_type = 'shipper' ) {
	$default_address_fields = array_keys( wc_stc_get_shipment_setting_default_address_fields( $address_type ) );

	if ( 'return' === $address_type ) {
		$default_address_data = wc_stc_get_shipment_setting_address_fields( 'shipper' );

		if ( 'no' === Package::get_setting( 'use_alternate_return' ) ) {
			return apply_filters( "woocommerce_shiptastic_shipment_{$address_type}_address_fields", $default_address_data, $address_type );
		}

		$default_address_data['country'] = $default_address_data['country'] . ':' . $default_address_data['state'];
	} else {
		$default_address_data = array(
			'company'   => get_option( 'blogname', '' ),
			'address_1' => get_option( 'woocommerce_store_address', '' ),
			'address_2' => get_option( 'woocommerce_store_address_2', '' ),
			'city'      => get_option( 'woocommerce_store_city', '' ),
			'postcode'  => get_option( 'woocommerce_store_postcode', '' ),
			'email'     => get_option( 'woocommerce_email_from_address', '' ),
			'country'   => get_option( 'woocommerce_default_country', '' ),
		);
	}

	foreach ( $default_address_fields as $prop ) {
		$key   = "woocommerce_shiptastic_{$address_type}_address_{$prop}";
		$value = get_option( $key, null );

		if ( null === $value ) {
			if ( array_key_exists( $prop, $default_address_data ) && ! in_array( $prop, array( 'state' ), true ) ) {
				$value = $default_address_data[ $prop ];
			} else {
				$value = '';
			}
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
		$split = wc_stc_split_shipment_street( $address_fields['address_1'] );

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

	return apply_filters( "woocommerce_shiptastic_shipment_{$address_type}_address_fields", $address_fields, $address_type );
}

/**
 * @param Order $shipment_order
 *
 * @return array
 */
function wc_stc_get_shipment_return_address( $shipment_order = false ) {
	return wc_stc_get_shipment_setting_address_fields( 'return' );
}

/**
 * @param WC_Order|\Vendidero\Shiptastic\Order $order
 * @param string $method_id
 *
 * @return WC_Order_Item_Shipping|false
 */
function wc_stc_get_shipment_order_shipping_method( $order, $method_id = '' ) {
	$method = false;

	if ( is_a( $order, '\Vendidero\Shiptastic\Order' ) ) {
		$shipment_order = $order;
		$order          = $order->get_order();
	} else {
		$shipment_order = wc_stc_get_shipment_order( $order );
	}

	if ( $shipment_order ) {
		$method = $shipment_order->get_shipping_method( $method_id );
	}

	/**
	 * Allows adjusting the shipping method for a certain order.
	 *
	 * @param WC_Order_Item_Shipping|false $method The order item.
	 * @param WC_Order $order The order object.
	 * @param string $method_id The shipping method if, if available
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_shipment_order_shipping_method', $method, $order, $method_id );
}

/**
 * @param WC_Order|\Vendidero\Shiptastic\Order $order
 * @param string $method_id
 *
 * @return string
 */
function wc_stc_get_shipment_order_shipping_method_id( $order, $method_id = '' ) {
	$id = '';

	if ( $method = wc_stc_get_shipment_order_shipping_method( $order, $method_id ) ) {
		$id = $method->get_method_id() . ':' . $method->get_instance_id();
	}

	/**
	 * Allows adjusting the shipping method id for a certain Order.
	 *
	 * @param string   $id The shipping method id.
	 * @param WC_Order $order The order object.
	 * @param string   $method_id The shipping method id, if available
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_shipment_order_shipping_method_id', $id, $order, $method_id );
}

function wc_stc_render_shipment_action_buttons( $actions ) {
	$actions_html = '';

	foreach ( $actions as $action ) {
		$action = wp_parse_args(
			$action,
			array(
				'url'               => '#',
				'group'             => '',
				'action'            => '',
				'target'            => '_self',
				'classes'           => '',
				'name'              => '',
				'custom_attributes' => array(),
				'title'             => '',
			)
		);

		if ( ! empty( $action['group'] ) ) {
			$actions_html .= '<div class="wc-stc-shipment-action-button-group"><label>' . esc_html( $action['group'] ) . '</label> <span class="wc-stc-shipment-action-button-group__items">' . wc_stc_render_shipment_action_buttons( $action['actions'] ) . '</span></div>';
		} elseif ( isset( $action['action'], $action['url'], $action['name'] ) ) {
			$classes = 'button wc-stc-shipment-action-button tip wc-stc-shipment-action-button-' . $action['action'] . ' ' . $action['action'] . ' ' . $action['classes'];

			if ( empty( $action['title'] ) ) {
				$action['title'] = $action['name'];
			}

			$custom_attributes = '';

			foreach ( $action['custom_attributes'] as $attribute => $val ) {
				$custom_attributes .= ' ' . esc_attr( $attribute ) . '="' . esc_attr( $val ) . '"';
			}

			$actions_html .= sprintf( '<a class="%1$s" href="%2$s" aria-label="%3$s" title="%3$s" target="%4$s" %5$s>%6$s</a>', esc_attr( $classes ), esc_url( $action['url'] ), esc_attr( $action['title'] ), esc_attr( $action['target'] ), $custom_attributes, esc_html( $action['name'] ) );
		}
	}

	return $actions_html;
}

function wc_stc_get_shipment_status_name( $status ) {
	$status_name = '';
	$statuses    = wc_stc_get_shipment_statuses();

	if ( array_key_exists( $status, $statuses ) ) {
		$status_name = $statuses[ $status ];
	}

	/**
	 * Filter to adjust the shipment status name or title.
	 *
	 * @param string  $status_name The status name or title.
	 * @param integer $status The status slug.
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_shipment_status_name', $status_name, $status );
}

function wc_stc_get_shipment_sent_statuses() {
	/**
	 * Filter to adjust which Shipment statuses should be considered as sent.
	 *
	 * @param array $statuses An array of statuses considered as shipped,
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters(
		'woocommerce_shiptastic_shipment_sent_statuses',
		array(
			'shipped',
			'delivered',
		)
	);
}

function wc_stc_get_shipment_counts( $type = '' ) {
	$counts = array();

	foreach ( array_keys( wc_stc_get_shipment_statuses() ) as $status ) {
		$counts[ $status ] = wc_stc_get_shipment_count( $status, $type );
	}

	return $counts;
}

function wc_stc_get_shipment_count( $status, $type = '' ) {
	$count             = 0;
	$shipment_statuses = array_keys( wc_stc_get_shipment_statuses() );

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
 * @param  string $maybe_status Status, including any wc-stc- prefix.
 * @return bool
 */
function wc_stc_is_shipment_status( $maybe_status ) {
	$shipment_statuses = wc_stc_get_shipment_statuses();

	return isset( $shipment_statuses[ $maybe_status ] );
}

/**
 * Main function for returning shipment items.
 *
 *
 * @param mixed $the_item Object or shipment item id.
 * @param string $item_type The shipment item type.
 *
 * @return bool|ShipmentItem
 */
function wc_stc_get_shipment_item( $the_item = false, $item_type = 'simple' ) {
	$item_id = wc_stc_get_shipment_item_id( $the_item );

	if ( ! $item_id ) {
		return false;
	}

	$item_class = 'Vendidero\Shiptastic\ShipmentItem';

	if ( 'return' === $item_type ) {
		$item_class = 'Vendidero\Shiptastic\ShipmentReturnItem';
	}

	/**
	 * Filter to adjust the classname used to construct a ShipmentItem.
	 *
	 * @param string  $classname The classname to be used.
	 * @param integer $item_id The shipment item id.
	 * @param string  $item_type The shipment item type.
	 *
	 * @package Vendidero/Shiptastic
	 */
	$classname = apply_filters( 'woocommerce_shiptastic_shipment_item_class', $item_class, $item_id, $item_type );

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
 * @param  mixed $item Item data to convert to an ID.
 * @return int|bool false on failure
 */
function wc_stc_get_shipment_item_id( $item ) {
	if ( is_numeric( $item ) ) {
		return $item;
	} elseif ( $item instanceof Vendidero\Shiptastic\ShipmentItem ) {
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
 * @param  array $dimensions Array of dimensions.
 * @return string
 */
function wc_stc_format_shipment_dimensions( $dimensions, $unit = '' ) {
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
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_format_shipment_dimensions', $dimension_string, $dimensions, $unit );
}

/**
 * Format a weight for display.
 *
 * @param  float $weight Weight.
 * @return string
 */
function wc_stc_format_shipment_weight( $weight, $unit = '' ) {
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
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_format_shipment_weight', $weight_string, $weight, $unit );
}

/**
 * Get My Account > Shipments columns.
 *
 * @return array
 */
function wc_stc_get_account_shipments_columns( $type = 'simple' ) {
	/**
	 * Filter to adjust columns being used to display shipments in a table view on the customer
	 * account page.
	 *
	 * @param string[] $columns The columns in key => value pairs.
	 * @param string   $type    The shipment type e.g. simple or return.
	 *
	 * @package Vendidero/Shiptastic
	 */
	$columns = apply_filters(
		'woocommerce_stc_account_shipments_columns',
		array(
			'shipment-number'   => _x( 'Shipment', 'shipments', 'woocommerce-germanized' ),
			'shipment-date'     => _x( 'Date', 'shipments', 'woocommerce-germanized' ),
			'shipment-status'   => _x( 'Status', 'shipments', 'woocommerce-germanized' ),
			'shipment-tracking' => _x( 'Tracking', 'shipments', 'woocommerce-germanized' ),
			'shipment-actions'  => _x( 'Actions', 'shipments', 'woocommerce-germanized' ),
		),
		$type
	);

	if ( ! is_user_logged_in() ) {
		$columns = array_diff_key( $columns, array( 'shipment-actions' => '' ) );
	}

	return $columns;
}

function wc_stc_get_order_customer_add_return_url( $order ) {
	if ( ! $shipment_order = wc_stc_get_shipment_order( $order ) ) {
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
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_add_return_shipment_url', $url, $shipment_order->get_order() );
}

/**
 * @param WC_Order $order
 *
 * @return mixed
 */
function wc_stc_order_is_customer_returnable( $order, $check_date = true ) {
	$is_returnable = false;

	if ( ! $shipment_order = wc_stc_get_shipment_order( $order ) ) {
		return false;
	}

	if ( $provider = wc_stc_get_order_shipping_provider( $order ) ) {
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

			if ( $shipment_order->get_date_delivered() ) {
				$completed_date = $shipment_order->get_date_delivered();
			} elseif ( $shipment_order->get_date_shipped() ) {
				$completed_date = $shipment_order->get_date_shipped();
			} elseif ( $shipment_order->get_order()->get_date_completed() ) {
				$completed_date = $shipment_order->get_order()->get_date_completed();
			}

			/**
			 * Filter to adjust the completed date of an order used to determine whether an order is
			 * still returnable by the customer or not. The date is constructed by checking for existence in the following order:
			 *
			 * 1. The date the order was delivered completely
			 * 2. The date the order was shipped completely
			 * 3. The date the order was marked as completed
			 * 4. The date the order was created
			 *
			 * @param WC_DateTime $completed_date The order completed date.
			 * @param WC_Order    $order The order instance.
			 *
			 * @package Vendidero/Shiptastic
			 */
			$completed_date = apply_filters( 'woocommerce_shiptastic_order_return_completed_date', $completed_date, $shipment_order->get_order() );

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
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_order_is_returnable_by_customer', $is_returnable, $shipment_order->get_order(), $check_date );
}

/**
 * @param $order
 *
 * @return bool|\Vendidero\Shiptastic\Interfaces\ShippingProvider
 */
function wc_stc_get_order_shipping_provider( $order, $method_id = '' ) {
	$shipment_order = false;

	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	} elseif ( is_a( $order, '\Vendidero\Shiptastic\Order' ) ) {
		$shipment_order = $order;
		$order          = $shipment_order->get_order();
	}

	if ( ! is_a( $order, 'WC_Order' ) ) {
		return false;
	}

	if ( ! $shipment_order ) {
		$shipment_order = wc_stc_get_shipment_order( $order );
	}

	$provider        = false;
	$shipping_method = $shipment_order->get_shipping_method_by_id( $method_id );

	if ( is_a( $shipping_method, 'WC_Order_Item_Shipping' ) ) {
		$provider_name = $shipping_method->get_meta( '_shipping_provider' );

		if ( ! empty( $provider_name ) && ( $provider_instance = wc_stc_get_shipping_provider( $provider_name ) ) ) {
			$provider = $provider_instance;
		}
	}

	/**
	 * Allow the actual shipping provider to be overridden in case shipments have
	 * already been created (manually).
	 */
	foreach ( array_reverse( $shipment_order->get_shipments() ) as $shipment ) {
		if ( ! empty( $method_id ) && $shipment->get_shipping_method() !== $method_id ) {
			continue;
		}

		if ( $shipment->get_shipping_provider_instance() ) {
			$provider = $shipment->get_shipping_provider_instance();
			break;
		}
	}

	if ( ! $provider ) {
		if ( $method = $shipment_order->get_shipping_method( $method_id ) ) {
			$provider = $method->get_shipping_provider_instance();
		}
	}

	/**
	 * Filters the shipping provider detected for a specific order.
	 *
	 * @param bool|\Vendidero\Shiptastic\Interfaces\ShippingProvider $provider The shipping provider instance.
	 * @param WC_Order              $order The order instance.
	 * @param string                $method_id The order item shipping id, if available
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_get_order_shipping_provider', $provider, $order, $method_id );
}

function wc_stc_get_customer_order_return_request_key() {
	$key = ( isset( $_REQUEST['key'] ) ? wc_clean( wp_unslash( $_REQUEST['key'] ) ) : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	return $key;
}

function wc_shiptastic_additional_costs_include_tax() {
	return apply_filters( 'woocommerce_shiptastic_additional_costs_include_tax', false );
}

function wc_stc_customer_can_add_return_shipment( $order_id ) {
	$can_view_shipments = false;

	if ( isset( $_REQUEST['key'] ) && ! empty( $_REQUEST['key'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = wc_stc_get_customer_order_return_request_key();

		if ( ( $order_shipment = wc_stc_get_shipment_order( $order_id ) ) && ! empty( $key ) ) {
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
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_customer_can_view_shipments', $can_view_shipments, $order_id );
}

/**
 * @param WC_Order|integer $order
 */
function wc_stc_customer_return_needs_manual_confirmation( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	if ( ! $order ) {
		return true;
	}

	$needs_manual_confirmation = true;

	if ( $provider = wc_stc_get_order_shipping_provider( $order ) ) {
		$needs_manual_confirmation = $provider->needs_manual_confirmation_for_returns();
	}

	/**
	 * Filter to decide whether a customer added return of a certain order
	 * needs manual confirmation by the shop manager or not.
	 *
	 * @param bool     $needs_manual_confirmation Whether needs manual confirmation or not.
	 * @param WC_Order $order The order instance for which the return shall be created.
	 *
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_customer_return_needs_manual_confirmation', $needs_manual_confirmation, $order );
}

/**
 * Get account shipments actions.
 *
 * @param  int|Shipment $shipment Shipment instance or ID.
 * @return array
 */
function wc_stc_get_account_shipments_actions( $shipment ) {
	if ( ! is_object( $shipment ) ) {
		$shipment_id = absint( $shipment );
		$shipment    = wc_stc_get_shipment( $shipment_id );
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
	 * @package Vendidero/Shiptastic
	 */
	return apply_filters( 'woocommerce_shiptastic_account_shipments_actions', $actions, $shipment );
}

function wc_shiptastic_get_product( $the_product ) {
	try {
		if ( is_a( $the_product, '\Vendidero\Shiptastic\Product' ) ) {
			return $the_product;
		}

		return new \Vendidero\Shiptastic\Product( $the_product );
	} catch ( \Exception $e ) {
		return false;
	}
}

function wc_stc_get_volume_dimension( $dimension, $to_unit, $from_unit = '' ) {
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

if ( ! function_exists( 'wc_stc_wp_theme_get_element_class_name' ) ) {
	/**
	 * Given an element name, returns a class name.
	 *
	 * If the WP-related function is not defined, return empty string.
	 *
	 * @param string $element The name of the element.
	 *
	 * @return string
	 */
	function wc_stc_wp_theme_get_element_class_name( $element ) {
		if ( function_exists( 'wc_wp_theme_get_element_class_name' ) ) {
			return wc_wp_theme_get_element_class_name( $element );
		} elseif ( function_exists( 'wp_theme_get_element_class_name' ) ) {
			return wp_theme_get_element_class_name( $element );
		}

		return '';
	}
}

function wc_shiptastic_allow_deferred_sync( $type = 'shipments' ) {
	$allow_defer = true;

	if ( 'shipments' === $type || 'label' === $type || 'return_label' === $type ) {
		if ( is_admin() && current_user_can( 'manage_woocommerce' ) ) {
			$allow_defer = false;
		}
	}

	if ( apply_filters( 'woocommerce_shiptastic_disable_deferred_sync', false ) ) {
		$allow_defer = false;
	}

	return apply_filters( "woocommerce_shiptastic_allow_{$type}_deferred_sync", $allow_defer );
}

/**
 * Forces a WP_Error object to be converted to a ShipmentError.
 *
 * @param $error
 *
 * @return mixed|\Vendidero\Shiptastic\ShipmentError
 */
function wc_stc_get_shipment_error( $error ) {
	if ( ! is_wp_error( $error ) ) {
		return $error;
	} elseif ( is_a( $error, 'Vendidero\Shiptastic\ShipmentError' ) ) {
		return $error;
	} else {
		return \Vendidero\Shiptastic\ShipmentError::from_wp_error( $error );
	}
}

function wc_shiptastic_substring( $str, $start, $length = null ) {
	if ( is_array( $str ) ) {
		return array_map( 'wc_shiptastic_substring', $str );
	} elseif ( is_scalar( $str ) ) {
		if ( function_exists( 'mb_substr' ) ) {
			$str = mb_substr( $str, $start, $length );
		} else {
			$str = substr( $str, $start, $length );
		}

		return $str;
	} else {
		return $str;
	}
}

/**
 *
 * Remove any special char except dash and whitespace.
 *
 * @param string|array $str
 *
 * @return string|array
 */
function wc_shiptastic_get_alphanumeric_string( $str ) {
	if ( is_array( $str ) ) {
		return array_map( 'wc_shiptastic_get_alphanumeric_string', $str );
	} elseif ( is_scalar( $str ) ) {
		$str = wc_shiptastic_decode_html( $str );
		$str = remove_accents( $str );
		$str = preg_replace( '/[^ \w-]/', ' ', $str );
		$str = preg_replace( '/\s+/', ' ', $str );

		return wc_clean( $str );
	} else {
		return $str;
	}
}

/**
 * Convert html entities, e.g. &amp; to utf-8.
 * Do not call html_entity_decode on bool as that would transform true => 1.
 *
 * @param string|array $str
 *
 * @return string|array
 */
function wc_shiptastic_decode_html( $str ) {
	if ( is_array( $str ) ) {
		return array_map( 'wc_shiptastic_decode_html', $str );
	} elseif ( is_scalar( $str ) && ! is_bool( $str ) ) {
		return html_entity_decode( $str, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	} else {
		return $str;
	}
}
