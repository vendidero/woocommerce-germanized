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
        global $sitepress;
        global $wc_ts_original_lang;

        if ( $set_default ) {
            $wc_ts_original_lang = $lang;
        }

        if ( isset( $sitepress ) && is_callable( array( $sitepress, 'get_current_language' ) ) && is_callable( array( $sitepress, 'switch_lang' ) ) ) {
            if ( $sitepress->get_current_language() != $lang ) {

                $sitepress->switch_lang( $lang, true );

                // Somehow WPML doesn't automatically change the locale
                if ( is_callable( array( $sitepress, 'reset_locale_utils_cache' ) ) ) {
                    $sitepress->reset_locale_utils_cache();
                }

                if ( function_exists( 'switch_to_locale' ) ) {
                    switch_to_locale( get_locale() );

                    // Filter on plugin_locale so load_plugin_textdomain loads the correct locale.
                    add_filter( 'plugin_locale', 'get_locale' );

                    // Init WC locale.
                    WC()->load_plugin_textdomain();
                    WC_germanized()->load_plugin_textdomain();
                    WC_germanized()->trusted_shops->refresh();
                }

                do_action( 'woocommerce_gzd_trusted_shops_switched_language', $lang, $wc_ts_original_lang );
            }
        }

        do_action( 'woocommerce_gzd_trusted_shops_switch_language', $lang, $wc_ts_original_lang );
    }
}

if ( ! function_exists( 'wc_ts_restore_language' ) ) {

    function wc_ts_restore_language() {
        global $wc_ts_original_lang;

        if ( isset( $wc_ts_original_lang ) && ! empty( $wc_ts_original_lang ) ) {
            wc_ts_switch_language( $wc_ts_original_lang );
        }
    }
}