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
use Vendidero\Germanized\DHL\Order;
use Vendidero\Germanized\DHL\Package;
use Vendidero\Germanized\DHL\ParcelLocator;
use Vendidero\Germanized\DHL\ParcelServices;
use Vendidero\Germanized\DHL\Product;
use Automattic\WooCommerce\Utilities\NumberUtil;

use Vendidero\Germanized\Shipments\Shipment;
use Vendidero\Germanized\Shipments\SimpleShipment;
use Vendidero\Germanized\Shipments\ReturnShipment;
use Vendidero\Germanized\Shipments\ShipmentFactory;

defined( 'ABSPATH' ) || exit;

function wc_gzd_dhl_round_customs_item_weight( $value, $precision = 0 ) {
	return NumberUtil::round( $value, $precision, 2 );
}

/**
 * @param Vendidero\Germanized\DHL\Label\Label $label
 *
 * @return array|false
 */
function wc_gzd_dhl_get_shipment_customs_data( $label, $max_desc_length = 255 ) {
	if ( ! $shipment = $label->get_shipment() ) {
		return false;
	}

	$customs_data = $label->get_customs_data( $max_desc_length );

	foreach ( $customs_data['items'] as $key => $item ) {
		if ( $shipment_item = $shipment->get_item( $key ) ) {
			/**
			 * Apply legacy filters
			 */
			$item['description'] = apply_filters( 'woocommerce_gzd_dhl_customs_item_description', $item['description'], $shipment_item, $label, $shipment );
			$item['category']    = apply_filters( 'woocommerce_gzd_dhl_customs_item_category', $item['category'], $shipment_item, $label, $shipment );

			$customs_data['items'][ $key ] = apply_filters( 'woocommerce_gzd_dhl_customs_item', $item, $shipment_item, $shipment, $label );
		}
	}

	return apply_filters( 'woocommerce_gzd_dhl_customs_data', $customs_data, $label, $shipment );
}

function wc_gzd_dhl_format_preferred_api_time( $time ) {
	return str_replace( array( ':', '-' ), '', $time );
}

/**
 * @param false|Shipment $shipment
 *
 * @return array
 */
function wc_gzd_dhl_get_label_payment_ref_placeholder( $shipment = false ) {
	return apply_filters(
		'woocommerce_gzd_dhl_label_payment_ref_placeholder',
		array(
			'{shipment_id}' => $shipment ? $shipment->get_shipment_number() : '',
			'{order_id}'    => $shipment ? $shipment->get_order_number() : '',
			'{email}'       => $shipment ? $shipment->get_email() : '',
		)
	);
}

function wc_gzd_dhl_get_preferred_days_select_options( $days, $current = '' ) {
	$preferred_days = array( 0 => _x( 'None', 'dhl day context', 'woocommerce-germanized' ) );

	if ( ! empty( $days ) ) {
		$days = array_keys( $days );

		foreach ( $days as $day ) {

			if ( empty( $day ) ) {
				continue;
			}

			$date = new \WC_DateTime( $day );
			$date->setTimezone( new DateTimeZone( 'Europe/Berlin' ) );

			$formatted_day  = $date->date_i18n( wc_date_format() );
			$preferred_days = array_merge( $preferred_days, array( $day => $formatted_day ) );
		}
	}

	if ( ! empty( $current ) ) {
		$date = new \WC_DateTime( $current );
		$date->setTimezone( new DateTimeZone( 'Europe/Berlin' ) );

		$preferred_days[ $current ] = $date->date_i18n( wc_date_format() );
	}

	return $preferred_days;
}

function wc_gzd_dhl_get_duties() {
	$duties = array(
		'DDU' => _x( 'Delivery Duty Unpaid', 'dhl', 'woocommerce-germanized' ),
		'DDP' => _x( 'Delivery Duty Paid', 'dhl', 'woocommerce-germanized' ),
		'DXV' => _x( 'Delivery Duty Paid (excl. VAT )', 'dhl', 'woocommerce-germanized' ),
		'DDX' => _x( 'Delivery Duty Paid (excl. Duties, taxes and VAT)', 'dhl', 'woocommerce-germanized' ),
	);

	return $duties;
}

function wc_gzd_dhl_is_valid_visual_min_age( $min_age ) {
	$ages = wc_gzd_dhl_get_visual_min_ages();

	if ( empty( $min_age ) || ( ! array_key_exists( $min_age, $ages ) && ! in_array( $min_age, $ages, true ) ) ) {
		return false;
	}

	return true;
}

function wc_gzd_dhl_is_valid_ident_min_age( $min_age ) {
	$ages = wc_gzd_dhl_get_ident_min_ages();

	if ( empty( $min_age ) || ( ! array_key_exists( $min_age, $ages ) && ! in_array( $min_age, $ages, true ) ) ) {
		return false;
	}

	return true;
}

function wc_gzd_dhl_get_visual_min_ages() {
	$visual_age = array(
		'0'   => _x( 'None', 'age context', 'woocommerce-germanized' ),
		'A16' => _x( 'Minimum age of 16', 'dhl', 'woocommerce-germanized' ),
		'A18' => _x( 'Minimum age of 18', 'dhl', 'woocommerce-germanized' ),
	);

	return $visual_age;
}

function wc_gzd_dhl_get_ident_min_ages() {
	return wc_gzd_dhl_get_visual_min_ages();
}

function wc_gzd_dhl_get_label_reference( $reference_text, $placeholders = array() ) {
	return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $reference_text );
}

/**
 * @param Label\Label $label
 * @param Shipment $shipment
 *
 * @return string
 */
function wc_gzd_dhl_get_label_customer_reference( $label, $shipment ) {
	/**
	 * Filter to adjust the customer reference field placed on the DHL label. Maximum characeter length: 35.
	 *
	 * @param string         $text The customer reference text.
	 * @param Label\Label    $label The label instance.
	 * @param SimpleShipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/DHL
	 */
	$ref = apply_filters(
		'woocommerce_gzd_dhl_label_customer_reference',
		wc_gzd_dhl_get_label_reference(
			_x( 'Shipment #{shipment_id} to order {order_id}', 'dhl', 'woocommerce-germanized' ),
			array(
				'{shipment_id}' => $shipment->get_shipment_number(),
				'{order_id}'    => $shipment->get_order_number(),
			)
		),
		$label,
		$shipment
	);

	return sanitize_text_field( substr( $ref, 0, 35 ) );
}

function wc_gzd_dhl_get_label_endorsement_type( $label, $shipment ) {
	/**
	 * Filter to adjust the endorsement type for internation shipments.
	 *
	 * @param string         $text The endorsement type: IMMEDIATE or ABANDONMENT.
	 * @param Label\Label    $label The label instance.
	 * @param SimpleShipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/DHL
	 */
	$type = strtoupper( apply_filters( 'woocommerce_gzd_dhl_label_endorsement_type', 'IMMEDIATE', $label, $shipment ) );

	if ( ! in_array( $type, array( 'IMMEDIATE', 'ABANDONMENT' ), true ) ) {
		$type = 'IMMEDIATE';
	}

	return $type;
}

function wc_gzd_dhl_get_return_label_customer_reference( $label, $shipment ) {
	/**
	 * Filter to adjust the customer reference field placed on the DHL return label. Maximum characeter length: 30.
	 *
	 * @param string         $text The customer reference text.
	 * @param Label\Label    $label The label instance.
	 * @param ReturnShipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/DHL
	 */
	$ref = apply_filters(
		'woocommerce_gzd_dhl_return_label_customer_reference',
		wc_gzd_dhl_get_label_reference(
			_x( 'Return #{shipment_id} to order {order_id}', 'dhl', 'woocommerce-germanized' ),
			array(
				'{shipment_id}' => $shipment->get_id(),
				'{order_id}'    => $shipment->get_order_number(),
			)
		),
		$label,
		$shipment
	);

	return sanitize_text_field( substr( $ref, 0, 30 ) );
}

function wc_gzd_dhl_get_inlay_return_label_reference( $label, $shipment ) {
	/**
	 * Filter to adjust the inlay return reference field placed on the DHL label. Maximum characeter length: 35.
	 *
	 * @param string         $text The customer reference text.
	 * @param Label\Label    $label The label instance.
	 * @param SimpleShipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Germanized/DHL
	 */
	$ref = apply_filters(
		'woocommerce_gzd_dhl_inlay_return_label_reference',
		wc_gzd_dhl_get_label_reference(
			_x( 'Return shipment #{shipment_id} to order #{order_id}', 'dhl', 'woocommerce-germanized' ),
			array(
				'{shipment_id}' => $shipment->get_id(),
				'{order_id}'    => $shipment->get_order_number(),
			)
		),
		$label,
		$shipment
	);

	return sanitize_text_field( substr( $ref, 0, 35 ) );
}

/**
 * @return false|\Vendidero\Germanized\DHL\ShippingProvider\ShippingMethod
 */
function wc_gzd_dhl_get_current_shipping_method() {
	if ( $current = wc_gzd_get_current_shipping_method_id() ) {
		return wc_gzd_dhl_get_shipping_method( $current );
	}

	return false;
}

function wc_gzd_dhl_get_international_services() {
	return array(
		'GoGreen',
		'AdditionalInsurance',
		'CDP',
		'Economy',
		'Premium',
		'PDDP',
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
		'CDP',
		'PDDP',
		'Economy',
		'AdditionalInsurance',
		'BulkyGoods',
		'IdentCheck',
		'CashOnDelivery',
		'ParcelOutletRouting',
		'GoGreen',
	);
}

/**
 * @param $instance_id
 *
 * @return \Vendidero\Germanized\DHL\ShippingProvider\ShippingMethod
 */
function wc_gzd_dhl_get_shipping_method( $instance_id ) {
	$method = wc_gzd_get_shipping_provider_method( $instance_id );
	return new \Vendidero\Germanized\DHL\ShippingProvider\ShippingMethod( $method );
}

function wc_gzd_dhl_get_deutsche_post_shipping_method( $instance_id ) {
	return wc_gzd_dhl_get_shipping_method( $instance_id );
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
		'parcelshop'  => _x( 'Postfiliale', 'dhl', 'woocommerce-germanized' ),
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
		'sat',
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
	} elseif ( in_array( $type, $types, true ) ) {
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
	if ( ! in_array( $country, array( 'US', 'AU' ), true ) ) {

		// Get all states for a country
		$states = WC()->countries->get_states( $country );

		// If the state is empty, it was entered as free text
		if ( ! empty( $states ) && ! empty( $state ) ) {
			// Change the state to be the name and not the code
			$state = $states[ $state ];

			// Remove anything in parentheses (e.g. TH)
			$ind = strpos( $state, ' (' );

			if ( false !== $ind ) {
				$state = substr( $state, 0, $ind );
			}
		}
	}

	return $state;
}

/**
 * @param $the_product
 *
 * @return \Vendidero\Germanized\Shipments\Product
 */
function wc_gzd_dhl_get_product( $the_product ) {
	return wc_gzd_shipments_get_product( $the_product );
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

	if ( ! Package::is_shipping_domestic( $shipment->get_country(), $shipment->get_postcode() ) ) {

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
 * @param \Vendidero\Germanized\DHL\Label\ReturnLabel $label
 */
function wc_gzd_dhl_get_return_label_sender_street_number( $label ) {
	$street_number = $label->get_sender_street_number();

	if ( ! Package::is_shipping_domestic( $label->get_sender_country(), $label->get_sender_postcode() ) ) {
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
 * @param $product
 * @param false|Shipment $shipment
 *
 * @return string[]
 */
function wc_gzd_dhl_get_product_services( $product, $shipment = false ) {
	if ( in_array( $product, array_keys( wc_gzd_dhl_get_products_domestic() ), true ) ) {
		$services = wc_gzd_dhl_get_services();
	} else {
		$services = wc_gzd_dhl_get_international_services();
	}

	/**
	 * Warenpost does only support certain services
	 */
	if ( 'V62WP' === $product ) {
		$services = array_intersect(
			$services,
			array(
				'PreferredTime',
				'PreferredLocation',
				'PreferredNeighbour',
				'PreferredDay',
				'ParcelOutletRouting',
				'GoGreen',
			)
		);
	} elseif ( 'V66WPI' === $product ) {
		$services = array_intersect(
			$services,
			array(
				'Premium',
			)
		);
	}

	/**
	 * Economy, CDP, PDDP are available for Paket International only
	 */
	if ( 'V53WPAK' === $product ) {
		/**
		 * Economy is not available for EU countries. Premium is booked by default.
		 */
		if ( $shipment && $shipment->is_shipping_inner_eu() ) {
			$services = array_diff( $services, array( 'Economy', 'Premium' ) );
		}
	} else {
		$services = array_diff( $services, array( 'Economy', 'CDP', 'PDDP' ) );
	}

	return $services;
}

/**
 * @param $product
 * @param $service
 * @param false|Shipment $shipment
 *
 * @return bool
 */
function wc_gzd_dhl_product_supports_service( $product, $service, $shipment = false ) {
	$services = wc_gzd_dhl_get_product_services( $product, $shipment );

	if ( ! in_array( $service, $services, true ) ) {
		return false;
	}

	return true;
}

/**
 * @param $service
 * @param false|Shipment $shipment
 *
 * @return array
 */
function wc_gzd_dhl_get_service_product_attributes( $service, $shipment = false ) {
	$products_supported = array();

	foreach ( array_keys( array_merge( wc_gzd_dhl_get_products_domestic(), wc_gzd_dhl_get_products_eu(), wc_gzd_dhl_get_products_international() ) ) as $product ) {
		if ( wc_gzd_dhl_product_supports_service( $product, $service, $shipment ) ) {
			$products_supported[] = $product;
		}
	}

	return array(
		'data-products-supported' => implode( ',', $products_supported ),
	);
}

/**
 * @param Shipment $shipment
 *
 * @return array
 */
function wc_gzd_dhl_get_deutsche_post_products( $shipment, $parent_only = true ) {
	$country  = $shipment->get_country();
	$postcode = $shipment->get_postcode();

	if ( 'return' === $shipment->get_type() ) {
		$country  = $shipment->get_sender_country();
		$postcode = $shipment->get_sender_postcode();
	}

	if ( Package::is_shipping_domestic( $country, $postcode ) ) {
		return wc_gzd_dhl_get_deutsche_post_products_domestic( $shipment, $parent_only );
	} elseif ( Package::is_eu_shipment( $country, $postcode ) ) {
		return wc_gzd_dhl_get_deutsche_post_products_eu( $shipment, $parent_only );
	} else {
		return wc_gzd_dhl_get_deutsche_post_products_international( $shipment, $parent_only );
	}
}

/**
 * @param Shipment|false $shipment
 *
 * @return array
 */
function wc_gzd_dhl_get_deutsche_post_products_domestic( $shipment = false, $parent_only = true ) {
	$dom = Package::get_internetmarke_api()->get_available_products(
		array(
			'product_destination' => 'national',
			'shipment_weight'     => $shipment ? wc_gzd_dhl_get_shipment_weight( $shipment, 'g' ) : false,
		)
	);

	return wc_gzd_dhl_im_get_product_list( $dom, $parent_only );
}

function wc_gzd_dhl_im_get_product_list( $products, $parent_only = true ) {
	$list                       = array();
	$additional_parent_products = array();

	foreach ( $products as $product ) {
		if ( $parent_only && $product->product_parent_id > 0 ) {
			$additional_parent_products[] = $product->product_parent_id;
			continue;
		}

		$list[ $product->product_code ] = wc_gzd_dhl_get_im_product_title( $product->product_name );
	}

	$additional_parent_products = array_unique( $additional_parent_products );

	if ( ! empty( $additional_parent_products ) ) {
		foreach ( $additional_parent_products as $product_id ) {
			$product = Package::get_internetmarke_api()->get_product_data( $product_id );

			if ( ! array_key_exists( $product->product_code, $list ) ) {
				$list[ $product->product_code ] = wc_gzd_dhl_get_im_product_title( $product->product_name );
			}
		}
	}

	return $list;
}

function wc_gzd_dhl_get_deutsche_post_products_eu( $shipment = false, $parent_only = true ) {
	$non_warenpost = Package::get_internetmarke_api()->get_available_products(
		array(
			'product_destination' => 'international',
			'product_is_wp_int'   => 0,
			'shipment_weight'     => $shipment ? wc_gzd_dhl_get_shipment_weight( $shipment, 'g' ) : false,
		)
	);

	$warenpost = Package::get_internetmarke_api()->get_available_products(
		array(
			'product_destination' => 'eu',
			'product_is_wp_int'   => 1,
			'shipment_weight'     => $shipment ? wc_gzd_dhl_get_shipment_weight( $shipment, 'g' ) : false,
		)
	);

	$international = array_merge( $non_warenpost, $warenpost );

	return wc_gzd_dhl_im_get_product_list( $international, $parent_only );
}

/**
 * @param Shipment|false $shipment
 *
 * @return array
 */
function wc_gzd_dhl_get_deutsche_post_products_international( $shipment = false, $parent_only = true ) {
	if ( $shipment && Package::is_eu_shipment( $shipment->get_country(), $shipment->get_postcode() ) ) {
		return wc_gzd_dhl_get_deutsche_post_products_eu( $shipment );
	} else {
		$international = Package::get_internetmarke_api()->get_available_products(
			array(
				'product_destination' => 'international',
				'shipment_weight'     => $shipment ? wc_gzd_dhl_get_shipment_weight( $shipment, 'g' ) : false,
			)
		);

		return wc_gzd_dhl_im_get_product_list( $international, $parent_only );
	}
}

/**
 * @param Label\DHL $label
 * @param string $type
 *
 * @return mixed|string|void
 * @see https://entwickler.dhl.de/group/ep/grundlagen2
 */
function wc_gzd_dhl_get_custom_label_format( $label, $type = '' ) {
	$available = array(
		'A4',
		'910-300-700',
		'910-300-700-oZ',
		'910-300-710',
		'910-300-600',
		'910-300-610',
		'910-300-400',
		'910-300-410',
		'910-300-300',
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
	 * <li>100x70mm (Warenpost only)</li>
	 * </ul>
	 *
	 * @param string    $format The label format.
	 * @param Label\DHL $label The label instance.
	 * @param string    $type The type e.g. inlay_return.
	 *
	 * @since 3.0.5
	 * @package Vendidero/Germanized/DHL
	 */
	$format = apply_filters( 'woocommerce_gzd_dhl_label_custom_format', '', $label, $type );

	/**
	 * Warenpost format
	 */
	if ( in_array( $label->get_product_id(), array( 'V62WP', 'V66WPI' ), true ) ) {
		$available[] = '100x70mm';
	}

	if ( ! empty( $format ) && ! in_array( $format, $available, true ) ) {
		$format = '';
	}

	return $format;
}

function wc_gzd_dhl_get_order( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	if ( is_a( $order, 'WC_Order' ) ) {
		try {
			return new Vendidero\Germanized\DHL\Order( $order );
		} catch ( Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, array( $order ) );
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
		'V55PAK',
	);
}

function wc_gzd_dhl_get_return_products_international() {

	$retoure = array(
		'retoure_international_a' => _x( 'DHL Retoure International A', 'dhl', 'woocommerce-germanized' ),
		'retoure_international_b' => _x( 'DHL Retoure International B', 'dhl', 'woocommerce-germanized' ),
	);

	return $retoure;
}

function wc_gzd_dhl_get_return_products_domestic() {

	$retoure = array(
		'retoure_online' => _x( 'DHL Retoure Online', 'dhl', 'woocommerce-germanized' ),
	);

	return $retoure;
}

function wc_gzd_dhl_get_im_product_title( $product_name ) {
	$title = $product_name;

	return $title;
}

function wc_gzd_dhl_is_warenpost_international_available() {
	return true;
}

function wc_gzd_dhl_get_products_international() {
	$country = Package::get_base_country();

	$germany_int = array(
		'V53WPAK' => _x( 'DHL Paket International', 'dhl', 'woocommerce-germanized' ),
	);

	if ( wc_gzd_dhl_is_warenpost_international_available() ) {
		$germany_int['V66WPI'] = _x( 'DHL Warenpost International', 'dhl', 'woocommerce-germanized' );
	}

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

function wc_gzd_dhl_get_product_title( $product_id ) {
	$products = wc_gzd_dhl_get_products_domestic() + wc_gzd_dhl_get_products_eu() + wc_gzd_dhl_get_products_international();

	return array_key_exists( $product_id, $products ) ? $products[ $product_id ] : $product_id;
}

function wc_gzd_dhl_get_products_eu() {
	$country = Package::get_base_country();

	$germany_int = array(
		'V53WPAK' => _x( 'DHL Paket International', 'dhl', 'woocommerce-germanized' ),
		'V55PAK'  => _x( 'DHL Paket Connect', 'dhl', 'woocommerce-germanized' ),
		'V54EPAK' => _x( 'DHL Europaket (B2B)', 'dhl', 'woocommerce-germanized' ),
	);

	if ( wc_gzd_dhl_is_warenpost_international_available() ) {
		$germany_int['V66WPI'] = _x( 'DHL Warenpost International', 'dhl', 'woocommerce-germanized' );
	}

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

function wc_gzd_dhl_get_products( $shipping_country, $shipping_postcode = '' ) {
	if ( Package::is_shipping_domestic( $shipping_country, $shipping_postcode ) ) {
		return wc_gzd_dhl_get_products_domestic();
	} elseif ( Package::is_eu_shipment( $shipping_country, $shipping_postcode ) ) {
		return wc_gzd_dhl_get_products_eu();
	} else {
		return wc_gzd_dhl_get_products_international();
	}
}

function wc_gzd_dhl_get_return_products( $shipping_country, $shipping_postcode = '' ) {
	if ( Package::is_shipping_domestic( $shipping_country, $shipping_postcode ) ) {
		return wc_gzd_dhl_get_return_products_domestic();
	} else {
		return wc_gzd_dhl_get_return_products_international();
	}
}

function wc_gzd_dhl_get_return_receivers() {
	$receivers = Package::get_return_receivers();
	$select    = array();

	foreach ( $receivers as $receiver ) {
		$select[ $receiver['slug'] ] = esc_attr( $receiver['id'] ) . ' (' . ( ! empty( esc_attr( $receiver['country'] ) ) ? $receiver['country'] : '*' ) . ')';
	}

	return $select;
}

function wc_gzd_dhl_get_default_return_receiver( $country, $method = false ) {
	return Package::get_return_receiver_by_country( $country );
}

function wc_gzd_dhl_get_default_return_receiver_slug( $country ) {
	$receiver = Package::get_return_receiver_by_country( $country );

	return ( $receiver ? $receiver['slug'] : '' );
}

function wc_gzd_dhl_get_default_product( $country, $shipment = false ) {
	if ( Package::is_shipping_domestic( $country ) ) {
		return Package::get_setting( 'label_default_product_dom', $shipment );
	} elseif ( Package::is_eu_shipment( $country ) ) {
		return Package::get_setting( 'label_default_product_eu', $shipment );
	} else {
		return Package::get_setting( 'label_default_product_int', $shipment );
	}
}

function wc_gzd_dhl_get_products_domestic() {
	$country = Package::get_base_country();

	$germany_dom = array(
		'V01PAK'  => _x( 'DHL Paket', 'dhl', 'woocommerce-germanized' ),
		'V01PRIO' => _x( 'DHL Paket PRIO', 'dhl', 'woocommerce-germanized' ),
		'V06PAK'  => _x( 'DHL Paket Taggleich', 'dhl', 'woocommerce-germanized' ),
		'V62WP'   => _x( 'DHL Warenpost', 'dhl', 'woocommerce-germanized' ),
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
