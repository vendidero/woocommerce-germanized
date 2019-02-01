<?php
/**
 * Core Functions
 *
 * WC_GZD_TS core functions.
 *
 * @author 		Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! function_exists( 'wc_ts_get_crud_data' ) ) {

	function wc_ts_get_crud_data( $object, $key, $suppress_suffix = false ) {
		return wc_gzd_get_crud_data( $object, $key, $suppress_suffix );
	}
}

if ( ! function_exists( 'wc_ts_woocommerce_supports_crud' ) ) {

	function wc_ts_woocommerce_supports_crud() {
		return wc_gzd_get_dependencies()->woocommerce_version_supports_crud();
	}
}

if ( ! function_exists( 'wc_ts_help_tip' ) ) {

	function wc_ts_help_tip( $tip, $allow_html = false ) {
		return wc_gzd_help_tip( $tip, $allow_html );
	}
}

if ( ! function_exists( 'wc_ts_set_crud_data' ) ) {

	function wc_ts_set_crud_data( $object, $key, $value ) {
		return wc_gzd_set_crud_data( $object, $key, $value );
	}
}

if ( ! function_exists( 'wc_ts_get_order_date' ) ) {

	function wc_ts_get_order_date( $order, $date_format = '' ) {
		return wc_gzd_get_order_date( $order, $date_format );
	}
}

if ( ! function_exists( 'wc_ts_get_order_currency' ) ) {

	function wc_ts_get_order_currency( $order ) {
		return wc_gzd_get_order_currency( $order );
	}
}

if ( ! function_exists( 'wc_ts_get_order_language' ) ) {

    function wc_ts_get_order_language( $order ) {
        $order_id = is_numeric( $order ) ? $order : wc_ts_get_crud_data( $order, 'id' );

        return get_post_meta( $order_id, 'wpml_language', true );
    }
}

if ( ! function_exists( 'wc_ts_switch_language' ) ) {

    function wc_ts_switch_language( $lang, $set_default = false ) {
        if ( $compatibility = WC_germanized()->get_compatibility( 'wpml' ) ) {
            $compatibility->set_language( $lang, $set_default );
        }
    }
}

if ( ! function_exists( 'wc_ts_restore_language' ) ) {

    function wc_ts_restore_language() {
        if ( $compatibility = WC_germanized()->get_compatibility( 'wpml' ) ) {
            $compatibility->restore_language();
        }
    }
}