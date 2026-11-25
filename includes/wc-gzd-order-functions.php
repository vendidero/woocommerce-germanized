<?php
/**
 * Order Functions
 *
 * WC_GZD order functions.
 *
 * @author        Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * @param $total_rows
 * @param WC_Order $order
 *
 * @return mixed
 */
function wc_gzd_cart_forwarding_fee_notice_filter( $total_rows, $order ) {
	// Seems like it is a refund order other order type.
	if ( ! is_callable( array( $order, 'get_payment_method' ) ) ) {
		return $total_rows;
	}

	$gateways = WC()->payment_gateways()->get_available_payment_gateways();
	$method   = $order->get_payment_method();
	$gateway  = isset( $gateways[ $method ] ) ? $gateways[ $method ] : null;

	if ( $gateway && $gateway->get_option( 'forwarding_fee' ) ) {
		$total_rows['order_total_forwarding_fee'] = array(
			'label' => '',
			'value' => sprintf( __( 'Plus %s forwarding fee (charged by the transport agent)', 'woocommerce-germanized' ), wc_price( $gateway->get_option( 'forwarding_fee' ) ) ),
		);
	}

	return $total_rows;
}

add_filter( 'woocommerce_get_order_item_totals', 'wc_gzd_cart_forwarding_fee_notice_filter', 1500, 2 );

function wc_gzd_order_supports_parcel_delivery_reminder( $order_id ) {
	if ( $order = wc_get_order( $order_id ) ) {
		if ( 'yes' === $order->get_meta( '_parcel_delivery_opted_in', true ) ) {
			return true;
		}
	}

	return false;
}

function wc_gzd_order_applies_for_photovoltaic_system_vat_exemption( $order_id ) {
	if ( $order = wc_get_order( $order_id ) ) {
		$country = $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country();

		if ( 'yes' === $order->get_meta( '_photovoltaic_systems_opted_in', true ) && 'DE' === $country && 'DE' === wc_gzd_get_base_country() ) {
			return true;
		}
	}

	return false;
}

function wc_gzd_get_order_min_age( $order_id ) {
	$min_age = false;

	if ( $order = wc_get_order( $order_id ) ) {
		$min_age = $order->get_meta( '_min_age', true );

		if ( '' === $min_age || ! is_numeric( $min_age ) ) {
			$min_age = false;
		}
	}

	/**
	 * Filters the minimum age required for a certain order.
	 *
	 * @param integer|boolean $min_age The minimum age for an order. False if not available.
	 * @param integer $order_id The order id
	 *
	 * @since 3.0.0
	 *
	 */
	return apply_filters( 'woocommerce_gzd_order_min_age', $min_age, $order_id );
}

function wc_gzd_get_order_defect_descriptions( $order_id ) {
	$defect_descriptions = array();

	if ( $order = wc_get_order( $order_id ) ) {
		$defect_descriptions = wc_gzd_get_cart_defect_descriptions( $order->get_items( 'line_item' ) );
	}

	return $defect_descriptions;
}

function wc_gzd_order_has_age_verification( $order_id ) {
	$age                = wc_gzd_get_order_min_age( $order_id );
	$needs_verification = false;

	if ( $age ) {
		$needs_verification = true;
	}

	/**
	 * Filter to determine whether an order needs age verification or not.
	 *
	 * @param boolean $needs_verification Whether the order needs age verification or not.
	 * @param integer $order_id The order id
	 *
	 * @since 3.0.0
	 *
	 */
	return apply_filters( 'woocommerce_gzd_order_needs_age_verification', $needs_verification, $order_id );
}

/**
 * Whether the order contains items which are exempt from the right of withdrawal
 * for a certain type, e.g. digital items or services.
 *
 * @param WC_Order|int $order
 * @param string $type Either digital or service.
 *
 * @return boolean
 */
function wc_gzd_order_has_revocation_exempt_items( $order, $type = 'digital' ) {
	$order       = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order );
	$has_exempts = false;

	if ( $order ) {
		foreach ( $order->get_items() as $item ) {
			if ( ! is_callable( array( $item, 'get_product' ) ) || ! ( $product = $item->get_product() ) ) {
				continue;
			}

			if ( wc_gzd_is_revocation_exempt( $product, $type, $item ) || apply_filters( 'woocommerce_gzd_product_is_revocation_exception', false, $product, $type, $item ) ) {
				$has_exempts = true;
				break;
			}
		}
	}

	return $has_exempts;
}

/**
 * Whether the customer has expressly agreed to the early performance of the contract
 * (and thus to the loss of the right of withdrawal) for digital items (download) or services (service).
 *
 * Uses the consent logged while placing the order. Orders placed before the consent was logged
 * fall back to the checkbox configuration: Only a mandatory checkbox implies consent.
 *
 * @param WC_Order $order
 * @param string $checkbox_id Either download or service.
 *
 * @return boolean
 */
function wc_gzd_order_has_revocation_exempt_consent( $order, $checkbox_id = 'download' ) {
	$has_consent = false;
	$manager     = WC_GZD_Legal_Checkbox_Manager::instance();

	if ( is_a( $order, 'WC_Order' ) && ( $checkbox = $manager->get_checkbox( $checkbox_id ) ) ) {
		if ( $manager->is_logged( $checkbox_id, $order ) ) {
			$has_consent = $manager->is_checked( $checkbox_id, $order );
		} else {
			$has_consent = $checkbox->is_enabled() && $checkbox->is_shown() && $checkbox->is_mandatory();
		}
	}

	/**
	 * Filter whether the customer has expressly agreed to the early performance of the contract for
	 * digital items (download) or services (service).
	 *
	 * @param boolean $has_consent Whether the customer has agreed or not.
	 * @param WC_Order $order The order instance.
	 * @param string $checkbox_id The checkbox id, e.g. download or service.
	 *
	 * @since 4.2.0
	 */
	return apply_filters( 'woocommerce_gzd_order_has_revocation_exempt_consent', $has_consent, $order, $checkbox_id );
}

function wc_gzd_order_is_anonymized( $order ) {
	if ( is_numeric( $order ) ) {
		$order = wc_get_order( $order );
	}

	$is_anyomized = $order->get_meta( '_anonymized' );

	return 'yes' === $is_anyomized;
}

function wc_gzd_get_order_date( $order, $format = '' ) {
	return wc_format_datetime( $order->get_date_created(), $format );
}

/**
 * @param WC_Order $order
 * @param string $type
 */
function wc_gzd_get_order_customer_title( $order, $type = 'billing' ) {
	$title_formatted = '';

	if ( $title = $order->get_meta( "_{$type}_title", true ) ) {
		$title_formatted = wc_gzd_get_customer_title( $title );
	}

	return $title_formatted;
}

/**
 * @param WC_Order_Item $order_item
 *
 * @return WC_GZD_Order_Item|WC_GZD_Order_Item_Product|false
 */
function wc_gzd_get_order_item( $order_item ) {
	if ( ! $order_item ) {
		return false;
	}

	$classname = 'WC_GZD_Order_Item';

	if ( is_a( $order_item, 'WC_Order_Item_Product' ) ) {
		$classname = 'WC_GZD_Order_Item_Product';
	}

	if ( ! class_exists( $classname ) ) {
		$classname = 'WC_GZD_Order_Item';
	}

	return new $classname( $order_item );
}
