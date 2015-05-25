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

function wc_gzd_is_customer_activated( $user_id ) {
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