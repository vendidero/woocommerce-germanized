<?php
/**
 * Order Functions
 *
 * WC_GZD order functions.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function wc_gzd_cart_forwarding_fee_notice_filter( $total_rows, $order ) {
	$gateways = WC()->payment_gateways()->get_available_payment_gateways();
	$gateway = isset( $gateways[ wc_gzd_get_crud_data( $order, 'payment_method' ) ] ) ? $gateways[ wc_gzd_get_crud_data( $order, 'payment_method' ) ] : null;

	if ( $gateway && $gateway->get_option( 'forwarding_fee' ) ) {
		$total_rows['order_total_forwarding_fee'] = array(
			'label' => '',
			'value'	=> sprintf( __( 'Plus %s forwarding fee (charged by the transport agent)', 'woocommerce-germanized' ), wc_price( $gateway->get_option( 'forwarding_fee' ) ) ),
		);
	}
	return $total_rows;
}

add_filter( 'woocommerce_get_order_item_totals', 'wc_gzd_cart_forwarding_fee_notice_filter', PHP_INT_MAX, 2 );

function wc_gzd_order_supports_parcel_delivery_reminder( $order_id ) {
	$order = wc_get_order( $order_id );
	
	if ( wc_gzd_get_crud_data( $order, 'parcel_delivery_opted_in' ) === 'yes' )
		return true;
	
	return false;
}