<?php
/**
 * WooCommerce Germanized DHL Shipment Functions
 *
 * Functions for shipment specific things.
 *
 * @package WooCommerce_Germanized/DHL/Functions
 * @version 3.4.0
 */

use Vendidero\Germanized\DHL\Label;
use Vendidero\Germanized\DHL\LabelQuery;
use Vendidero\Germanized\DHL\Order;
use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\ParcelLocator;
use Vendidero\Germanized\DHL\ShippingProviderMethodDHL;
use Vendidero\Germanized\DHL\ParcelServices;
use Vendidero\Germanized\DHL\LabelFactory;
use Vendidero\Germanized\DHL\SimpleLabel;
use Vendidero\Germanized\DHL\ReturnLabel;
use Vendidero\Germanized\DHL\Product;

use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\SimpleShipment;
use Vendidero\Germanized\Shipments\ReturnShipment;
use Vendidero\Germanized\Shipments\ShipmentFactory;

defined( 'ABSPATH' ) || exit;

function wc_gzd_dhl_format_preferred_api_time( $time ) {
	return str_replace( array( ':', '-' ), '', $time );
}

function wc_gzd_dhl_get_preferred_times_select_options( $times ) {
	$preferred_times = array( 0 => _x( 'None', 'dhl time context', 'woocommerce-germanized' ) );

	if ( ! empty( $times ) ) {
		$preferred_times = $times;
	}

	return $preferred_times;
}

function wc_gzd_dhl_get_preferred_days_select_options( $days, $current = '' ) {
	$preferred_days = array( 0 => _x( 'None', 'dhl day context', 'woocommerce-germanized' ) );

	if ( ! empty( $days ) ) {
		$days = array_keys( $days );

		foreach( $days as $day ) {

			if ( empty( $day ) ) {
				continue;
			}

			$formatted_day  = date_i18n( wc_date_format(), strtotime( $day ) );
			$preferred_days = array_merge( $preferred_days, array( $day => $formatted_day ) );
		}
	}

	if ( ! empty( $current ) ) {
		$preferred_days[ $current ] = date_i18n( wc_date_format(), strtotime( $current ) );
	}

	return $preferred_days;
}

function wc_gzd_dhl_get_duties() {
	$duties = array(
		'DDU' => _x( 'Delivery Duty Unpaid', 'dhl', 'woocommerce-germanized' ),
		'DDP' => _x( 'Delivery Duty Paid', 'dhl', 'woocommerce-germanized' ),
		'DXV' => _x( 'Delivery Duty Paid (excl. VAT )', 'dhl', 'woocommerce-germanized' ),
		'DDX' => _x( 'Delivery Duty Paid (excl. Duties, taxes and VAT)', 'dhl', 'woocommerce-germanized' )
	);

	return $duties;
}

function wc_gzd_dhl_is_valid_visual_min_age( $min_age ) {
	$ages = wc_gzd_dhl_get_visual_min_ages();

	if ( empty( $min_age ) || ( ! array_key_exists( $min_age, $ages ) && ! in_array( $min_age, $ages ) ) ) {
		return false;
	}

	return true;
}

function wc_gzd_dhl_get_visual_min_ages() {
	$visual_age = array(
		'0'   => _x( 'None', 'age context', 'woocommerce-germanized' ),
		'A16' => _x( 'Minimum age of 16', 'dhl', 'woocommerce-germanized' ),
		'A18' => _x( 'Minimum age of 18', 'dhl', 'woocommerce-germanized' )
	);

	return $visual_age;
}

function wc_gzd_dhl_get_ident_min_ages() {
	return wc_gzd_dhl_get_visual_min_ages();
}

function wc_gzd_dhl_get_label_reference( $reference_text, $placeholders = array() ) {
	return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $reference_text );
}

function wc_gzd_dhl_get_label_customer_reference( $label, $shipment ) {
	/**
	 * Filter to adjust the customer reference field placed on the DHL label. Maximum characeter length: 35.
	 *
	 * @param string         $text The customer reference text.
	 * @param Label          $label The label instance.
	 * @param SimpleShipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/DHL
	 */
	$ref = apply_filters( 'woocommerce_gzd_dhl_label_customer_reference', wc_gzd_dhl_get_label_reference( _x( 'Shipment #{shipment_id} to order {order_id}', 'dhl', 'woocommerce-germanized' ), array( '{shipment_id}' => $shipment->get_id(), '{order_id}' => $shipment->get_order_number() ) ), $label, $shipment );

	return sanitize_text_field( substr( $ref, 0, 35 ) );
}

function wc_gzd_dhl_get_return_label_customer_reference( $label, $shipment ) {
	/**
	 * Filter to adjust the customer reference field placed on the DHL return label. Maximum characeter length: 30.
	 *
	 * @param string         $text The customer reference text.
	 * @param Label          $label The label instance.
	 * @param ReturnShipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/DHL
	 */
	$ref = apply_filters( 'woocommerce_gzd_dhl_return_label_customer_reference', wc_gzd_dhl_get_label_reference( _x( 'Return #{shipment_id} to order {order_id}', 'dhl', 'woocommerce-germanized' ), array( '{shipment_id}' => $shipment->get_id(), '{order_id}' => $shipment->get_order_number() ) ), $label, $shipment );

	return sanitize_text_field( substr( $ref, 0, 30 ) );
}

function wc_gzd_dhl_get_inlay_return_label_reference( $label, $shipment ) {
	/**
	 * Filter to adjust the inlay return reference field placed on the DHL label. Maximum characeter length: 35.
	 *
	 * @param string         $text The customer reference text.
	 * @param Label          $label The label instance.
	 * @param SimpleShipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/DHL
	 */
	$ref = apply_filters( 'woocommerce_gzd_dhl_inlay_return_label_reference', wc_gzd_dhl_get_label_reference( _x( 'Return shipment #{shipment_id} to order #{order_id}', 'dhl', 'woocommerce-germanized' ), array( '{shipment_id}' => $shipment->get_id(), '{order_id}' => $shipment->get_order_number() ) ), $label, $shipment );

	return sanitize_text_field( substr( $ref, 0, 35 ) );
}

/**
 * Standard way of retrieving shipments based on certain parameters.
 *
 * @since  2.6.0
 * @param  array $args Array of args (above).
 * @return Label[]|stdClass Number of pages and an array of order objects if
 *                             paginate is true, or just an array of values.
 */
function wc_gzd_dhl_get_labels( $args ) {
    $query = new LabelQuery( $args );
    return $query->get_labels();
}

function wc_gzd_dhl_get_current_shipping_method() {
	$chosen_shipping_methods = WC()->session ? WC()->session->get( 'chosen_shipping_methods' ) : array();

	if ( ! empty( $chosen_shipping_methods ) ) {
		$method = wc_gzd_dhl_get_shipping_method( $chosen_shipping_methods[0] );

		return $method;
	}

	return false;
}

function wc_gzd_dhl_get_international_services() {
	return array(
		'Premium',
		'GoGreen'
	);
}

function wc_gzd_dhl_get_services() {
    return array(
        'PreferredTime',
        'PreferredLocation',
        'PreferredNeighbour',
        'PreferredDay',
	    'VisualCheckOfAge',
        'Personally',
        'NoNeighbourDelivery',
        'NamedPersonOnly',
        'Premium',
        'AdditionalInsurance',
        'BulkyGoods',
        'IdentCheck',
        'CashOnDelivery',
	    'ParcelOutletRouting',
	    'GoGreen'
    );
}

function wc_gzd_dhl_get_shipping_method( $instance_id ) {
	$method = wc_gzd_get_shipping_provider_method( $instance_id );

	return new ShippingProviderMethodDHL( $method );
}

function wc_gzd_dhl_get_preferred_services() {
	return array(
		'PreferredTime',
		'PreferredLocation',
		'PreferredNeighbour',
		'PreferredDay',
	);
}

function wc_gzd_dhl_get_pickup_types() {
    return array(
    	'packstation' => _x( 'Packstation', 'dhl', 'woocommerce-germanized' ),
	    'postoffice'  => _x( 'Postfiliale', 'dhl', 'woocommerce-germanized' ),
	    'parcelshop'  => _x( 'Postfiliale', 'dhl', 'woocommerce-germanized' )
    );
}

function wc_gzd_dhl_is_pickup_type( $maybe_type, $type = 'packstation' ) {
	$label = wc_gzd_dhl_get_pickup_type( $type );

	if ( ! $label ) {
		return false;
	}

	$label      = strtolower( trim( $label ) );
	$maybe_type = strtolower( trim( $maybe_type ) );

	if ( strpos( $maybe_type, $label ) !== false ) {
		return true;
	}

	return false;
}

function wc_gzd_dhl_get_excluded_working_days() {
	$work_days = array(
		'mon',
		'tue',
		'wed',
		'thu',
		'fri',
		'sat'
	);

	$excluded = array();

	foreach ( $work_days as $value ) {
		if ( ParcelServices::is_preferred_day_excluded( $value ) ) {
			$excluded[] = $value;
		}
	}

	return $excluded;
}

function wc_gzd_dhl_order_has_pickup( $order ) {
	return ParcelLocator::order_has_pickup( $order );
}

function wc_gzd_dhl_get_pickup_type( $type ) {
	$types = wc_gzd_dhl_get_pickup_types();

	if ( array_key_exists( $type, $types ) ) {
		return $types[ $type ];
	} elseif( in_array( $type, $types ) ) {
		return $type;
	} else {
		return false;
	}
}

/**
 * @param WP_Error $error
 *
 * @return bool
 */
function wc_gzd_dhl_wp_error_has_errors( $error ) {
	if ( is_callable( array( $error, 'has_errors' ) ) ) {
		return $error->has_errors();
	} else {
		$errors = $error->errors;

		return ( ! empty( $errors ) ? true : false );
	}
}

function wc_gzd_dhl_validate_return_label_args( $shipment, $args = array() ) {

	$args = wp_parse_args( $args, array(
		'receiver_slug' => '',
	) );

	$error = new WP_Error();

	$args['receiver_slug'] = sanitize_key( $args['receiver_slug'] );

	if ( empty( $args['receiver_slug'] ) ) {
		$error->add( 500, _x( 'Receiver is missing or does not exist.', 'dhl', 'woocommerce-germanized' ) );
	}

	if ( wc_gzd_dhl_wp_error_has_errors( $error ) ) {
		return $error;
	}

	return $args;
}

function wc_gzd_dhl_validate_label_args( $shipment, $args = array() ) {

	$args = wp_parse_args( $args, array(
		'preferred_day'         => '',
		'preferred_time_start'  => '',
		'preferred_time_end'    => '',
		'preferred_location'    => '',
		'preferred_neighbor'    => '',
		'ident_date_of_birth'   => '',
		'ident_min_age'         => '',
		'visual_min_age'        => '',
		'email_notification'    => 'no',
		'has_inlay_return'      => 'no',
		'codeable_address_only' => 'no',
		'cod_total'             => 0,
		'duties'                => '',
		'services'              => array(),
		'return_address'        => array(),
	) );

	$error = new WP_Error();

	if ( ! $shipment_order = $shipment->get_order() ) {
		$error->add( 500, sprintf( _x( 'Shipment order #%s does not exist', 'dhl', 'woocommerce-germanized' ), $shipment->get_order_id() ) );
	}

	$dhl_order = wc_gzd_dhl_get_order( $shipment_order );

	// Do only allow valid services
	if ( ! empty( $args['services'] ) ) {
		$args['services'] = array_intersect( $args['services'], wc_gzd_dhl_get_services() );
	}

	// Check if return address has empty mandatory fields
	if ( 'yes' === $args['has_inlay_return'] ) {
		$args['return_address'] = wp_parse_args( $args['return_address'], array(
			'name'          => '',
			'company'       => '',
			'street'        => '',
			'street_number' => '',
			'postcode'      => '',
			'city'          => '',
			'state'         => '',
			'country'       => Package::get_setting( 'return_address_country' ),
		) );

		$mandatory = array(
			'street'     => _x( 'Street', 'dhl', 'woocommerce-germanized' ),
			'postcode'   => _x( 'Postcode', 'dhl', 'woocommerce-germanized' ),
			'city'       => _x( 'City', 'dhl', 'woocommerce-germanized' ),
		);

		foreach( $mandatory as $mand => $title ) {
			if ( empty( $args['return_address'][ $mand ] ) ) {
				$error->add( 500, sprintf( _x( '%s of the return address is a mandatory field.', 'dhl', 'woocommerce-germanized' ), $title ) );
			}
		}

		if ( empty( $args['return_address']['name'] ) && empty( $args['return_address']['company'] ) ) {
			$error->add( 500, _x( 'Please either add a return company or name.', 'dhl', 'woocommerce-germanized' ) );
		}
	} else {
		$args['return_address'] = array();
	}

	// No cash on delivery available
	if ( ! empty( $args['cod_total'] ) && ! $dhl_order->has_cod_payment() ) {
		$args['cod_total'] = 0;
	}

	if ( ! empty( $args['cod_total'] ) && $dhl_order->has_cod_payment() ) {
		$args['services'] = array_merge( $args['services'], array( 'CashOnDelivery' ) );
	}

	if ( ! empty( $args['preferred_day'] ) && wc_gzd_dhl_is_valid_datetime( $args['preferred_day'], 'Y-m-d' ) ) {
		$args['services'] = array_merge( $args['services'], array( 'PreferredDay' ) );
	} else {
		if ( ! empty( $args['preferred_day'] ) && ! wc_gzd_dhl_is_valid_datetime( $args['preferred_day'], 'Y-m-d' ) ) {
			$error->add( 500, _x( 'Error while parsing preferred day.', 'dhl', 'woocommerce-germanized' ) );
		}

		$args['services']      = array_diff( $args['services'], array( 'PreferredDay' ) );
		$args['preferred_day'] = '';
	}

	if ( ( ! empty( $args['preferred_time_start'] ) && wc_gzd_dhl_is_valid_datetime( $args['preferred_time_start'], 'H:i' ) ) && ( ! empty( $args['preferred_time_end'] ) && wc_gzd_dhl_is_valid_datetime( $args['preferred_time_end'], 'H:i' ) ) ) {
		$args['services'] = array_merge( $args['services'], array( 'PreferredTime' ) );
	} else {
		if ( ( ! empty( $args['preferred_time_start'] ) && ! wc_gzd_dhl_is_valid_datetime( $args['preferred_time_start'], 'H:i' ) ) || ( ! empty( $args['preferred_time_end'] ) && ! wc_gzd_dhl_is_valid_datetime( $args['preferred_time_end'], 'H:i' ) ) ) {
			$error->add( 500, _x( 'Error while parsing preferred time.', 'dhl', 'woocommerce-germanized' ) );
		}

		$args['services']             = array_diff( $args['services'], array( 'PreferredTime' ) );
		$args['preferred_time_start'] = '';
		$args['preferred_time_end']   = '';
 	}

	if ( ! empty( $args['preferred_location'] ) ) {
		$args['services'] = array_merge( $args['services'], array( 'PreferredLocation' ) );
	} else {
		$args['services'] = array_diff( $args['services'], array( 'PreferredLocation' ) );
	}

	if ( ! empty( $args['preferred_neighbor'] ) ) {
		$args['services'] = array_merge( $args['services'], array( 'PreferredNeighbour' ) );
	} else {
		$args['services'] = array_diff( $args['services'], array( 'PreferredNeighbour' ) );
	}

	if ( ! empty( $args['visual_min_age'] ) && wc_gzd_dhl_is_valid_visual_min_age( $args['visual_min_age'] ) ) {
		$args['services']       = array_merge( $args['services'], array( 'VisualCheckOfAge' ) );
	} else {
		if ( ! empty( $args['visual_min_age'] ) && ! wc_gzd_dhl_is_valid_visual_min_age( $args['visual_min_age'] ) ) {
			$error->add( 500, _x( 'The visual min age check is invalid.', 'dhl', 'woocommerce-germanized' ) );
		}

		$args['services']       = array_diff( $args['services'], array( 'VisualCheckOfAge' ) );
		$args['visual_min_age'] = '';
	}

	// In case order does not support email notification - remove parcel outlet routing
	if ( in_array( 'ParcelOutletRouting', $args['services'] ) ) {
		if ( ! $dhl_order->supports_email_notification() ) {
			$args['services'] = array_diff( $args['services'], array( 'ParcelOutletRouting' ) );
		}
	}

	if ( in_array( 'IdentCheck', $args['services'] ) ) {
		if ( ! empty( $args['ident_min_age'] ) && ! array_key_exists( $args['ident_min_age'], wc_gzd_dhl_get_ident_min_ages() ) ) {
			$error->add( 500, _x( 'The ident min age check is invalid.', 'dhl', 'woocommerce-germanized' ) );

			$args['ident_min_age'] = '';
		}

		if ( ! empty( $args['ident_date_of_birth'] ) ) {
			if ( ! wc_gzd_dhl_is_valid_datetime( $args['ident_date_of_birth'], 'Y-m-d' ) ) {
				$error->add( 500, _x( 'There was an error parsing the date of birth for the identity check.', 'dhl', 'woocommerce-germanized' ) );
			}
		}

		if ( empty( $args['ident_date_of_birth'] ) && empty( $args['ident_min_age'] ) ) {
			$error->add( 500, _x( 'Either a minimum age or a date of birth must be added to the ident check.', 'dhl', 'woocommerce-germanized' ) );
		}
	} else {
		$args['ident_min_age']       = '';
		$args['ident_date_of_birth'] = '';
	}

	// We don't need duties for non-crossborder shipments
	if ( ! empty( $args['duties'] ) && ! Package::is_crossborder_shipment( $shipment->get_country() ) ) {
		unset( $args['duties'] );
	}

	if ( ! empty( $args['duties'] ) && ! array_key_exists( $args['duties'], wc_gzd_dhl_get_duties() ) ) {
		$error->add( 500, sprintf( _x( '%s duties element does not exist.', 'dhl', 'woocommerce-germanized' ), $args['duties'] ) );
	}

	if ( wc_gzd_dhl_wp_error_has_errors( $error ) ) {
		return $error;
	}

	return $args;
}

function wc_gzd_dhl_is_valid_datetime( $maybe_datetime, $format = 'Y-m-d' ) {
	if ( ! is_a( $maybe_datetime, 'DateTime' && ! is_numeric( $maybe_datetime ) ) ) {
		if ( ! DateTime::createFromFormat( $format, $maybe_datetime ) ) {
			return false;
		}
	}

	return true;
}

function wc_gzd_dhl_format_label_state( $state, $country ) {
	// If not USA or Australia, then change state from ISO code to name
	if ( ! in_array( $country, array( 'US', 'AU' ) ) ) {

		// Get all states for a country
		$states = WC()->countries->get_states( $country );

		// If the state is empty, it was entered as free text
		if ( ! empty( $states ) && ! empty( $state ) ) {
			// Change the state to be the name and not the code
			$state = $states[ $state ];

			// Remove anything in parentheses (e.g. TH)
			$ind = strpos( $state, " (" );

			if ( false !== $ind ) {
				$state = substr( $state, 0, $ind );
			}
		}
	}

	return $state;
}

function wc_gzd_dhl_get_product( $the_product ) {
	if ( ! is_a( $the_product, '\Vendidero\Germanized\DHL\Product' ) ) {
		$product = new Product( $the_product );
	} else {
		$product = $the_product;
	}

	return $product;
}

/**
 * @param Shipment $shipment
 */
function wc_gzd_dhl_shipment_needs_label( $shipment, $check_status = true ) {
	$needs_label = true;

	if ( is_numeric( $shipment ) ) {
		$shipment = wc_gzd_get_shipment( $shipment );
	}

	if ( $shipment && 'dhl' !== $shipment->get_shipping_provider() ) {
		$needs_label = false;
	}

	// In case it is a return shipment - make sure that retoures are enabled
	if ( ! $shipment->supports_label() ) {
		$needs_label = false;
	}

	// If label already exists
	if ( $label = $shipment->get_label() ) {
		$needs_label = false;
	}

	// If shipment is already delivered
	if ( $check_status && $shipment->has_status( array( 'delivered', 'shipped', 'returned' ) ) ) {
		$needs_label = false;
	}

	/**
	 * Filter to determine whether a shipment needs a DHL label or not.
	 *
	 * @param boolean  $needs_label Whether the shipment needs a DHL label or not.
	 * @param Shipment $shipment The shipment object.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/DHL
	 */
	return apply_filters( 'woocommerce_gzd_dhl_shipment_needs_label', $needs_label, $shipment );
}

/**
 * @param SimpleLabel $parent_label
 */
function wc_gzd_dhl_get_inlay_return_label_default_args( $parent_label ) {
	$dhl_shipping_method = false;
	$defaults            = array(
		'shipment_id' => $parent_label->get_shipment_id(),
	);

	if ( $shipment = $parent_label->get_shipment() ) {
		$shipping_method     = $shipment->get_shipping_method();
		$dhl_shipping_method = wc_gzd_dhl_get_shipping_method( $shipping_method );

		$defaults['sender_address'] = $shipment->get_address();
	}

	return $defaults;
}

function wc_gzd_dhl_validate_inlay_return_label_args( $parent_label, $args = array() ) {
	return $args;
}

/**
 * @param Shipment $shipment
 */
function wc_gzd_dhl_get_label_shipment_address_addition( $shipment ) {
	$addition        = $shipment->get_address_2();
	$street_addition = $shipment->get_address_street_addition();

	if ( ! empty( $street_addition ) ) {
		$addition = $street_addition . ( ! empty( $addition ) ? ' ' . $addition : '' );
	}

	return trim( $addition );
}

/**
 * @param Shipment $shipment
 *
 * @return mixed
 */
function wc_gzd_dhl_get_label_shipment_street_number( $shipment ) {
	$street_number = $shipment->get_address_street_number();

	if ( ! Package::is_shipping_domestic( $shipment->get_country() ) ) {

		if ( empty( $street_number ) ) {
			/**
			 * Filter to adjust the placeholder used as street number for the DHL API in case
			 * the shipment is not domestic (inner Germnany) and a street number was not provided.
			 *
			 * @param string $placeholder The placeholder to use - default 0 as advised by DHL support.
			 *
			 * @since 3.1.0
			 * @package Vendidero/Germanized/DHL
			 */
			$street_number = apply_filters( 'woocommerce_gzd_dhl_label_shipment_street_number_placeholder', '0' );
		}
	}

	return $street_number;
}

/**
 * @param ReturnLabel $label
 */
function wc_gzd_dhl_get_return_label_sender_street_number( $label ) {
	$street_number = $label->get_sender_street_number();

	if ( ! Package::is_shipping_domestic( $label->get_sender_country() ) ) {

		if ( empty( $street_number ) ) {
			/**
			 * This filter is documented in includes/wc-gzd-dhl-core-functions.php
			 */
			$street_number = apply_filters( 'woocommerce_gzd_dhl_label_shipment_street_number_placeholder', '0' );
		}
	}

	return $street_number;
}

/**
 * @param Order $dhl_order
 * @param Shipment $shipment
 */
function wc_gzd_dhl_get_label_default_args( $dhl_order, $shipment ) {

	$shipping_method     = $shipment->get_shipping_method();
	$dhl_shipping_method = wc_gzd_dhl_get_shipping_method( $shipping_method );
	$shipment_weight     = $shipment->get_weight();

	$defaults = array(
		'dhl_product'           => wc_gzd_dhl_get_default_product( $shipment->get_country(), $dhl_shipping_method ),
		'services'              => array(),
		'codeable_address_only' => Package::get_setting( 'label_address_codeable_only', $dhl_shipping_method ),
		'weight'                => wc_gzd_dhl_get_shipment_weight( $shipment ),
	);

	if ( $dhl_order->supports_email_notification() ) {
		$defaults['email_notification'] = 'yes';
	}

	if ( $dhl_order->has_cod_payment() ) {
		$defaults['cod_total'] = $shipment->get_total();

		/**
		 * This check is necessary to make sure only one label per order
		 * has the additional total (shipping total, fee total) added to the COD amount.
		 */
		$shipments              = wc_gzd_get_shipments_by_order( $shipment->get_order_id() );
		$needs_additional_total = true;

		foreach( $shipments as $shipment ) {
			if ( $existing_label = wc_gzd_dhl_get_shipment_label( $shipment, 'simple' ) ) {

				if ( $existing_label->cod_includes_additional_total() ) {
					$needs_additional_total = false;
					break;
				}
			}
		}

		if ( $needs_additional_total ) {
			$defaults['cod_total'] += round( $shipment->get_additional_total(), wc_get_price_decimals() );
			$defaults['cod_includes_additional_total'] = true;
		}
	}

	if ( Package::is_crossborder_shipment( $shipment->get_country() ) ) {

		$defaults['duties'] = Package::get_setting( 'label_default_duty', $dhl_shipping_method );

	} elseif ( Package::is_shipping_domestic( $shipment->get_country() ) ) {

		if ( Package::base_country_supports( 'services' ) ) {

			if ( $dhl_order->has_preferred_day() ) {
				$defaults['preferred_day'] = $dhl_order->get_preferred_day()->format( 'Y-m-d' );
			}

			if ( $dhl_order->has_preferred_time() ) {
				$defaults['preferred_time']       = $dhl_order->get_preferred_time();
				$defaults['preferred_time_start'] = $dhl_order->get_preferred_time_start()->format( 'H:i' );
				$defaults['preferred_time_end']   = $dhl_order->get_preferred_time_end()->format( 'H:i' );
			}

			if ( $dhl_order->has_preferred_location() ) {
				$defaults['preferred_location'] = $dhl_order->get_preferred_location();
			}

			if ( $dhl_order->has_preferred_neighbor() ) {
				$defaults['preferred_neighbor'] = $dhl_order->get_preferred_neighbor_formatted_address();
			}

			$visual_min_age = Package::get_setting( 'label_visual_min_age', $dhl_shipping_method );

			if ( wc_gzd_dhl_is_valid_visual_min_age( $visual_min_age ) ) {
				$defaults['services'][]     = 'VisualCheckOfAge';
				$defaults['visual_min_age'] = $visual_min_age;
			}

			if ( $dhl_order->needs_age_verification() && 'yes' === Package::get_setting( 'label_auto_age_check_sync', $dhl_shipping_method ) ) {
				$defaults['services'][]     = 'VisualCheckOfAge';
				$defaults['visual_min_age'] = $dhl_order->get_min_age();
			}

			foreach( wc_gzd_dhl_get_services() as $service ) {

				// Combination is not available
				if ( ! empty( $defaults['visual_min_age'] ) && 'NamedPersonOnly' === $service ) {
					continue;
				}

				if ( 'yes' === Package::get_setting( 'label_service_' . $service, $dhl_shipping_method ) ) {
					$defaults['services'][] = $service;
				}
			}

			// Demove duplicates
			$defaults['services'] = array_unique( $defaults['services'] );
		}

		if ( Package::base_country_supports( 'returns' ) ) {

			$defaults['return_address'] = array(
				'name'          => Package::get_setting( 'return_address_name' ),
				'company'       => Package::get_setting( 'return_address_company' ),
				'street'        => Package::get_setting( 'return_address_street' ),
				'street_number' => Package::get_setting( 'return_address_street_no' ),
				'postcode'      => Package::get_setting( 'return_address_postcode' ),
				'city'          => Package::get_setting( 'return_address_city' ),
				'phone'         => Package::get_setting( 'return_address_phone' ),
				'email'         => Package::get_setting( 'return_address_email' ),
			);

			if ( 'yes' === Package::get_setting( 'label_auto_inlay_return_label', $dhl_shipping_method ) ) {
				$defaults['has_inlay_return'] = 'yes';
			}
		}
	}

	if( ! Package::is_shipping_domestic( $shipment->get_country() ) ) {

		foreach( wc_gzd_dhl_get_international_services() as $service ) {
			if ( 'yes' === Package::get_setting( 'label_service_' . $service, $dhl_shipping_method ) ) {
				$defaults['services'][] = $service;
			}
		}

		// Demove duplicates
		$defaults['services'] = array_unique( $defaults['services'] );
	}

	return $defaults;
}

function wc_gzd_dhl_get_custom_label_format( $label, $type = '' ) {
	$available = array(
		'A4',
		'910-300-700',
		'910-300-700-oZ',
		'910-300-600',
		'910-300-610',
		'910-300-710',
	);

	/**
	 * This filter allows adjusting the default label format (GUI) to a custom format e.g. 910-300-700.
	 * The following formats are available:
	 *
	 * <ul>
	 * <li>A4</li>
	 * <li>910-300-700</li>
	 * <li>910-300-700-oZ</li>
	 * <li>910-300-600</li>
	 * <li>910-300-610</li>
	 * <li>910-300-710</li>
	 * </ul>
	 *
	 * @param string $format The label format.
	 * @param Label  $label The label instance.
	 * @param string $type The type e.g. inlay_return.
	 *
	 * @since 3.0.5
	 * @package Vendidero/Germanized/DHL
	 */
	$format = apply_filters( 'woocommerce_gzd_dhl_label_custom_format', '', $label, $type );

	if ( ! empty( $format ) && ! in_array( $format, $available ) ) {
		$format = '';
	}

	return $format;
}

/**
 * @param Shipment $shipment
 * @param string $unit
 *
 * @return float
 */
function wc_gzd_dhl_get_shipment_weight( $shipment, $unit = 'kg' ) {
	$shipping_method     = $shipment->get_shipping_method();
	$shipment_weight     = $shipment->get_weight();
	$dhl_shipping_method = wc_gzd_dhl_get_shipping_method( $shipping_method );
	$min_weight          = wc_get_weight( Package::get_setting( 'label_minimum_shipment_weight', $dhl_shipping_method ), $unit, 'kg' );
	$weight              = empty( $shipment_weight ) ? wc_get_weight( Package::get_setting( 'label_default_shipment_weight', $dhl_shipping_method ), $unit, 'kg' ) : wc_get_weight( $shipment_weight, $unit, $shipment->get_weight_unit() );

	if ( $weight < $min_weight ) {
		$weight = $min_weight;
	}

	return $weight;
}

/**
 * @param Order $dhl_order
 * @param ReturnShipment $shipment
 */
function wc_gzd_dhl_get_return_label_default_args( $dhl_order, $shipment ) {

	$shipping_method     = $shipment->get_shipping_method();
	$dhl_shipping_method = wc_gzd_dhl_get_shipping_method( $shipping_method );

	$defaults = array(
		'services'       => array(),
		'receiver_slug'  => wc_gzd_dhl_get_default_return_receiver_slug( $shipment->get_sender_country(), $dhl_shipping_method ),
		'weight'         => wc_gzd_dhl_get_shipment_weight( $shipment ),
		'sender_address' => $shipment->get_sender_address(),
	);

	$defaults['sender_address'] = array_merge( $defaults['sender_address'], array(
		'name'            => $shipment->get_formatted_sender_full_name(),
		'street'          => $shipment->get_sender_address_street(),
		'street_number'   => $shipment->get_sender_address_street_number(),
		'street_addition' => $shipment->get_sender_address_street_addition(),
	) );

	return $defaults;
}

/**
 * @param Shipment $shipment the shipment
 * @param array $args
 */
function wc_gzd_dhl_create_label( $shipment, $args = array() ) {
	try {
		if ( ! $shipment || ! is_a( $shipment, 'Vendidero\Germanized\Shipments\Shipment' ) ) {
			throw new Exception( _x( 'Invalid shipment', 'dhl', 'woocommerce-germanized' ) );
		}

		if ( ! $order = $shipment->get_order() ) {
			throw new Exception( _x( 'Order does not exist', 'dhl', 'woocommerce-germanized' ) );
		}

		$dhl_order     = wc_gzd_dhl_get_order( $order );
		$shipment_type = $shipment->get_type();
		$label_type    = 'return' === $shipment_type ? 'return' : 'simple';
		$hook_suffix   = 'simple' === $label_type ? '' : $label_type . '_';

		if ( 'return' === $label_type ) {
			$args = wp_parse_args( $args, wc_gzd_dhl_get_return_label_default_args( $dhl_order, $shipment ) );
			$args = wc_gzd_dhl_validate_return_label_args( $shipment, $args );
		} else {
			$args = wp_parse_args( $args, wc_gzd_dhl_get_label_default_args( $dhl_order, $shipment ) );
			$args = wc_gzd_dhl_validate_label_args( $shipment, $args );
		}

		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$label = LabelFactory::get_label( false, $label_type );

		if ( ! $label ) {
			throw new Exception( _x( 'Error while creating the label instance', 'dhl', 'woocommerce-germanized' ) );
		}

		$label->set_props( $args );
		$label->set_shipment( $shipment );

		/**
		 * Action fires before creating a DHL label.
		 *
		 * The dynamic portion of this hook, `$hook_prefix` refers to the label type e.g. return.
		 *
		 * Example hook name: woocommerce_gzd_dhl_before_create_return_label
		 *
		 * @param Label $label The label object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		do_action( "woocommerce_gzd_dhl_before_create_{$hook_suffix}label", $label );

		$label->save();

		/**
		 * Action fires after creating a DHL label.
		 *
		 * The dynamic portion of this hook, `$hook_prefix` refers to the label type e.g. return.
		 *
		 * Example hook name: woocommerce_gzd_dhl_after_create_return_label
		 *
		 * @param Label $label The label object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		do_action( "woocommerce_gzd_dhl_after_create_{$hook_suffix}label", $label );

	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}

	return $label;
}

function wc_gzd_dhl_update_label( $label, $args = array() ) {
	try {
		$shipment = $label->get_shipment();

		if ( ! $shipment || ! is_a( $shipment, 'Vendidero\Germanized\Shipments\Shipment' ) ) {
			throw new Exception( _x( 'Invalid shipment', 'dhl', 'woocommerce-germanized' ) );
		}

		if ( ! $order = $shipment->get_order() ) {
			throw new Exception( _x( 'Order does not exist', 'dhl', 'woocommerce-germanized' ) );
		}

		$dhl_order   = wc_gzd_dhl_get_order( $order );
		$label_type  = $label->get_type();
		$hook_suffix = 'simple' === $label_type ? '' : $label_type . '_';

		if ( 'return' === $label_type ) {
			$args = wp_parse_args( $args, wc_gzd_dhl_get_return_label_default_args( $dhl_order, $shipment ) );
			$args = wc_gzd_dhl_validate_return_label_args( $shipment, $args );
		} else {
			$args = wp_parse_args( $args, wc_gzd_dhl_get_label_default_args( $dhl_order, $shipment ) );
			$args = wc_gzd_dhl_validate_label_args( $shipment, $args );
		}

		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$label->set_props( $args );
		$label->set_shipment_id( $shipment->get_id() );

		/**
		 * Action fires before updating a DHL label.
		 *
		 * The dynamic portion of this hook, `$hook_prefix` refers to the label type e.g. return.
		 *
		 * Example hook name: woocommerce_gzd_dhl_before_update_return_label
		 *
		 * @param Label $label The label object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		do_action( "woocommerce_gzd_dhl_before_update_{$hook_suffix}label", $label );

		$label->save();

		/**
		 * Action fires after updating a DHL label.
		 *
		 * The dynamic portion of this hook, `$hook_prefix` refers to the label type e.g. return.
		 *
		 * Example hook name: woocommerce_gzd_dhl_after_update_return_label
		 *
		 * @param Label $label The label object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		do_action( "woocommerce_gzd_dhl_after_update_{$hook_suffix}label", $label );

	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}

	return $label;
}

/**
 * @param SimpleLabel $parent_label
 * @param array $args
 *
 * @return bool|ReturnLabel|WP_Error
 */
function wc_gzd_dhl_create_inlay_return_label( $parent_label, $args = array() ) {
	try {
		if ( ! $parent_label || ! is_a( $parent_label, 'Vendidero\Germanized\DHL\Label' ) ) {
			throw new Exception( _x( 'Invalid label', 'dhl', 'woocommerce-germanized' ) );
		}

		$args      = wp_parse_args( $args, wc_gzd_dhl_get_inlay_return_label_default_args( $parent_label ) );
		$args      = wc_gzd_dhl_validate_inlay_return_label_args( $parent_label, $args );

		if ( is_wp_error( $args ) ) {
			return $args;
		}

		$label = LabelFactory::get_label( false, 'return' );

		$label->set_props( $args );
		$label->set_parent_id( $parent_label->get_id() );

		/**
		 * Action fires before creating a DHL direct return label.
		 *
		 * @param ReturnLabel $label The label object.
		 * @param SimpleLabel $label The parent label object.
		 *
		 * @since 3.0.0
		 * @package Vendidero/Germanized/DHL
		 */
		do_action( 'woocommerce_gzd_dhl_before_create_inlay_return_label', $label, $parent_label );

		$label->save();

	} catch ( Exception $e ) {
		return new WP_Error( 'error', $e->getMessage() );
	}

	return $label;
}

function wc_gzd_dhl_get_shipping_method_slug( $method ) {
	if ( empty( $method ) ) {
		return $method;
	}

	// Assumes format 'name:id'
	$new_ship_method = explode(':', $method );
	$new_ship_method = isset( $new_ship_method[0] ) ? $new_ship_method[0] : $method;

	return $new_ship_method;
}

/**
 * Main function for returning label.
 *
 * @param  mixed $the_label Object or label id.
 *
 * @return bool|SimpleLabel|ReturnLabel
 *
 */
function wc_gzd_dhl_get_label( $the_label = false ) {
	return LabelFactory::get_label( $the_label );
}

function wc_gzd_dhl_get_order( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order);
	}

	if ( is_a( $order, 'WC_Order' ) ) {
		try {
			return new Vendidero\Germanized\DHL\Order( $order );
		} catch ( Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, func_get_args() );
			return false;
		}
	}

	return false;
}

function wc_gzd_dhl_get_inlay_return_products() {
	return array(
		'V01PAK',
		'V01PRIO',
		'V86PARCEL',
		'V55PAK'
	);
}

function wc_gzd_dhl_get_return_products_international() {

	$retoure =  array(
		'retoure_international_a' => _x( 'DHL Retoure International A', 'dhl', 'woocommerce-germanized' ),
		'retoure_international_b' => _x( 'DHL Retoure International B', 'dhl', 'woocommerce-germanized' ),
	);

	return $retoure;
}

function wc_gzd_dhl_get_return_products_domestic() {

	$retoure =  array(
		'retoure_online'  => _x( 'DHL Retoure Online', 'dhl', 'woocommerce-germanized' ),
	);

	return $retoure;
}

function wc_gzd_dhl_get_products_international() {

	$country = Package::get_base_country();

	$germany_int =  array(
		'V55PAK'  => _x( 'DHL Paket Connect', 'dhl', 'woocommerce-germanized' ),
		'V54EPAK' => _x( 'DHL Europaket (B2B)', 'dhl', 'woocommerce-germanized' ),
		'V53WPAK' => _x( 'DHL Paket International', 'dhl', 'woocommerce-germanized' ),
	);

	$dhl_prod_int = array();

	switch ( $country ) {
		case 'DE':
			$dhl_prod_int = $germany_int;
			break;
		default:
			break;
	}

	return $dhl_prod_int;
}

function wc_gzd_dhl_get_products( $shipping_country ) {
	if ( Package::is_shipping_domestic( $shipping_country ) ) {
		return wc_gzd_dhl_get_products_domestic();
	} else {
		return wc_gzd_dhl_get_products_international();
	}
}

function wc_gzd_dhl_get_return_products( $shipping_country ) {
	if ( Package::is_shipping_domestic( $shipping_country ) ) {
		return wc_gzd_dhl_get_return_products_domestic();
	} else {
		return wc_gzd_dhl_get_return_products_international();
	}
}

function wc_gzd_dhl_get_return_receivers() {
	$receivers = Package::get_return_receivers();
	$select    = array();

	foreach( $receivers as $receiver ) {
		$select[ $receiver['slug'] ] = esc_attr( $receiver['id'] ) . ' (' . ( ! empty( esc_attr( $receiver['country'] ) ) ? $receiver['country'] : '*' ) . ')';
	}

	return $select;
}

function wc_gzd_dhl_get_default_return_receiver( $country, $method = false ) {
	return Package::get_return_receiver_by_country( $country );
}

function wc_gzd_dhl_get_default_return_receiver_slug( $country, $method = false ) {
	$receiver = Package::get_return_receiver_by_country( $country );

	return ( $receiver ? $receiver['slug'] : '' );
}

function wc_gzd_dhl_get_default_product( $country, $method = false ) {
	if ( Package::is_shipping_domestic( $country ) ) {
		return Package::get_setting( 'label_default_product_dom', $method );
	} else {
		return Package::get_setting( 'label_default_product_int', $method );
	}
}

function wc_gzd_dhl_get_products_domestic() {

	$country = Package::get_base_country();

	$germany_dom = array(
		'V01PAK'  => _x( 'DHL Paket', 'dhl', 'woocommerce-germanized' ),
		'V01PRIO' => _x( 'DHL Paket PRIO', 'dhl', 'woocommerce-germanized' ),
		'V06PAK'  => _x( 'DHL Paket Taggleich', 'dhl', 'woocommerce-germanized' ),
	);

	$dhl_prod_dom = array();

	switch ( $country ) {
		case 'DE':
			$dhl_prod_dom = $germany_dom;
			break;
		default:
			break;
	}

	return $dhl_prod_dom;
}

function wc_gzd_dhl_get_return_label_by_parent( $label_parent_id ) {
	$labels = wc_gzd_dhl_get_labels( array(
		'parent_id' => $label_parent_id,
		'type'      => 'return',
	) );

	if ( ! empty( $labels ) ) {
		return $labels[0];
	}

	return false;
}

function wc_gzd_dhl_get_return_label_by_shipment( $the_shipment ) {
	return wc_gzd_dhl_get_shipment_label( $the_shipment, 'return' );
}

function wc_gzd_dhl_get_shipment_label( $the_shipment, $type = '' ) {
	$shipment_id = ShipmentFactory::get_shipment_id( $the_shipment );

	if ( $shipment_id ) {

		$args = array(
			'shipment_id' => $shipment_id,
		);

		if ( ! empty( $type ) ) {
			$args['type'] = $type;
		}

		$labels = wc_gzd_dhl_get_labels( $args );

		if ( ! empty( $labels ) ) {
			return $labels[0];
		}
	}

	return false;
}

function wc_gzd_dhl_generate_label_filename( $label, $prefix = 'label' ) {
    $filename = 'dhl-' . $prefix . '-' . $label->get_shipment_id() . '.pdf';

    return $filename;
}

function _wc_gzd_dhl_keep_force_filename( $new_filename ) {
	return isset( $GLOBALS['gzd_dhl_unique_filename'] ) ? $GLOBALS['gzd_dhl_unique_filename'] : $new_filename;
}

function wc_gzd_dhl_upload_data( $filename, $bits, $relative = true ) {
    try {
        Package::set_upload_dir_filter();
        $GLOBALS['gzd_dhl_unique_filename'] = $filename;
	    add_filter( 'wp_unique_filename', '_wc_gzd_dhl_keep_force_filename', 10, 1 );

	    $tmp = wp_upload_bits( $filename,null, $bits );

	    unset( $GLOBALS['gzd_dhl_unique_filename'] );
	    remove_filter( 'wp_unique_filename', '_wc_gzd_dhl_keep_force_filename', 10 );
	    Package::unset_upload_dir_filter();

        if ( isset( $tmp['file'] ) ) {
            $path = $tmp['file'];

            if ( $relative ) {
	            $path = Package::get_relative_upload_dir( $path );
            }

            return $path;
        } else {
            throw new Exception( _x( 'Error while uploading label.', 'dhl', 'woocommerce-germanized' ) );
        }
    } catch ( Exception $e ) {
        return false;
    }
}

function wc_gzd_dhl_get_label_types() {
	return array_keys( wc_gzd_dhl_get_label_type_data( false ) );
}

/**
 * Get label type data by type.
 *
 * @param  string $type type name.
 * @return bool|array Details about the label type.
 */
function wc_gzd_dhl_get_label_type_data( $type = false ) {
	$types = array(
		'simple' => array(
			'class_name' => '\Vendidero\Germanized\DHL\SimpleLabel'
		),
		'return' => array(
			'class_name' => '\Vendidero\Germanized\DHL\ReturnLabel'
		),
	);

	if ( $type && array_key_exists( $type, $types ) ) {
		return $types[ $type ];
	} elseif( false === $type ) {
		return $types;
	} else {
		return $types['simple'];
	}
}

/**
 * Get the order ID depending on what was passed.
 *
 * @since 3.0.0
 * @param  mixed $order Order data to convert to an ID.
 * @return int|bool false on failure
 */
function wc_gzd_dhl_get_label_id( $label ) {
    if ( is_numeric( $label ) ) {
        return $label;
    } elseif ( $label instanceof Vendidero\Germanized\DHL\Label ) {
        return $label->get_id();
    } elseif ( ! empty( $label->label_id ) ) {
        return $label->label_id;
    } else {
        return false;
    }
}
