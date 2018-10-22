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

function wc_ts_get_crud_data( $object, $key, $suppress_suffix = false ) {
	return wc_gzd_get_crud_data( $object, $key, $suppress_suffix );
}

function wc_ts_woocommerce_supports_crud() {
	return wc_gzd_get_dependencies()->woocommerce_version_supports_crud();
}

function wc_ts_help_tip( $tip, $allow_html = false ) {
	return wc_gzd_help_tip( $tip, $allow_html );
}

function wc_ts_set_crud_data( $object, $key, $value ) {
	return wc_gzd_set_crud_data( $object, $key, $value );
}