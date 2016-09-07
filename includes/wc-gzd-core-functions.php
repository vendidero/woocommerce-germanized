<?php
/**
 * Core Functions
 *
 * WC_GZD core functions.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include( 'wc-gzd-product-functions.php' );

/**
 * Format tax rate percentage for output in frontend
 *  
 * @param  float  $rate   
 * @param  boolean $percent show percentage after number
 * @return string
 */
function wc_gzd_format_tax_rate_percentage( $rate, $percent = false ) {
	return str_replace( '.', ',', wc_format_decimal( str_replace( '%', '', $rate ), true, true ) ) . ( $percent ? '%' : '' );
}

function wc_gzd_is_customer_activated( $user_id = '' ) {
	
	if ( is_user_logged_in() && empty( $user_id ) )
		$user_id = get_current_user_id();

	if ( empty( $user_id ) || ! $user_id )
		return false;

	return ( get_user_meta( $user_id, '_woocommerce_activation' ) ? false : true );
}

function wc_gzd_get_hook_priority( $hook ) {
	return WC_GZD_Hook_Priorities::instance()->get_hook_priority( $hook );
}

function wc_gzd_get_email_attachment_order() {
	$order = explode( ',', get_option( 'woocommerce_gzd_mail_attach_order', 'terms,revocation,data_security,imprint' ) );
	$items = array();

	foreach ( $order as $key => $item ) {
		$title = '';
		switch( $item ) {
			case "terms":
				$title = __( 'Terms & Conditions', 'woocommerce-germanized' );
			break;
			case "revocation":
				$title = __( 'Right of Recission', 'woocommerce-germanized' );
			break;
			case "imprint":
				$title = __( 'Imprint', 'woocommerce-germanized' );
			break;
			case "data_security":
				$title = __( 'Data Security', 'woocommerce-germanized' );
			break;
		}

		$items[ $item ] = $title;
	}
	
	return $items;	
}

function wc_gzd_get_page_permalink( $page ) {
	$page_id   = wc_get_page_id( $page );
	$permalink = $page_id ? get_permalink( $page_id ) : '';
	return apply_filters( 'woocommerce_get_' . $page . '_page_permalink', $permalink );
}

if ( ! function_exists( 'is_payment_methods' ) ) {

	/**
	 * is_checkout - Returns true when viewing the checkout page.
	 * @return bool
	 */
	function is_payment_methods() {
		return is_page( wc_get_page_id( 'payment_methods' ) ) || apply_filters( 'woocommerce_gzd_is_payment_methods', false ) ? true : false;
	}
}

function wc_gzd_get_small_business_notice() {
	return apply_filters( 'woocommerce_gzd_small_business_notice', __( 'Because of the small business owner state according to &#167; 19 UStG the seller does not levy nor state the German value added tax.', 'woocommerce-germanized' ) );
}

function wc_gzd_help_tip( $tip, $allow_html = false ) {
	
	if ( function_exists( 'wc_help_tip' ) )
		return wc_help_tip( $tip, $allow_html );

	return '<a class="tips" data-tip="' . ( $allow_html ? esc_html( $tip ) : $tip ) . '" href="#">[?]</a>';
}
