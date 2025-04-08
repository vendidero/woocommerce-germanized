<?php
use Vendidero\Shiptastic\DHL\Label;
use Vendidero\Shiptastic\DHL\Order;
use Vendidero\Shiptastic\DHL\Package;
use Vendidero\Shiptastic\DHL\ParcelLocator;
use Vendidero\Shiptastic\DHL\ParcelServices;
use Vendidero\Shiptastic\DHL\Product;

use Vendidero\Shiptastic\Shipment;
use Vendidero\Shiptastic\SimpleShipment;
use Vendidero\Shiptastic\ReturnShipment;
use Vendidero\Shiptastic\ShipmentFactory;

defined( 'ABSPATH' ) || exit;

/**
 * @param Vendidero\Shiptastic\DHL\Label\Label $label
 *
 * @return array|false
 */
function wc_stc_dhl_get_shipment_customs_data( $label, $max_desc_length = 255 ) {
	if ( ! $shipment = $label->get_shipment() ) {
		return false;
	}

	$customs_data = $label->get_customs_data( $max_desc_length );

	foreach ( $customs_data['items'] as $key => $item ) {
		if ( $shipment_item = $shipment->get_item( $key ) ) {
			/**
			 * Apply legacy filters
			 */
			$item['description'] = apply_filters( 'woocommerce_shiptastic_dhl_customs_item_description', $item['description'], $shipment_item, $label, $shipment );
			$item['category']    = apply_filters( 'woocommerce_shiptastic_dhl_customs_item_category', $item['category'], $shipment_item, $label, $shipment );

			$customs_data['items'][ $key ] = apply_filters( 'woocommerce_shiptastic_dhl_customs_item', $item, $shipment_item, $shipment, $label );
		}
	}

	return apply_filters( 'woocommerce_shiptastic_dhl_customs_data', $customs_data, $label, $shipment );
}

/**
 * @param false|Shipment $shipment
 *
 * @return array
 */
function wc_stc_dhl_get_label_payment_ref_placeholder( $shipment = false ) {
	return apply_filters(
		'woocommerce_stc_dhl_label_payment_ref_placeholder',
		array(
			'{shipment_id}' => $shipment ? $shipment->get_shipment_number() : '',
			'{order_id}'    => $shipment ? $shipment->get_order_number() : '',
			'{email}'       => $shipment ? $shipment->get_email() : '',
		)
	);
}

function wc_stc_dhl_get_preferred_days_select_options( $days, $current = '' ) {
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

function wc_stc_dhl_get_duties() {
	$duties = array(
		'DDP' => _x( 'Delivery Duty Paid', 'dhl', 'woocommerce-germanized' ),
		'DAP' => _x( 'Delivery At Place', 'dhl', 'woocommerce-germanized' ),
		'DXV' => _x( 'Delivery Duty Paid (excl. VAT )', 'dhl', 'woocommerce-germanized' ),
		'DDX' => _x( 'Delivery Duty Paid (excl. Duties, taxes and VAT)', 'dhl', 'woocommerce-germanized' ),
	);

	return $duties;
}

function wc_stc_dhl_is_valid_visual_min_age( $min_age ) {
	$ages = wc_stc_dhl_get_visual_min_ages();

	if ( empty( $min_age ) || ( ! array_key_exists( $min_age, $ages ) && ! in_array( $min_age, $ages, true ) ) ) {
		return false;
	}

	return true;
}

function wc_stc_dhl_is_valid_ident_min_age( $min_age ) {
	$ages = wc_stc_dhl_get_ident_min_ages();

	if ( empty( $min_age ) || ( ! array_key_exists( $min_age, $ages ) && ! in_array( $min_age, $ages, true ) ) ) {
		return false;
	}

	return true;
}

function wc_stc_dhl_get_visual_min_ages() {
	$visual_age = array(
		'0'   => _x( 'None', 'age context', 'woocommerce-germanized' ),
		'A16' => _x( 'Minimum age of 16', 'dhl', 'woocommerce-germanized' ),
		'A18' => _x( 'Minimum age of 18', 'dhl', 'woocommerce-germanized' ),
	);

	return $visual_age;
}

function wc_stc_dhl_get_ident_min_ages() {
	return wc_stc_dhl_get_visual_min_ages();
}

function wc_stc_dhl_get_label_reference( $reference_text, $placeholders = array() ) {
	return str_replace( array_keys( $placeholders ), array_values( $placeholders ), $reference_text );
}

/**
 * @param Label\Label $label
 * @param Shipment $shipment
 *
 * @return string
 */
function wc_stc_dhl_get_label_customer_reference( $label, $shipment ) {
	$dhl         = wc_stc_get_shipping_provider( 'dhl' );
	$default_ref = $dhl->get_formatted_label_reference( $label, 'simple', 'ref_1' );

	/**
	 * Filter to adjust the customer reference field placed on the DHL label. Maximum characeter length: 35.
	 *
	 * @param string         $text The customer reference text.
	 * @param Label\Label    $label The label instance.
	 * @param SimpleShipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Shiptastic/DHL
	 */
	$ref = apply_filters(
		'woocommerce_stc_dhl_label_customer_reference',
		$default_ref,
		$label,
		$shipment
	);

	return wc_stc_dhl_escape_reference( $ref );
}

function wc_stc_dhl_get_endorsement_types() {
	return array(
		'return'  => _x( 'Return shipment', 'dhl', 'woocommerce-germanized' ),
		'abandon' => _x( 'Abandon shipment', 'dhl', 'woocommerce-germanized' ),
	);
}

/**
 * @param Label\DHL $label
 * @param $shipment
 * @param string $api_type
 *
 * @return string
 */
function wc_stc_dhl_get_label_endorsement_type( $label, $shipment, $api_type = 'default' ) {
	$type = $label->get_endorsement();

	/**
	 * Filter to adjust the endorsement type for internation shipments.
	 *
	 * @param string         $text The endorsement type: return or abandon.
	 * @param Label\Label    $label The label instance.
	 * @param SimpleShipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Shiptastic/DHL
	 */
	$type = strtolower( apply_filters( 'woocommerce_shiptastic_dhl_label_endorsement_type', $type, $label, $shipment ) );

	/**
	 * SOAP Label API was using IMMEDIATE instead of RETURN
	 */
	if ( 'immediate' === $type ) {
		$type = 'return';
	} elseif ( 'abandonment' === $type ) {
		$type = 'abandon';
	}

	if ( ! in_array( $type, array_keys( wc_stc_dhl_get_endorsement_types() ), true ) ) {
		$type = 'return';
	}

	/**
	 * The SOAP API uses abandonment instead of abandon and
	 * immediate instead of return.
	 */
	if ( 'default' === $api_type ) {
		if ( 'abandon' === $type ) {
			$type = 'abandonment';
		} else {
			$type = 'immediate';
		}
	}

	return strtoupper( $type );
}

function wc_stc_dhl_get_return_label_customer_reference( $label, $shipment ) {
	$dhl         = wc_stc_get_shipping_provider( 'dhl' );
	$default_ref = $dhl->get_formatted_label_reference( $label, 'return', 'ref_1' );

	/**
	 * Filter to adjust the customer reference field placed on the DHL return label. Maximum characeter length: 30.
	 *
	 * @param string         $text The customer reference text.
	 * @param Label\Label    $label The label instance.
	 * @param ReturnShipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Shiptastic/DHL
	 */
	$ref = apply_filters(
		'woocommerce_stc_dhl_return_label_customer_reference',
		$default_ref,
		$label,
		$shipment
	);

	return wc_stc_dhl_escape_reference( $ref, 30 );
}

function wc_stc_dhl_escape_reference( $ref, $length = 35 ) {
	/**
	 * Seems like DHL REST API does not properly escape those strings which leads to cryptic error messages, e.g.:
	 * Error: CLT103x150 is not a valid print format for shipment null.
	 */
	$ref = str_replace( array( '{', '}' ), '', $ref );

	return sanitize_text_field( wc_shiptastic_substring( $ref, 0, $length ) );
}

function wc_stc_dhl_get_inlay_return_label_reference( $label, $shipment ) {
	$dhl         = wc_stc_get_shipping_provider( 'dhl' );
	$default_ref = $dhl->get_formatted_label_reference( $label, 'simple', 'inlay' );

	/**
	 * Filter to adjust the inlay return reference field placed on the DHL label. Maximum characeter length: 35.
	 *
	 * @param string         $text The customer reference text.
	 * @param Label\Label    $label The label instance.
	 * @param SimpleShipment $shipment The shipment instance.
	 *
	 * @since 3.0.0
	 * @package Vendidero/Shiptastic/DHL
	 */
	$ref = apply_filters(
		'woocommerce_stc_dhl_inlay_return_label_reference',
		$default_ref,
		$label,
		$shipment
	);

	return wc_stc_dhl_escape_reference( $ref, 35 );
}

/**
 * @return \Vendidero\Shiptastic\ShippingMethod\ProviderMethod|false
 */
function wc_stc_dhl_get_current_shipping_method() {
	if ( $current = wc_stc_get_current_shipping_method_id() ) {
		return wc_stc_get_shipping_provider_method( $current );
	}

	return false;
}

/**
 * @param $instance_id
 *
 * @return \Vendidero\Shiptastic\ShippingMethod\ProviderMethod
 */
function wc_stc_dhl_get_shipping_method( $instance_id ) {
	return wc_stc_get_shipping_provider_method( $instance_id );
}

function wc_stc_dhl_get_deutsche_post_shipping_method( $instance_id ) {
	return wc_stc_dhl_get_shipping_method( $instance_id );
}

function wc_stc_dhl_get_pickup_types() {
	wc_deprecated_function( 'wc_stc_dhl_get_pickup_types', '3.1' );

	return array(
		'packstation' => _x( 'Packstation', 'dhl', 'woocommerce-germanized' ),
		'postoffice'  => _x( 'Postfiliale', 'dhl', 'woocommerce-germanized' ),
		'parcelshop'  => _x( 'Postfiliale', 'dhl', 'woocommerce-germanized' ),
	);
}

function wc_stc_dhl_is_pickup_type( $maybe_type, $type = 'packstation' ) {
	wc_deprecated_function( 'wc_stc_dhl_is_pickup_type', '3.1' );

	$label = wc_stc_dhl_get_pickup_type( $type );

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

function wc_stc_dhl_get_excluded_working_days() {
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

function wc_stc_dhl_order_has_pickup( $order ) {
	wc_deprecated_function( 'wc_stc_dhl_order_has_pickup', '3.1' );

	return ParcelLocator::order_has_pickup( $order );
}

function wc_stc_dhl_get_pickup_type( $type ) {
	wc_deprecated_function( 'wc_stc_dhl_get_pickup_type', '3.1' );

	$types = wc_stc_dhl_get_pickup_types();

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
function wc_stc_dhl_wp_error_has_errors( $error ) {
	return wc_stc_shipment_wp_error_has_errors( $error );
}

function wc_stc_dhl_is_valid_datetime( $maybe_datetime, $format = 'Y-m-d' ) {
	if ( ! is_a( $maybe_datetime, 'DateTime' && ! is_numeric( $maybe_datetime ) ) ) {
		if ( ! DateTime::createFromFormat( $format, $maybe_datetime ) ) {
			return false;
		}
	}

	return true;
}

function wc_stc_dhl_format_label_state( $state, $country ) {
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

	// No need to transmit states for DE
	if ( 'DE' === $country ) {
		$state = '';
	}

	return $state;
}

/**
 * @param Shipment $shipment
 *
 * @return string
 */
function wc_stc_dhl_get_parcel_outlet_routing_email_address( $shipment ) {
	$email = $shipment->get_email();

	if ( empty( $email ) ) {
		$email = $shipment->get_sender_email();
	}

	return apply_filters( 'woocommerce_shiptastic_dhl_parcel_outlet_routing_email_address', $email, $shipment );
}

/**
 * @param $the_product
 *
 * @return \Vendidero\Shiptastic\Product
 */
function wc_stc_dhl_get_product( $the_product ) {
	return wc_shiptastic_get_product( $the_product );
}

/**
 * @param Shipment $shipment
 */
function wc_stc_dhl_get_label_shipment_address_addition( $shipment ) {
	return wc_stc_get_shipment_address_addition( $shipment );
}

/**
 * @param Shipment $shipment
 *
 * @return mixed
 */
function wc_stc_dhl_get_label_shipment_street_number( $shipment ) {
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
			 * @package Vendidero/Shiptastic/DHL
			 */
			$street_number = apply_filters( 'woocommerce_shiptastic_dhl_label_shipment_street_number_placeholder', '0' );
		}
	}

	return $street_number;
}

/**
 * @param \Vendidero\Shiptastic\DHL\Label\ReturnLabel $label
 */
function wc_stc_dhl_get_return_label_sender_street_number( $label ) {
	$street_number = $label->get_sender_street_number();

	if ( ! Package::is_shipping_domestic( $label->get_sender_country(), $label->get_sender_postcode() ) ) {
		if ( empty( $street_number ) ) {
			/**
			 * This filter is documented in includes/wc-stc-dhl-core-functions.php
			 */
			$street_number = apply_filters( 'woocommerce_shiptastic_dhl_label_shipment_street_number_placeholder', '0' );
		}
	}

	return $street_number;
}

/**
 * @param Label\DHL $label
 * @param string $type
 *
 * @return mixed|string|void
 * @see https://entwickler.dhl.de/group/ep/grundlagen2
 */
function wc_stc_dhl_get_custom_label_format( $label, $type = '' ) {
	$shipment     = $label->get_shipment();
	$available    = $shipment ? $shipment->get_shipping_provider_instance()->get_print_formats()->filter( array( 'product_id' => $label->get_product_id() ) )->as_options() : array();
	$label_format = 'default' === $label->get_print_format() ? '' : $label->get_print_format();

	if ( 'inlay_return' === $type ) {
		$available = array_diff( $available, array( '100x70mm' ) );
	}

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
	 * @package Vendidero/Shiptastic/DHL
	 */
	$format = apply_filters( 'woocommerce_shiptastic_dhl_label_custom_format', $label_format, $label, $type );

	/**
	 * Do not allow Warenpost label format for inlay returns
	 */
	if ( ! empty( $format ) && ( ! array_key_exists( $format, $available ) ) ) {
		$format = '';
	}

	return $format;
}

function wc_stc_dhl_get_order( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	if ( is_a( $order, 'WC_Order' ) ) {
		try {
			return new Vendidero\Shiptastic\DHL\Order( $order );
		} catch ( Exception $e ) {
			wc_caught_exception( $e, __FUNCTION__, array( $order ) );
			return false;
		}
	}

	return false;
}

/**
 * @param $product
 * @param $args
 *
 * @return string
 * @throws Exception
 */
function wc_stc_dhl_get_billing_number( $product, $args = array() ) {
	$args = wp_parse_args(
		$args,
		array(
			'api_type'   => 'default',
			'services'   => array(),
			'is_sandbox' => Package::is_debug_mode(),
		)
	);

	$provider = Package::get_dhl_shipping_provider();

	if ( 'return' === $product ) {
		$product_number = '07';
	} else {
		preg_match( '!\d+!', $product, $matches );

		if ( isset( $matches[0] ) && ! empty( $matches[0] ) ) {
			$product_number = $matches[0];
		} else {
			$product_number = false;
		}
	}

	if ( $product_number ) {
		$participation_number = Package::get_participation_number( $product, $args );
		$account_base         = Package::get_account_number( $args['is_sandbox'] );

		// Participation number may contain account number too
		if ( strlen( $participation_number ) >= 12 ) {
			$account_base         = substr( $participation_number, 0, 10 ); // First 10 chars
			$participation_number = substr( $participation_number, -2 ); // Last 2 chars
		}

		$account_number = $account_base . $product_number . $participation_number;

		if ( strlen( $account_number ) !== 14 ) {
			throw new Exception( wp_kses_post( sprintf( _x( 'Either your customer number or the participation number for <strong>%1$s</strong> is missing. Please validate your <a href="%2$s">settings</a> and try again.', 'dhl', 'woocommerce-germanized' ), esc_html( $provider->get_product( $product ) ? $provider->get_product( $product )->get_label() : $product ), esc_url( Package::get_dhl_shipping_provider()->get_edit_link() ) ) ) );
		}

		return $account_number;
	} else {
		throw new Exception( esc_html_x( 'Could not create billing number, participation number is missing.', 'dhl', 'woocommerce-germanized' ) );
	}
}

function wc_stc_dhl_get_return_receivers() {
	$receivers = Package::get_return_receivers();
	$select    = array();

	foreach ( $receivers as $receiver ) {
		$select[ $receiver['slug'] ] = esc_attr( $receiver['id'] ) . ' (' . ( ! empty( esc_attr( $receiver['country'] ) ) ? $receiver['country'] : '*' ) . ')';
	}

	return $select;
}

function wc_stc_dhl_get_default_return_receiver_slug( $country ) {
	$receiver = Package::get_return_receiver_by_country( $country );

	return ( $receiver ? $receiver['slug'] : '' );
}
