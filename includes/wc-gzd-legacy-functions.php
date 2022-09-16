<?php
/**
 * Legacy Functions
 *
 * WC_GZD legacy functions.
 *
 * @author        Vendidero
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

function wc_gzd_get_crud_data( $object, $key, $suppress_suffix = false ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return '';
}

function wc_gzd_set_crud_data( $object, $key, $value ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return $object;
}

function wc_gzd_set_crud_meta_data( $object, $key, $value ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return $object;
}

function wc_gzd_unset_crud_meta_data( $object, $key ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return $object;
}

function wc_gzd_set_crud_term_data( $object, $term, $taxonomy ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return $object;
}

function wc_gzd_unset_crud_term_data( $object, $taxonomy ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return $object;
}

function wc_gzd_get_order_item_product( $item, $order ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return $item->get_product();
}

function wc_gzd_get_variable_visible_children( $product ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return $product->get_visible_children();
}

function wc_gzd_get_price_including_tax( $product, $args = array() ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return wc_get_price_including_tax( $product, $args );
}

function wc_gzd_get_price_excluding_tax( $product, $args = array() ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return wc_get_price_excluding_tax( $product, $args );
}

function wc_gzd_get_variation( $parent, $variation ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return wc_get_product( $variation );
}

function wc_gzd_get_order_currency( $order ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return $order->get_currency();
}

function wc_gzd_reduce_order_stock( $order_id ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	wc_maybe_reduce_stock_levels( $order_id );
}

function wc_gzd_get_product_type( $id ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return WC_Product_Factory::get_product_type( $id );
}

function wc_gzd_get_product_name( $product ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return $product->get_name();
}

function wc_gzd_get_cart_url() {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return wc_get_cart_url();
}

function wc_gzd_get_checkout_url() {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return wc_get_checkout_url();
}

function wc_gzd_help_tip( $tip, $allow_html = false ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	wc_help_tip( $tip, $allow_html );
}

function wc_gzd_string_to_bool( $string ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return wc_string_to_bool( $string );
}

function wc_gzd_bool_to_string( $bool ) {
	wc_deprecated_function( __FUNCTION__, '3.0' );

	return wc_bool_to_string( $bool );
}

add_action(
	'init',
	function() {
		if ( ! function_exists( 'wc_ts_set_crud_data' ) ) {
			function wc_ts_set_crud_data( $object, $key, $value ) {
				wc_deprecated_function( __FUNCTION__, '3.10' );

				return $object;
			}
		}

		if ( ! function_exists( 'wc_ts_get_crud_data' ) ) {
			function wc_ts_get_crud_data( $object, $key, $suppress_suffix = false ) {
				wc_deprecated_function( __FUNCTION__, '3.10' );

				return '';
			}
		}
	},
	0
);
